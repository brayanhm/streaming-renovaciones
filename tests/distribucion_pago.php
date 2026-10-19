<?php
declare(strict_types=1);

trait FailDistributionAfterPayment
{
    protected function setDistribution(int $id): void
    {
        parent::setDistribution($id);
        throw new RuntimeException('Fallo de selección simulado después de registrar el pago.');
    }
}
final class PaymentFailureChatgpt extends \App\Models\ChatgptCuenta { use FailDistributionAfterPayment; }
final class PaymentFailureClaude extends \App\Models\ClaudeCuenta { use FailDistributionAfterPayment; }

/** Dos solicitudes independientes detenidas ante el mismo bloqueo de la cuenta. */
function concurrentDistributionPayments(int $id, string $service, string $date, array $tokens): array
{
    $locker = clPdo();
    $locker->beginTransaction();
    $locker->query('SELECT id FROM cuentas_principales WHERE id = ' . $id . ' FOR UPDATE')->fetch();
    $children = [];
    try {
        foreach ($tokens as $token) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . session_save_path(), __DIR__ . '/claude.php',
                '--payment-worker', $service, (string) $id, $date, '123.45', $token],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            clCheck(is_resource($process), 'No pudo iniciarse el proceso de pago.');
            fclose($pipes[0]);
            $children[] = [$process, $pipes];
            stream_set_timeout($pipes[1], 10);
            clEq(trim((string) fgets($pipes[1])), 'READY', 'El proceso de pago no inició.');
        }
        $locker->rollBack();
        $results = [];
        foreach ($children as [$process, $pipes]) {
            $results[] = trim((string) stream_get_contents($pipes[1]));
            $errors = trim((string) stream_get_contents($pipes[2]));
            fclose($pipes[1]); fclose($pipes[2]);
            clEq(proc_close($process), 0, 'Falló un proceso de pago.');
            clEq($errors, '', 'Un proceso de pago produjo errores.');
        }
        $children = [];
        sort($results);
        return $results;
    } finally {
        if ($locker->inTransaction()) $locker->rollBack();
        foreach ($children as [$process, $pipes]) {
            if (is_resource($process)) {
                proc_terminate($process);
                foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
                proc_close($process);
            }
        }
    }
}

/** Pruebas de pago y selección atómicos, siempre en la base efímera de claude.php. */
function runDistributionPaymentTests(PDO $pdo, callable $test): void
{
    $today = date('Y-m-d');
    foreach ([\App\Models\ChatgptCuenta::class, \App\Models\ClaudeCuenta::class] as $class) {
        $service = $class === \App\Models\ChatgptCuenta::class ? 'chatgpt' : 'claude';
        $create = static function ($model, bool $expired = true) use ($today): int {
            return $model->create(['etiqueta' => 'Pago distribución fixture', 'correo' => bin2hex(random_bytes(8)) . '@example.invalid',
                'password_cuenta' => 'fixture-only', 'fecha_pago' => $expired ? (new DateTimeImmutable('today'))->modify('-90 days')->format('Y-m-d') : $today,
                'monto_bs' => '200.00']);
        };

        $test("pago-distribución {$service}: cuenta Gratis recibe el costo real y vigencia de hoy sin liberar cupos", static function () use ($pdo, $class, $create, $today): void {
            $model = new $class($pdo);
            $previous = $create($model, false);
            $id = $create($model);
            $model->selectDistribution($previous);
            $model->adjustOccupation($id, 2, 0, 'Cupos anteriores', 'fixture', clToken());
            $sales = $model->sales($id);
            $oldPayments = $model->payments($id);
            clEq($model->find($id)['puede_pagar_distribucion'], true, 'La cuenta Gratis preparada debe ofrecer pago y selección.');
            $paymentId = $model->payAndSelectDistribution($id, $today, '145,50', clToken());
            $account = $model->find($id);
            $dates = $class::paymentDates($today);
            clEq($account['estado_periodo'], 'PAGADO', 'Debe quedar pagada desde hoy.');
            clEq($account['fecha_inicio'], $today, 'El pago debe usar la fecha de hoy.');
            clEq($account['fecha_vencimiento'], $dates['fin_pagado'], 'Debe usar el mes calendario del servicio.');
            clEq($account['fin_gracia'], $dates['fin_gracia'], 'Solo ChatGPT debe añadir gracia.');
            clEq($account['ocupados'], 2, 'Pagar y distribuir no libera ocupación.');
            clEq($account['ciclo_actual'], 0, 'Pagar no inicia otro ciclo.');
            clEq($model->sales($id), $sales, 'Debe conservar las ventas.');
            $payments = $model->payments($id);
            clEq(count($payments), count($oldPayments) + 1, 'Debe registrar exactamente un pago nuevo.');
            clEq((int) $payments[0]['id'], $paymentId, 'Debe devolver el pago recién creado.');
            clEq($payments[0]['monto_bs'], '145.50', 'Debe registrar el costo real, sin inventar uno.');
            clEq($payments[0]['fecha_pago'], $today, 'El historial contable debe conservar la fecha de hoy.');
            clEq((string) $account['pagos_bs'], '345.50', 'El gasto debe sumarse a los pagos históricos.');
            clEq((int) $model->currentDistribution()['id'], $id, 'Debe reemplazar la cuenta en distribución.');
            clEq($model->find($previous)['distribucion_actual'], false, 'La anterior debe dejar de estar seleccionada.');
            clCheck($account['inicio_distribucion'] !== null, 'Debe registrar el inicio.');
        });

        $test("pago-distribución {$service}: Gratis manual renueva fechas y los reintentos no duplican ni reseleccionan", static function () use ($pdo, $class, $create, $today): void {
            $model = new $class($pdo);
            $id = $create($model, false);
            $other = $create($model, false);
            $model->selectDistribution($id);
            $model->setFreePlan($id, true);
            $start = $model->find($id)['inicio_distribucion'];
            $token = clToken();
            $payment = $model->payAndSelectDistribution($id, $today, '120.00', $token);
            $history = $model->distributionHistory($id);
            clEq((int) $model->find($id)['plan_gratis'], 0, 'El pago debe quitar la marca manual Gratis.');
            clEq($model->find($id)['inicio_distribucion'], $start, 'Debe conservar el inicio del ciclo actual.');
            clReject(static fn() => $model->payAndSelectDistribution($id, $today, '120.00', clToken()), 'Un formulario Gratis antiguo no puede registrar otro pago en una cuenta ya pagada.');
            $model->selectDistribution($other);
            clEq($model->payAndSelectDistribution($id, $today, '120', $token), $payment, 'Repetir el token recupera el pago original.');
            clEq(count($model->payments($id)), 2, 'Los reintentos no duplican gastos.');
            clEq((int) $model->currentDistribution()['id'], $other, 'Un reintento no vuelve a seleccionar la cuenta anterior.');
            clEq($model->distributionHistory($id), $history, 'Los reintentos no alteran fechas.');
            clReject(static fn() => $model->payAndSelectDistribution($id, $today, '121', $token), 'No permite reutilizar el token con otro costo.');
            clReject(static fn() => $model->payAndSelectDistribution($other, $today, '120', $token), 'No permite reutilizar el token en otra cuenta.');
        });

        $test("pago-distribución {$service}: datos inválidos o cuentas no preparadas no dejan pagos ni cambian selección", static function () use ($pdo, $class, $create, $today): void {
            $model = new $class($pdo);
            $id = $create($model);
            $current = $create($model, false);
            $model->selectDistribution($current);
            $payments = $model->payments($id);
            foreach (['', '0', '-1', '1e2', '1.001', 'abc'] as $amount) {
                clReject(static fn() => $model->payAndSelectDistribution($id, $today, $amount, clToken()), 'Un costo inválido debe rechazarse.');
            }
            foreach (['2000-01-01', '2999-01-01'] as $date) {
                clReject(static fn() => $model->payAndSelectDistribution($id, $date, '100', clToken()), 'Este flujo solo permite pagar hoy.');
            }
            $model->setActive($id, false);
            clReject(static fn() => $model->payAndSelectDistribution($id, $today, '100', clToken()), 'Una cuenta inactiva no debe pagarse para distribuir.');
            $model->setActive($id, true);
            $pdo->exec('UPDATE cuentas_principales SET password_cuenta = NULL WHERE id = ' . $id);
            clReject(static fn() => $model->payAndSelectDistribution($id, $today, '100', clToken()), 'Sin credenciales no debe dejar un pago.');
            clEq($model->find($id)['puede_pagar_distribucion'], false, 'No debe ofrecer el pago si faltan credenciales.');
            clEq($model->payments($id), $payments, 'Los rechazos no deben dejar pagos parciales.');
            clEq((int) $model->currentDistribution()['id'], $current, 'Los rechazos conservan la cuenta actual.');
            clEq($model->distributionHistory($id), [], 'Los rechazos no inventan el inicio.');
            clReject(static fn() => $model->payAndSelectDistribution($current, $today, '100', clToken()), 'Una cuenta pagada solo necesita selección, no otro gasto.');
        });

        $test("pago-distribución {$service}: un fallo al seleccionar revierte también el pago y las fechas", static function () use ($pdo, $class, $service, $create, $today): void {
            $model = new $class($pdo);
            $current = $create($model, false);
            $id = $create($model);
            $model->selectDistribution($current);
            $before = $model->find($id);
            $payments = $model->payments($id);
            $failureClass = $service === 'chatgpt' ? PaymentFailureChatgpt::class : PaymentFailureClaude::class;
            $failureModel = new $failureClass($pdo);
            clReject(static fn() => $failureModel->payAndSelectDistribution($id, $today, '100', clToken()), 'Debe producir el fallo de selección simulado.');
            clEq($model->find($id), $before, 'El fallo debe revertir plan, vigencia e inicio.');
            clEq($model->payments($id), $payments, 'El fallo debe revertir el gasto.');
            clEq((int) $model->currentDistribution()['id'], $current, 'El fallo debe revertir el cambio de cuenta.');
        });

        $test("pago-distribución {$service}: solicitudes simultáneas registran un solo gasto", static function () use ($pdo, $class, $service, $create, $today): void {
            $model = new $class($pdo);
            $id = $create($model);
            clEq(concurrentDistributionPayments($id, $service, $today, [clToken(), clToken()]), ['PAID', 'REJECTED'], 'Solo una solicitud puede pagar una cuenta Gratis.');
            clEq(count($model->payments($id)), 2, 'Debe conservar un pago inicial y solo un pago de activación.');
            clEq((int) $model->currentDistribution()['id'], $id, 'La cuenta debe quedar seleccionada.');
            clEq(count($model->distributionHistory($id)), 1, 'La activación concurrente no duplica el inicio.');
        });

        $test("pago-distribución {$service}: formulario del índice exige costo, usa hoy y respeta permisos", static function () use ($pdo, $class, $service, $create, $today): void {
            $pdo->prepare('INSERT INTO usuarios (username, rol) VALUES (:usuario, :rol)')
                ->execute(['usuario' => 'pagar-distribuir-' . $service, 'rol' => 'admin']);
            $userId = (int) $pdo->lastInsertId();
            $_SESSION['auth'] = ['id' => $userId, 'username' => 'pago-distribucion-fixture', 'rol' => 'admin'];
            $model = new $class($pdo);
            $id = $create($model);
            $current = $create($model, false);
            $model->selectDistribution($current);
            $controllerClass = $service === 'chatgpt' ? DistributionChatgptController::class : DistributionClaudeController::class;
            $controller = new $controllerClass();
            $_GET = ['q' => 'Pago distribución fixture'];
            try { $controller->index(); } catch (DistributionPage $page) { $data = $page->data; }
            clCheck(isset($data), 'Debe devolver el índice.');
            $data['rows'] = array_values(array_filter($data['rows'], static fn(array $row): bool => in_array((int) $row['id'], [$id, $current], true)));
            $html = (static function (array $data) use ($service): string {
                extract($data);
                ob_start();
                try {
                    require APP_PATH . '/views/layouts/header.php';
                    require APP_PATH . '/views/' . $service . '/index.php';
                    require APP_PATH . '/views/layouts/footer.php';
                    return (string) ob_get_contents();
                } finally { ob_end_clean(); }
            })($data);
            clCheck(str_contains($html, 'Pagar y distribuir') && str_contains($html, 'Costo real en Bs'), 'La cuenta Gratis debe pedir el costo antes de distribuir.');
            clCheck(str_contains($html, date('d/m/Y')), 'Debe explicar que el pago se registra hoy.');
            $previewDir = (string) getenv('DISTRIBUTION_PREVIEW_DIR');
            if ($previewDir !== '' && is_dir($previewDir)) file_put_contents($previewDir . '/' . $service . '-pago-distribucion.html', $html);
            $_POST = ['origen' => 'listado', 'registrar_pago' => '1', 'token_operacion' => $data['indexSelectionToken'],
                'monto_bs' => '230.25', 'fecha_pago' => '2000-01-01', 'q' => 'Pago distribución fixture'];
            try { $controller->selectDistribution($id); } catch (DistributionRedirect $redirect) {}
            clEq($model->payments($id)[0]['fecha_pago'], $today, 'La fecha enviada por el cliente no debe sustituir hoy.');
            clEq($model->payments($id)[0]['monto_bs'], '230.25', 'Debe registrar el costo ingresado.');
            clEq((int) $model->currentDistribution()['id'], $id, 'El formulario debe pagar y seleccionar.');
            $other = $create($model);
            $before = $model->payments($other);
            $pdo->exec("UPDATE usuarios SET rol = 'operador' WHERE id = " . $userId);
            $_POST['token_operacion'] = $controller->formToken('distribuir_listado', 0);
            try { $controller->selectDistribution($other); } catch (DistributionRedirect $redirect) {}
            clEq($model->payments($other), $before, 'Un operador no puede registrar el pago del listado.');
            clEq((int) $model->currentDistribution()['id'], $id, 'Un operador no puede cambiar la selección.');
            $_POST = []; $_GET = []; unset($_SESSION['auth']);
        });
    }
}
