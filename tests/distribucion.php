<?php
declare(strict_types=1);

final class DistributionRedirect extends Exception {}
final class DistributionJson extends Exception
{
    public function __construct(public array $data, public int $status) { parent::__construct('Respuesta JSON fixture'); }
}
final class DistributionPage extends Exception
{
    public function __construct(public array $data) { parent::__construct('Vista fixture'); }
}
trait DistributionControllerHarness
{
    protected function jsonResponse(array $data, int $status): void
    {
        throw new DistributionJson($data, $status);
    }
    protected function render(string $view, array $data = []): void
    {
        throw new DistributionPage($data);
    }
    protected function redirect(string $path): void
    {
        throw new DistributionRedirect($path);
    }
    public function formToken(string $action, int $id): string
    {
        return $this->issueToken($action, $id);
    }
}
final class DistributionChatgptController extends \App\Controllers\ChatgptController { use DistributionControllerHarness; }
final class DistributionClaudeController extends \App\Controllers\ClaudeController { use DistributionControllerHarness; }

/** Se ejecuta desde claude.php, dentro de su base efímera y aislada. */
function runDistributionTests(PDO $pdo, string $date, callable $test): void
{
    $create = static function (\App\Models\CuentaCompartida $model, int $capacity = 3) use ($date): int {
        return $model->create([
            'etiqueta' => 'Distribución fixture', 'correo' => bin2hex(random_bytes(8)) . '@example.invalid',
            'password_cuenta' => 'fixture-only', 'capacidad' => $capacity, 'fecha_pago' => $date, 'monto_bs' => '200.00',
        ]);
    };
    $token = static fn(): string => bin2hex(random_bytes(32));
    $test('distribución: crear y pagar no cambian la cuenta seleccionada por servicio', static function () use ($pdo, $create, $token, $date): void {
        $chatgpt = new \App\Models\ChatgptCuenta($pdo);
        $claude = new \App\Models\ClaudeCuenta($pdo);
        $a = $create($chatgpt);
        $b = $create($claude);
        $chatgpt->selectDistribution($a);
        $claude->selectDistribution($b);
        $reserve = $create($chatgpt);
        $chatgpt->registerPayment($reserve, $date, '210.00', $token());
        clEq((int) $chatgpt->currentDistribution()['id'], $a, 'Crear o pagar no selecciona otra cuenta.');
        clEq((int) $claude->currentDistribution()['id'], $b, 'ChatGPT no modifica la selección Claude.');
        clEq($chatgpt->find($reserve)['distribucion_actual'], false, 'La reserva no debe estar seleccionada.');
        clEq((int) $pdo->query('SELECT COUNT(*) FROM cuentas_distribucion')->fetchColumn(), 2, 'Solo debe existir una fila por servicio.');
    });

    foreach ([\App\Models\ChatgptCuenta::class, \App\Models\ClaudeCuenta::class] as $class) {
        $label = $class === \App\Models\ChatgptCuenta::class ? 'ChatGPT' : 'Claude';
        $test("distribución {$label}: cambiar selección conserva accesos y bloquea la cuenta anterior para todos", static function () use ($pdo, $class, $create, $token, $date): void {
            $model = new $class($pdo);
            $a = $create($model);
            $b = $create($model);
            clReject(static fn() => $model->sell($a, 1, $date, $token()), 'El administrador tampoco puede vender en una reserva.');
            $model->selectDistribution($a);
            $model->sell($a, 1, $date, $token());
            $history = $model->sales($a);
            $model->selectDistribution($b);
            clEq($model->sales($a), $history, 'Cambiar selección no retira accesos.');
            clEq($model->find($a)['ocupados'], 1, 'La cuenta anterior conserva ocupación.');
            clReject(static fn() => $model->sell($a, 1, $date, $token()), 'Un vendedor no puede usar la cuenta anterior.');
            clReject(static fn() => $model->sell($a, 1, $date, $token()), 'Las ventas adicionales tampoco se permiten fuera de la seleccionada.');
            $model->selectDistribution($a);
            clEq($model->find($a)['ocupados'], 1, 'Volver a seleccionarla no reinicia cupos.');
        });

        $test("distribución {$label}: capacidad configurable como referencia sin detener las entregas", static function () use ($pdo, $class, $create, $token, $date): void {
            $model = new $class($pdo);
            $id = $create($model, 1);
            $model->selectDistribution($id);
            $model->sell($id, 1, $date, $token());
            clEq($model->find($id)['estado_distribucion'], 'En distribución', 'Llegar a la referencia conserva la distribución.');
            clEq($model->find($id)['ventas_habilitadas'], true, 'La referencia no debe bloquear las entregas.');
            $model->sell($id, 1, $date, $token());
            clEq($model->find($id)['ocupados'], 2, 'Cualquier vendedor puede superar la referencia en la seleccionada.');
            $row = $model->find($id);
            $model->update($id, ['etiqueta' => $row['etiqueta'], 'correo' => $row['correo'], 'capacidad' => 4]);
            clEq($model->find($id)['capacidad'], 4, 'La capacidad se guarda por cuenta.');
            clEq($model->find($id)['estado_distribucion'], 'En distribución', 'Aumentar capacidad vuelve a permitir ventas.');
            foreach (['0', '-1', '1.5', '1e2', '100000', 'abc'] as $invalid) {
                clReject(static fn() => $model->update($id, ['etiqueta' => $row['etiqueta'], 'correo' => $row['correo'], 'capacidad' => $invalid]), 'Aceptó una capacidad inválida.');
            }
            clEq($model->find($id)['capacidad'], 4, 'Una capacidad inválida no modifica la cuenta.');
        });

        $test("distribución {$label}: solo la cuenta seleccionada está en distribución y conserva su ocupación", static function () use ($pdo, $class, $create, $token, $date): void {
            $model = new $class($pdo);
            $id = $create($model, 1);
            $other = $create($model);
            $model->selectDistribution($id);
            $model->sell($id, 1, $date, $token());
            $model->selectDistribution($other);
            clEq($model->find($id)['estado_distribucion'], 'Vendida', 'Una cuenta vendida no debe volver a reserva al elegir otra.');
            clEq($model->find($id)['distribucion_actual'], false, 'El estado Vendida no la mantiene seleccionada.');
            clReject(static fn() => $model->sell($id, 1, $date, $token()), 'Vendida no permite vender fuera de la selección.');
            $model->setFreePlan($id, true);
            clEq($model->find($id)['estado_distribucion'], 'Vendida', 'El plan y la ocupación son estados separados.');
            clEq($model->find($id)['estado_periodo'], 'GRATIS', 'La marca Gratis sigue vigente.');
            clReject(static fn() => $model->sell($id, 1, $date, $token()), 'Una cuenta vendida y Gratis sigue bloqueada para el administrador.');
            $model->setFreePlan($id, false);
            $model->selectDistribution($id);
            $model->setActive($id, false);
            clEq($model->find($id)['estado_distribucion'], 'En distribución', 'Desactivar bloquea entregas, pero no selecciona otra cuenta.');
            clReject(static fn() => $model->sell($id, 1, $date, $token()), 'Vendida no evita el bloqueo de una cuenta inactiva.');
            $model->setActive($id, true);
            $model->startCycle($id, true, 'fixture', $token(), $model->currentCycleId($id));
            clEq($model->find($id)['ocupados'], 0, 'Un nuevo ciclo reinicia la ocupación.');
            clEq($model->find($id)['estado_distribucion'], 'En distribución', 'Una cuenta reutilizada deja de estar Vendida.');
            clEq(count($model->sales($id)), 1, 'Se conserva la venta del ciclo anterior.');
            clEq(count($model->payments($id)), 1, 'Los cambios de estado no crean ni borran pagos.');
        });

        $test("distribución {$label}: Gratis manual bloquea ventas y restaurar conserva cupos", static function () use ($pdo, $class, $create, $token, $date): void {
            $model = new $class($pdo);
            $id = $create($model);
            $model->selectDistribution($id);
            $model->sell($id, 1, $date, $token());
            $model->setFreePlan($id, true);
            clEq($model->find($id)['estado_periodo'], 'GRATIS', 'Debe mostrar Gratis inmediatamente.');
            clReject(static fn() => $model->sell($id, 1, $date, $token()), 'Gratis bloquea incluso al administrador.');
            clReject(static fn() => $model->selectDistribution($id), 'No puede seleccionar Gratis.');
            clReject(static fn() => $model->startCycle($id, true, 'fixture', $token(), $model->currentCycleId($id)), 'No puede reiniciar Gratis.');
            $model->setFreePlan($id, false);
            clEq($model->find($id)['ocupados'], 1, 'Restaurar no reinicia cupos.');
            clEq(count($model->payments($id)), 1, 'Restaurar un plan vigente no inventa pagos.');
            $model->setFreePlan($id, true);
            $model->registerPayment($id, date('Y-m-d'), '205.00', $token());
            clEq($model->find($id)['estado_periodo'], 'PAGADO', 'El pago real elimina Gratis manual.');
            clEq($model->find($id)['ocupados'], 1, 'El nuevo pago conserva cupos.');
        });

        $test("distribución {$label}: ciclo requiere cierre confirmado y sus reintentos no vuelven a reiniciar", static function () use ($pdo, $class, $create, $token, $date): void {
            $model = new $class($pdo);
            $id = $create($model);
            $other = $create($model);
            $model->selectDistribution($id);
            $model->sell($id, 1, $date, $token());
            $history = $model->sales($id);
            $cycleToken = $token();
            clReject(static fn() => $model->startCycle($id, false, 'fixture', $cycleToken, $model->currentCycleId($id)), 'El cierre de sesiones es obligatorio.');
            clEq($model->sales($id), $history, 'La falta de confirmación no altera ventas.');
            clEq($model->startCycle($id, true, 'fixture', $cycleToken, $model->currentCycleId($id)), 1, 'Debe liberar la ocupación actual.');
            clEq($model->find($id)['ocupados'], 0, 'El ciclo comienza vacío.');
            $model->sell($id, 1, $date, $token());
            $model->selectDistribution($other);
            clEq($model->startCycle($id, true, 'fixture', $cycleToken, $model->currentCycleId($id)), 1, 'Repetir devuelve el resultado original.');
            clEq($model->find($id)['ocupados'], 1, 'Repetir el ciclo no libera una venta nueva.');
            clEq((int) $model->currentDistribution()['id'], $other, 'Repetir un ciclo viejo no cambia la selección.');
            clEq(count($model->cycles($id)), 1, 'El ciclo no debe duplicarse.');
            clEq(count($model->sales($id)), 2, 'Se conserva todo el historial.');
            clEq(count($model->payments($id)), 1, 'El ciclo no registra un pago ficticio.');
            clReject(static fn() => $model->startCycle($other, true, 'fixture', $cycleToken, $model->currentCycleId($other)), 'El token no puede usarse en otra cuenta.');
        });

        $test("distribución {$label}: vigencia terminada exige pago y no permite restaurar ni editar fechas para vender", static function () use ($pdo, $class, $create, $token, $date): void {
            $model = new $class($pdo);
            $id = $create($model);
            $model->selectDistribution($id);
            $model->sell($id, 1, $date, $token());
            $past = (new DateTimeImmutable('today'))->modify('-3 months')->format('Y-m-d');
            $dates = $class::paymentDates($past);
            $pdo->prepare('UPDATE cuentas_principales SET fecha_inicio = :inicio, fecha_vencimiento = :fin, fin_gracia = :gracia WHERE id = :id')
                ->execute(['inicio' => $past, 'fin' => $dates['fin_pagado'], 'gracia' => $dates['fin_gracia'], 'id' => $id]);
            clEq($model->find($id)['estado_periodo'], 'GRATIS', 'El vencimiento debe mostrar Gratis automáticamente.');
            clReject(static fn() => $model->setFreePlan($id, false), 'No puede restaurar una vigencia terminada.');
            if ($model instanceof \App\Models\ChatgptCuenta) {
                clReject(static fn() => $model->configureExistingPeriod($id, 'PAGO', date('Y-m-d')), 'Editar fechas no puede evitar el pago de renovación.');
            }
            clEq($model->find($id)['ocupados'], 1, 'El vencimiento conserva cupos.');
            $model->registerPayment($id, date('Y-m-d'), '200.00', $token());
            clEq($model->find($id)['estado_periodo'], 'PAGADO', 'El nuevo pago habilita la vigencia.');
        });
    }

    $test('distribución ChatGPT: dos dispositivos suman dos aunque superen la referencia y la gracia permite ventas', static function () use ($pdo, $create, $token, $date): void {
        $model = new \App\Models\ChatgptCuenta($pdo);
        $id = $model->create(['etiqueta' => 'Gracia fixture', 'correo' => bin2hex(random_bytes(8)) . '@example.invalid',
            'password_cuenta' => 'fixture-only', 'capacidad' => 2,
            'fecha_pago' => shift_months_clamped(new DateTimeImmutable('today'), -1)->format('Y-m-d'), 'monto_bs' => '200.00']);
        $model->selectDistribution($id);
        $model->sell($id, 1, $date, $token());
        $model->sell($id, 2, $date, $token());
        clEq($model->find($id)['ocupados'], 3, 'Dos dispositivos deben contarse aunque solo falte uno hasta la referencia.');
        $model->sell($id, 1, date('Y-m-d'), $token());
        clEq($model->find($id)['estado_periodo'], 'GRACIA', 'Debe permitir ventas durante la gracia.');
    });

    $test('permisos: el operador puede seguir vendiendo, pero no modificar la selección ni administrar cuentas', static function () use ($pdo, $create, $token, $date): void {
        $pdo->exec("CREATE TABLE usuarios (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(60), rol VARCHAR(20), activo TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB");
        $pdo->exec("INSERT INTO usuarios (username, rol) VALUES ('operador-fixture', 'operador')");
        $userId = (int) $pdo->lastInsertId();
        $redirect = static function (callable $action): void {
            try {
                $action();
            } catch (DistributionRedirect $redirect) {
                return;
            }
            throw new RuntimeException('La operación debe terminar con una redirección.');
        };
        foreach ([['chatgpt', \App\Models\ChatgptCuenta::class, DistributionChatgptController::class], ['claude', \App\Models\ClaudeCuenta::class, DistributionClaudeController::class]] as [$service, $modelClass, $controllerClass]) {
            $model = new $modelClass($pdo);
            $id = $create($model, 1);
            $model->selectDistribution($id);
            $model->sell($id, 1, $date, $token());
            $_SESSION['auth'] = ['id' => $userId, 'username' => 'operador-fixture', 'rol' => 'admin'];
            $controller = new $controllerClass();
            clEq($_SESSION['auth']['rol'], 'operador', 'La sesión debe reflejar el rol real.');
            $before = $model->find($id);
            foreach (['selectDistribution', 'startCycle', 'setPlan', 'pagar', 'update', 'adjustOccupation', 'toggleActive'] as $method) {
                $redirect(static fn() => $controller->$method($id));
                clEq($model->find($id), $before, 'Un operador no puede modificar distribución, ciclo, plan, pago o capacidad.');
            }
            $_POST = ['token_operacion' => $controller->formToken('vender', $id), 'fecha_venta' => $date, 'dispositivos' => '1', 'rol' => 'admin'];
            $_SESSION['auth']['rol'] = 'admin';
            $redirect(static fn() => $controller->vender($id));
            clEq($model->find($id)['ocupados'], 2, 'El operador puede seguir vendiendo en la seleccionada sin permisos administrativos.');
            $pdo->exec('UPDATE usuarios SET activo = 0 WHERE id = ' . $userId);
            $redirect(static fn() => new $controllerClass());
            clEq(auth_user(), null, 'Una cuenta de usuario inactiva debe perder la sesión.');
            $pdo->exec('UPDATE usuarios SET activo = 1 WHERE id = ' . $userId);
        }
        $_POST = [];
        unset($_SESSION['auth']);
    });
}
