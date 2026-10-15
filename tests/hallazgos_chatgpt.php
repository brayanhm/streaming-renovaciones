<?php
declare(strict_types=1);

final class LegacySubscriptionsHarness extends \App\Controllers\SuscripcionesController { use DistributionControllerHarness; }
final class LegacyDashboardHarness extends \App\Controllers\DashboardController { use DistributionControllerHarness; }

/** Regresiones del análisis de ChatGPT; se ejecutan en la base temporal de claude.php. */
function runChatgptFindingTests(PDO $pdo, callable $test): void
{
    foreach (['fecha_inicio' => 'DATE NULL', 'fecha_vencimiento' => 'DATE NULL', 'cliente_id' => 'INT NULL',
        'ultimo_contacto_fecha' => 'DATETIME NULL', 'ultimo_contacto_tipo' => 'VARCHAR(20) NULL'] as $column => $definition) {
        ensure_column($pdo, 'suscripciones', $column, 'ALTER TABLE suscripciones ADD COLUMN ' . $column . ' ' . $definition);
    }
    $pdo->exec("INSERT INTO usuarios (username, rol) VALUES ('hallazgos-admin', 'admin')");
    $admin = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO usuarios (username, rol) VALUES ('hallazgos-operador', 'operador')");
    $operator = (int) $pdo->lastInsertId();
    $today = date('Y-m-d');
    $token = static fn(): string => bin2hex(random_bytes(32));
    $create = static function ($model, int $capacity = 5, ?string $date = null) use ($today): int {
        return $model->create(['etiqueta' => 'Hallazgos fixture', 'correo' => bin2hex(random_bytes(8)) . '@example.invalid',
            'password_cuenta' => 'fixture-inicial', 'capacidad' => $capacity, 'fecha_pago' => $date ?? $today, 'monto_bs' => '200.00']);
    };
    $login = static function (string $class, int $user) {
        $_SESSION['auth'] = ['id' => $user, 'username' => 'hallazgos-fixture', 'rol' => 'admin'];
        return new $class();
    };
    $redirect = static function (callable $action): string {
        try { $action(); } catch (DistributionRedirect $result) { return $result->getMessage(); }
        throw new RuntimeException('Faltó la redirección.');
    };
    $page = static function (callable $action): array {
        try { $action(); } catch (DistributionPage $result) { return $result->data; }
        throw new RuntimeException('Faltó la vista.');
    };
    foreach ([['chatgpt', \App\Models\ChatgptCuenta::class, DistributionChatgptController::class],
        ['claude', \App\Models\ClaudeCuenta::class, DistributionClaudeController::class]] as [$service, $modelClass, $controllerClass]) {
        $test("hallazgo {$service}: formularios de ciclos anteriores no liberan ventas nuevas; repetir el mismo intento es idempotente", static function () use ($pdo, $modelClass, $controllerClass, $service, $create, $today, $token, $login, $redirect, $admin): void {
            $model = new $modelClass($pdo); $id = $create($model); $model->selectDistribution($id);
            $model->sell($id, 1, $today, $token());
            $controller = $login($controllerClass, $admin);
            $first = $controller->formToken('ciclo', $id); $stale = $controller->formToken('ciclo', $id);
            $_POST = ['token_operacion' => $first, 'sesiones_cerradas' => '1']; $redirect(static fn() => $controller->startCycle($id));
            $sale = $model->sell($id, 1, $today, $token());
            foreach ([$stale, $first] as $formToken) {
                $_POST = ['token_operacion' => $formToken, 'sesiones_cerradas' => '1']; $redirect(static fn() => $controller->startCycle($id));
                clEq($model->find($id)['ocupados'], 1, 'Ningún formulario anterior puede liberar la entrega nueva.');
                clEq($model->sale($id, $sale)['liberada_at'], null, 'La entrega nueva debe seguir ocupada.');
                clEq(count($model->cycles($id)), 1, 'No se debe crear otro ciclo.');
            }
            $missing = $controller->formToken('ciclo', $id);
            unset($_SESSION[$service . '_operaciones'][$missing]['ciclo']);
            $_POST = ['token_operacion' => $missing, 'sesiones_cerradas' => '1']; $redirect(static fn() => $controller->startCycle($id));
            clEq(count($model->cycles($id)), 1, 'Un formulario previo a la actualización debe pedir recarga.');
            $fresh = $controller->formToken('ciclo', $id);
            $_POST = ['token_operacion' => $fresh, 'sesiones_cerradas' => '1']; $redirect(static fn() => $controller->startCycle($id));
            clEq($model->find($id)['ocupados'], 0, 'Un formulario nuevo sí debe permitir el siguiente ciclo.');
        });
        $test("hallazgo {$service}: dos órdenes de desactivar o activar conservan el estado solicitado", static function () use ($pdo, $modelClass, $controllerClass, $create, $login, $redirect, $admin): void {
            $model = new $modelClass($pdo); $id = $create($model); $controller = $login($controllerClass, $admin);
            foreach (['0', '1'] as $target) {
                $forms = [$controller->formToken('estado', $id), $controller->formToken('estado', $id)];
                foreach ($forms as $form) {
                    $_POST = ['token_operacion' => $form, 'activo' => $target]; $redirect(static fn() => $controller->toggleActive($id));
                    clEq((int) $model->find($id)['activo'], (int) $target, 'La segunda orden no debe invertir el estado.');
                }
            }
            $_POST = ['token_operacion' => $controller->formToken('estado', $id)]; $redirect(static fn() => $controller->toggleActive($id));
            clEq((int) $model->find($id)['activo'], 1, 'La ausencia de estado explícito no debe modificar la cuenta.');
        });
        $test("hallazgo {$service}: ventas liberadas o vencidas no entregan contraseñas actuales ni activan copia automática", static function () use ($pdo, $modelClass, $controllerClass, $service, $create, $today, $token, $login, $page, $operator): void {
            $model = new $modelClass($pdo); $id = $create($model); $model->selectDistribution($id);
            $sale = $model->sell($id, 1, $today, $token());
            $model->startCycle($id, true, 'fixture', $token(), $model->currentCycleId($id));
            $row = $model->find($id);
            $model->update($id, ['etiqueta' => $row['etiqueta'], 'correo' => $row['correo'], 'password_cuenta' => 'fixture-ciclo-nuevo']);
            $controller = $login($controllerClass, $operator); $_GET = ['venta' => $sale];
            $data = $page(static fn() => $controller->show($id));
            clEq($data['saleTexts'][$sale], '', 'El historial no puede entregar la contraseña del ciclo siguiente.');
            clEq($data['lastSaleId'], null, 'La venta retirada no puede activar la copia automática.');
            clCheck(str_contains($data['saleCopyReasons'][$sale], 'ciclo anterior'), 'Debe explicar que la entrega es de otro ciclo.');
            $_POST = ['token_operacion' => $controller->formToken('entregar', $id), 'dispositivos' => '1'];
            try { $controller->deliver($id); } catch (DistributionJson $response) { $delivery = $response; }
            clEq($delivery->status, 200, 'Una entrega nueva válida debe seguir funcionando.');
            $currentSale = $delivery->data['saleId']; $retry = $_POST;
            $_GET = ['venta' => $currentSale];
            $model->setActive($id, false);
            clEq($page(static fn() => $controller->show($id))['saleTexts'][$currentSale], '', 'Una cuenta desactivada tampoco entrega credenciales.');
            $model->setActive($id, true); $model->setFreePlan($id, true);
            clEq($page(static fn() => $controller->show($id))['saleTexts'][$currentSale], '', 'Una cuenta Gratis tampoco entrega credenciales.');
            $model->setFreePlan($id, false); $model->selectDistribution($create($model));
            clCheck($page(static fn() => $controller->show($id))['saleTexts'][$currentSale] !== '', 'Una entrega vigente puede volver a copiarse cuando la cuenta queda en reserva.');
            clEq(count($model->sales($id)), 2, 'Volver a copiar no registra otra venta.');
            $model->selectDistribution($id);
            $pdo->prepare('UPDATE ' . $service . '_ventas SET fecha_vencimiento = :hoy WHERE id = :id')->execute(['hoy' => $today, 'id' => $currentSale]);
            $_GET = ['venta' => $currentSale]; $data = $page(static fn() => $controller->show($id));
            clEq($data['saleTexts'][$currentSale], '', 'Una venta que vence hoy ya no puede copiarse.');
            clEq($page(static fn() => $controller->distribution())['lastDelivery'], null, 'La distribución debe omitir la última entrega vencida sin fallar.');
            $_POST = $retry;
            try { $controller->deliver($id); } catch (DistributionJson $response) { $expired = $response; }
            clEq($expired->status, 422, 'Reintentar una entrega vencida no devuelve credenciales.');
            clEq($model->find($id)['ocupados'], 1, 'Reintentar no debe registrar otra venta.');
            clEq(count($model->sales($id)), 2, 'El historial debe conservar ambas ventas.');
            $_GET = [];
        });
        $test("hallazgo {$service}: rutas generales y modelos no reactivan ni modifican asignaciones heredadas", static function () use ($pdo, $modelClass, $service, $create, $today, $token, $login, $redirect, $operator): void {
            $model = new $modelClass($pdo); $id = $create($model, 1); $model->selectDistribution($id);
            $pdo->exec("INSERT INTO modalidades (nombre_modalidad, dispositivos) VALUES ('Legacy hallazgo', 1)"); $mode = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO suscripciones (modalidad_id, cuenta_principal_id, flag_no_renovo, fecha_inicio, fecha_vencimiento) VALUES (:mode, :id, 0, :inicio, :fin)')
                ->execute(['mode' => $mode, 'id' => $id, 'inicio' => $today, 'fin' => date('Y-m-d', strtotime('+1 month'))]);
            $legacy = (int) $pdo->lastInsertId(); $model->startCycle($id, true, 'fixture', $token(), $model->currentCycleId($id));
            $model->sell($id, 1, $today, $token()); $before = $pdo->query('SELECT * FROM suscripciones WHERE id = ' . $legacy)->fetch();
            $_POST = ['fecha_vencimiento' => date('Y-m-d', strtotime('+1 month')), 'meses' => '1'];
            foreach ([[LegacySubscriptionsHarness::class, ['edit', 'update', 'updateDueDate', 'destroy']], [LegacyDashboardHarness::class, ['renovar', 'noRenovo', 'whatsapp']]] as [$class, $methods]) {
                $controller = $login($class, $operator);
                foreach ($methods as $method) clEq($redirect(static fn() => $controller->$method($legacy)), '/' . $service . '/' . $id, 'La ruta general debe dirigir a la cuenta compartida.');
            }
            $generic = new \App\Models\Suscripcion($pdo);
            foreach ([static fn() => $generic->update($legacy, []), static fn() => $generic->updateDueDate($legacy, $today),
                static fn() => $generic->renovar($legacy, 1), static fn() => $generic->delete($legacy),
                static fn() => $generic->markNoRenovo($legacy), static fn() => $generic->markWhatsappContact($legacy, 'MENOS_2'),
                static fn() => $generic->create(['cuenta_principal_id' => $id])] as $mutation) {
                clReject($mutation, 'El modelo general no debe modificar la asignación compartida.');
            }
            clEq($pdo->query('SELECT * FROM suscripciones WHERE id = ' . $legacy)->fetch(), $before, 'El registro heredado debe permanecer intacto.');
            clEq($model->find($id)['ocupados'], 1, 'El operador no puede exceder el único cupo.');
        });
    }
    $test('hallazgo fechas: solo la primera revisión de una cuenta pendiente y sin pagos puede configurar vigencia', static function () use ($pdo, $create, $today, $login, $page, $admin): void {
        $model = new \App\Models\ChatgptCuenta($pdo);
        $id = $model->importPendingEmails([bin2hex(random_bytes(8)) . '@example.invalid'])['ids'][0];
        $controller = $login(DistributionChatgptController::class, $admin);
        clEq($page(static fn() => $controller->show($id))['canReviewPeriod'], true, 'La cuenta importada pendiente debe mostrar el formulario.');
        $model->configureExistingPeriod($id, 'PAGO', $today); $before = $model->find($id);
        clReject(static fn() => $model->configureExistingPeriod($id, 'GRACIA', $today), 'Una segunda revisión no puede extender fechas.');
        clEq($model->find($id), $before, 'La revisión rechazada debe conservar las fechas.');
        $paid = $create($model, 5, (new DateTimeImmutable('today'))->modify('-6 weeks')->format('Y-m-d'));
        $payments = $model->payments($paid); $before = $model->find($paid);
        foreach (['PAGO', 'GRACIA'] as $origin) clReject(static fn() => $model->configureExistingPeriod($paid, $origin, $today), 'No puede ampliar una cuenta en gracia que tiene pagos.');
        clEq($model->find($paid), $before, 'El intento no puede ampliar el período.');
        clEq($model->payments($paid), $payments, 'El historial contable permanece intacto.');
        clEq($page(static fn() => $controller->show($paid))['canReviewPeriod'], false, 'La cuenta con pagos no debe ofrecer la revisión.');
    });
    $test('regresión general: editar fechas de suscripciones sin cuenta compartida conserva su funcionamiento', static function () use ($pdo, $today): void {
        $pdo->prepare("INSERT INTO suscripciones (flag_no_renovo, estado, fecha_inicio, fecha_vencimiento) VALUES (1, 'VENCIDO', :inicio, :fin)")
            ->execute(['inicio' => $today, 'fin' => $today]); $id = (int) $pdo->lastInsertId();
        $generic = new \App\Models\Suscripcion($pdo); $future = date('Y-m-d', strtotime('+1 month'));
        clEq($generic->updateDueDate($id, $future), true, 'Debe seguir admitiendo fechas para suscripciones generales.');
        clEq($generic->find($id)['fecha_vencimiento'], $future, 'Debe guardar la fecha solicitada.');
        clEq((int) $generic->find($id)['flag_no_renovo'], 0, 'Debe conservar la reactivación normal de los otros servicios.');
    });
    $_POST = []; $_GET = []; unset($_SESSION['auth']);
}
