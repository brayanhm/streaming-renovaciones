<?php
declare(strict_types=1);

/** Selección desde el listado; ejecutado en la base aislada de claude.php. */
function runDistributionSelectionTests(PDO $pdo, callable $test): void
{
    foreach ([\App\Models\ChatgptCuenta::class, \App\Models\ClaudeCuenta::class] as $class) {
        $service = $class === \App\Models\ChatgptCuenta::class ? 'chatgpt' : 'claude';
        $controllerClass = $service === 'chatgpt' ? DistributionChatgptController::class : DistributionClaudeController::class;
        $test("selección {$service}: el índice permite distribuir y vuelve a la misma búsqueda", static function () use ($pdo, $class, $service, $controllerClass): void {
            $pdo->prepare('INSERT INTO usuarios (username, rol) VALUES (:usuario, :rol)')
                ->execute(['usuario' => 'seleccion-' . $service . '-fixture', 'rol' => 'admin']);
            $userId = (int) $pdo->lastInsertId();
            $_SESSION['auth'] = ['id' => $userId, 'username' => 'seleccion-fixture', 'rol' => 'admin'];
            $model = new $class($pdo);
            $create = static fn(string $label): int => $model->create(['etiqueta' => 'Selección fixture ' . $label,
                'correo' => bin2hex(random_bytes(8)) . '@example.invalid', 'password_cuenta' => 'fixture-only',
                'fecha_pago' => date('Y-m-d'), 'monto_bs' => '200.00']);
            $current = $create('actual');
            $next = $create('siguiente');
            $model->selectDistribution($current);
            $model->sell($current, 1, date('Y-m-d'), clToken());
            $sales = $model->sales($current);
            $payments = $model->payments($next);
            $controller = new $controllerClass();
            $_GET = ['q' => 'Selección fixture'];
            try { $controller->index(); } catch (DistributionPage $page) { $data = $page->data; }
            clCheck(isset($data), 'Debe devolver el índice.');
            clCheck($data['indexSelectionToken'] !== '', 'El administrador necesita el token del listado.');
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
            clCheck(str_contains($html, 'value="listado"') && str_contains($html, '/' . $service . '/' . $next . '/distribuir'), 'La fila debe incluir un formulario de selección.');
            $_POST = ['origen' => 'listado', 'token_operacion' => $data['indexSelectionToken'], 'q' => 'Selección fixture'];
            try { $controller->selectDistribution($next); } catch (DistributionRedirect $result) { $redirect = $result->getMessage(); }
            clEq($redirect ?? '', '/' . $service . '?q=' . urlencode('Selección fixture'), 'Debe volver al índice conservando la búsqueda.');
            clEq((int) $model->currentDistribution()['id'], $next, 'Debe seleccionar la cuenta de la fila.');
            clEq($model->find($current)['distribucion_actual'], false, 'La cuenta anterior debe salir de distribución.');
            clEq($model->sales($current), $sales, 'Seleccionar desde el índice conserva las ventas.');
            clEq($model->payments($next), $payments, 'Seleccionar una cuenta vigente no registra otro pago.');
            clCheck($model->find($next)['inicio_distribucion'] !== null, 'Debe registrar el inicio de distribución.');
            $pdo->exec("UPDATE usuarios SET rol = 'operador' WHERE id = " . $userId);
            $_POST = ['origen' => 'listado', 'token_operacion' => $controller->formToken('distribuir_listado', 0)];
            try { $controller->selectDistribution($current); } catch (DistributionRedirect $result) {}
            clEq((int) $model->currentDistribution()['id'], $next, 'Un operador no puede cambiar la selección del listado.');
            $pdo->exec("UPDATE usuarios SET rol = 'admin' WHERE id = " . $userId);
            $_POST = ['origen' => 'listado', 'token_operacion' => $controller->formToken('distribuir', $current)];
            try { $controller->selectDistribution($current); } catch (DistributionRedirect $result) {}
            clEq((int) $model->currentDistribution()['id'], $next, 'Un token del detalle no debe suplantar el token del listado.');
            $_POST = []; $_GET = []; unset($_SESSION['auth']);
        });
    }
}
