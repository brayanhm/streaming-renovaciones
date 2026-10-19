<?php
declare(strict_types=1);

/** Invocado por claude.php después de distribucion.php, siempre en su base efímera. */
function runDeliveryTests(PDO $pdo, callable $test): void
{
    $pdo->exec('CREATE TABLE auditoria (id INT AUTO_INCREMENT PRIMARY KEY, usuario VARCHAR(60), accion VARCHAR(60), detalle VARCHAR(255), ip VARCHAR(45)) ENGINE=InnoDB');
    $pdo->exec("INSERT INTO usuarios (username, rol) VALUES ('entrega-operador-fixture', 'operador')");
    $operator = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO usuarios (username, rol) VALUES ('entrega-admin-fixture', 'admin')");
    $admin = (int) $pdo->lastInsertId();
    $create = static function (\App\Models\CuentaCompartida $model, int $capacity = 3): int {
        return $model->create(['etiqueta' => 'Entrega fixture', 'correo' => bin2hex(random_bytes(8)) . '@example.invalid',
            'password_cuenta' => 'entrega-password-fixture', 'capacidad' => $capacity, 'fecha_pago' => date('Y-m-d'), 'monto_bs' => '200.00']);
    };
    $login = static function (string $class, int $userId): \App\Controllers\CuentasCompartidasController {
        $_SESSION['auth'] = ['id' => $userId, 'username' => 'entrega-fixture', 'rol' => 'admin'];
        return new $class();
    };
    $deliver = static function ($controller, int $id, string $devices, string $token): DistributionJson {
        $_POST = ['dispositivos' => $devices, 'token_operacion' => $token];
        try { $controller->deliver($id); } catch (DistributionJson $response) { return $response; }
        throw new RuntimeException('No devolvió una respuesta JSON.');
    };
    $page = static function ($controller): array {
        try { $controller->distribution(); } catch (DistributionPage $page) { return $page->data; }
        throw new RuntimeException('No devolvió una pantalla de distribución.');
    };

    foreach ([['CHATGPT', \App\Models\ChatgptCuenta::class, DistributionChatgptController::class], ['CLAUDE', \App\Models\ClaudeCuenta::class, DistributionClaudeController::class]] as [$service, $modelClass, $controllerClass]) {
        $test("entrega {$service}: copia registra la cantidad correcta y repetir token o recargar no duplica cupos", static function () use ($pdo, $service, $modelClass, $controllerClass, $create, $login, $deliver, $page, $operator): void {
            $model = new $modelClass($pdo);
            if ($service === 'CLAUDE') $model->saveTemplates(['entrega_1' => '']);
            if ($service === 'CHATGPT') {
                $defaults = require APP_PATH . '/config/chatgpt_templates.php';
                $model->saveTemplates(array_map(static fn(string $text): string => str_replace('*', '', $text), $defaults));
            }
            $id = $create($model, 5);
            $model->selectDistribution($id);
            $controller = $login($controllerClass, $operator);
            $token = $controller->formToken('entregar', $id);
            $first = $deliver($controller, $id, '1', $token);
            clEq($first->status, 200, 'La entrega debe registrarse.');
            clEq($first->data['occupied'], 1, 'Una entrega consume un cupo.');
            clCheck(str_contains($first->data['text'], $model->find($id)['correo']), 'El texto debe incluir el correo actual.');
            clCheck(str_contains($first->data['text'], 'entrega-password-fixture'), 'El texto debe incluir la contraseña actual.');
            clCheck(str_contains($first->data['text'], '📧 Correo: ' . $model->find($id)['correo'])
                && str_contains($first->data['text'], '🔑 Contraseña: entrega-password-fixture'), 'Las credenciales deben copiarse sin asteriscos de formato.');
            if ($service === 'CHATGPT') clCheck(str_contains($first->data['text'], '*LEER OBLIGATORIO*'), 'La plantilla estándar instalada sin formato debe actualizar sus negritas.');
            if ($service === 'CLAUDE') {
                clCheck(str_contains($first->data['text'], 'Claude compartido'), 'Una plantilla vacía debe usar la entrega predeterminada.');
                clCheck(!str_contains($first->data['text'], 'Bs Bs'), 'El precio debe llevar una sola etiqueta de moneda.');
            }
            $again = $deliver($controller, $id, '1', $token);
            clEq($again->data['saleId'], $first->data['saleId'], 'Repetir el token recupera la misma entrega.');
            clEq($model->find($id)['ocupados'], 1, 'El reintento conserva la ocupación.');
            $data = $page($controller);
            clEq($data['lastDelivery']['saleId'], $first->data['saleId'], 'La recarga recupera el texto de la última entrega.');
            clEq($data['lastDelivery']['text'], $first->data['text'], 'Volver a copiar debe conservar el formato de WhatsApp.');
            clEq($model->find($id)['ocupados'], 1, 'Mostrar la última entrega no consume cupos.');
            $nextDevices = $service === 'CHATGPT' ? '2' : '1';
            $next = $deliver($controller, $id, $nextDevices, $again->data['nextToken']);
            clEq($next->status, 200, 'El siguiente cliente requiere una nueva entrega.');
            clEq($next->data['occupied'], $service === 'CHATGPT' ? 3 : 2, 'ChatGPT dos dispositivos consume dos; Claude uno consume uno.');
            clEq(count($model->sales($id)), 2, 'Debe conservar dos ventas en el historial.');
            $lastSale = $model->sale($id, $next->data['saleId']);
            clEq((int) $lastSale['dispositivos'], (int) $nextDevices, 'La cantidad se registra en la venta.');
            $model->startCycle($id, true, 'fixture', bin2hex(random_bytes(32)), $model->currentCycleId($id));
            clEq($page($controller)['lastDelivery'], null, 'El texto del ciclo anterior ya no debe aparecer como última entrega.');
            clEq($model->find($id)['ocupados'], 0, 'La pantalla no recupera los cupos reiniciados.');
        });

        $test("entrega {$service}: superar la referencia permite entregas, pero Gratis, reservas y tokens inválidos las bloquean", static function () use ($pdo, $modelClass, $controllerClass, $create, $login, $deliver, $operator, $admin): void {
            $model = new $modelClass($pdo);
            $id = $create($model, 1);
            $reserve = $create($model);
            $model->selectDistribution($id);
            $controller = $login($controllerClass, $operator);
            $first = $deliver($controller, $id, '1', $controller->formToken('entregar', $id));
            clEq($first->status, 200, 'El último cupo debe venderse.');
            clEq($first->data['state'], 'En distribución', 'La última entrega no debe quitar la cuenta de distribución.');
            clEq($deliver($controller, $id, '1', $first->data['nextToken'])->status, 200, 'El operador puede seguir entregando sobre la referencia.');
            clEq($model->find($id)['ocupados'], 2, 'La entrega adicional debe contarse.');
            clEq($deliver($controller, $id, '1', $controller->formToken('vender', $id))->status, 422, 'Un token de otra acción no permite entregar.');
            $controller = $login($controllerClass, $admin);
            clEq($deliver($controller, $reserve, '1', $controller->formToken('entregar', $reserve))->status, 422, 'El admin tampoco puede entregar una reserva.');
            $additional = $deliver($controller, $id, '1', $controller->formToken('entregar', $id));
            clEq($additional->status, 200, 'El administrador también puede seguir entregando en la seleccionada.');
            $model->setFreePlan($id, true);
            clEq($deliver($controller, $id, '1', $additional->data['nextToken'])->status, 422, 'Gratis bloquea todas las entregas.');
            clEq($model->find($id)['ocupados'], 3, 'Gratis no registra una venta nueva.');
        });

        $test("entrega {$service}: vendedor continúa tras el llenado y cambiar cuenta retira la anterior inmediatamente", static function () use ($pdo, $service, $modelClass, $controllerClass, $create, $login, $deliver, $page, $operator): void {
            $model = new $modelClass($pdo);
            $reference = $modelClass::CAPACIDAD;
            $id = $create($model, $reference);
            $model->selectDistribution($id);
            $model->adjustOccupation($id, $reference - 1, 0, 'Preparar último cupo', 'fixture', clToken());
            $controller = $login($controllerClass, $operator);
            $last = $deliver($controller, $id, '1', $controller->formToken('entregar', $id));
            clEq($last->status, 200, 'El operador debe alcanzar la referencia.');
            clEq($last->data['state'], 'En distribución', 'La cuenta llena continúa en distribución.');
            $history = $model->distributionHistory($id);
            $data = $page($controller);
            $render = static function (array $data): string {
                extract($data);
                ob_start();
                try {
                    require APP_PATH . '/views/layouts/header.php';
                    require APP_PATH . '/views/cuentas_compartidas/entrega.php';
                    require APP_PATH . '/views/layouts/footer.php';
                    return (string) ob_get_contents();
                }
                finally { ob_end_clean(); }
            };
            $html = $render($data);
            preg_match_all('/<button[^>]*name="dispositivos"[^>]*>/', $html, $buttons);
            clEq(count($buttons[0]), $service === 'CHATGPT' ? 2 : 1, 'Debe ofrecer las cantidades de este servicio.');
            foreach ($buttons[0] as $button) clCheck(!str_contains($button, 'disabled'), 'La pantalla del vendedor no debe bloquear entregas al llenarse.');
            $additional = $deliver($controller, $id, $service === 'CHATGPT' ? '2' : '1', $last->data['nextToken']);
            clEq($additional->status, 200, 'El operador puede seguir entregando sobre la referencia.');
            clEq($additional->data['occupied'], $reference + ($service === 'CHATGPT' ? 2 : 1), 'Debe contar todos los dispositivos adicionales.');
            clEq($model->distributionHistory($id), $history, 'Las entregas adicionales conservan la primera fecha de llenado.');
            $previewDir = (string) getenv('DISTRIBUTION_PREVIEW_DIR');
            if ($previewDir !== '' && is_dir($previewDir)) {
                file_put_contents($previewDir . '/' . strtolower($service) . '-distribucion-continua.html', $render($page($controller)));
            }
            $next = $create($model, $reference);
            $model->setActive($next, true);
            clEq((int) $model->currentDistribution()['id'], $id, 'Crear o activar una cuenta no cambia la seleccionada.');
            $model->selectDistribution($next);
            clEq((int) $page($controller)['cuenta']['id'], $next, 'La pantalla de distribución debe usar inmediatamente la nueva cuenta.');
            clEq($model->find($id)['distribucion_actual'], false, 'La cuenta anterior debe dejar de estar seleccionada.');
            clEq($model->find($id)['estado_distribucion'], 'Vendida', 'La anterior llena queda vendida fuera de distribución.');
            clEq(count(array_filter($model->all(), static fn(array $row): bool => $row['distribucion_actual'])), 1, 'Solo puede existir una cuenta en distribución por servicio.');
            $before = count($model->sales($id));
            clEq($deliver($controller, $id, '1', $additional->data['nextToken'])->status, 422, 'Un formulario abierto de la anterior no puede registrar otra venta.');
            clEq(count($model->sales($id)), $before, 'El intento anterior debe dejar intactas las ventas.');
            clEq($deliver($controller, $next, '1', $controller->formToken('entregar', $next))->status, 200, 'Las siguientes entregas se registran en la nueva cuenta.');
        });

        $test("entrega {$service}: pantalla sin selección es accesible al operador y no crea ventas", static function () use ($pdo, $service, $modelClass, $controllerClass, $login, $page, $operator): void {
            $pdo->prepare('UPDATE cuentas_distribucion SET cuenta_principal_id = NULL WHERE servicio = :servicio')->execute(['servicio' => $service]);
            $model = new $modelClass($pdo);
            $before = $model->all();
            $data = $page($login($controllerClass, $operator));
            clEq($data['cuenta'], null, 'Debe mostrar que falta seleccionar una cuenta.');
            clEq($data['canAdmin'], false, 'La pantalla admite operadores sin permisos administrativos.');
            clEq($model->all(), $before, 'Abrir la pantalla no crea ventas.');
        });
    }

    $test('entrega: cantidades inválidas se rechazan y los mensajes personalizados se conservan', static function () use ($pdo, $create, $login, $deliver, $operator): void {
        $chatgpt = new \App\Models\ChatgptCuenta($pdo);
        $claude = new \App\Models\ClaudeCuenta($pdo);
        $id = $create($chatgpt, 1);
        $chatgpt->selectDistribution($id);
        $controller = $login(DistributionChatgptController::class, $operator);
        foreach (['0', '3', '2abc'] as $invalid) {
            clEq($deliver($controller, $id, $invalid, $controller->formToken('entregar', $id))->status, 422, 'No debe registrar una cantidad inválida.');
        }
        clEq($chatgpt->find($id)['ocupados'], 0, 'Las cantidades rechazadas no generan ocupación.');
        $chatgpt->saveTemplates(['entrega_1' => 'Entrega personalizada {CORREO} / {PASSWORD} / {DISPOSITIVOS}']);
        $result = $deliver($controller, $id, '1', $controller->formToken('entregar', $id));
        clCheck(str_starts_with($result->data['text'], 'Entrega personalizada '), 'Debe conservar el texto que ya estaba configurado.');
        $claudeId = $create($claude);
        $claude->selectDistribution($claudeId);
        $controller = $login(DistributionClaudeController::class, $operator);
        clEq($deliver($controller, $claudeId, '2', $controller->formToken('entregar', $claudeId))->status, 422, 'Claude permite solamente un dispositivo.');
        clEq($claude->find($claudeId)['ocupados'], 0, 'Claude no debe aceptar dos dispositivos.');
    });
    $_POST = [];
    unset($_SESSION['auth']);
}
