<?php
declare(strict_types=1);

/** Ejecutado por claude.php en su base temporal; cubre ambos servicios. */
function runDistributionDateTests(PDO $pdo, callable $test): void
{
    $today = date('Y-m-d');
    $create = static function ($model, int $capacity = 3) use ($today): int {
        return $model->create(['etiqueta' => 'Fechas fixture', 'correo' => bin2hex(random_bytes(8)) . '@example.invalid',
            'password_cuenta' => 'fixture-only', 'capacidad' => $capacity, 'fecha_pago' => $today, 'monto_bs' => '200.00']);
    };
    $edit = static function ($model, int $id, int $capacity): void {
        $row = $model->find($id);
        $model->update($id, ['etiqueta' => $row['etiqueta'], 'correo' => $row['correo'], 'capacidad' => $capacity]);
    };
    foreach ([\App\Models\ChatgptCuenta::class, \App\Models\ClaudeCuenta::class] as $class) {
        $label = $class === \App\Models\ChatgptCuenta::class ? 'ChatGPT' : 'Claude';
        $test("fechas {$label}: selección registra el inicio en hora local y conserva el ciclo al volver", static function () use ($pdo, $class, $create, $today): void {
            $model = new $class($pdo);
            $id = $create($model);
            $other = $create($model);
            $model->registerPayment($id, $today, '210.00', clToken());
            clEq($model->distributionHistory($id), [], 'Crear y pagar no deben iniciar la distribución.');
            clEq($model->find($id)['inicio_distribucion'], null, 'El inicio queda sin registrar hasta la selección.');
            $before = date('Y-m-d H:i:s');
            $model->selectDistribution($id);
            $start = $model->find($id)['inicio_distribucion'];
            clCheck($start >= $before && $start <= date('Y-m-d H:i:s'), 'Debe usar la fecha y hora de PHP en Bolivia.');
            clEq($model->find($id)['cupos_completados_at'], null, 'Seleccionar no llena cupos.');
            $pdo->prepare('UPDATE cuentas_distribucion_historial SET inicio_distribucion = :fecha WHERE cuenta_principal_id = :id')
                ->execute(['fecha' => $today . ' 00:00:00', 'id' => $id]);
            $history = $model->distributionHistory($id);
            $model->selectDistribution($id);
            $model->selectDistribution($other);
            $model->selectDistribution($id);
            clEq($model->distributionHistory($id), $history, 'Repetir o volver a seleccionar conserva el primer inicio.');
            $all = array_column($model->all(), null, 'id');
            clEq($all[$id]['inicio_distribucion'], $model->currentDistribution()['inicio_distribucion'], 'Listado y distribución deben mostrar el mismo inicio.');
        });

        $test("fechas {$label}: solo el primer llenado se conserva tras reintentos, ajustes y nuevas ventas", static function () use ($pdo, $class, $create, $today): void {
            $model = new $class($pdo);
            $id = $create($model, 3);
            $model->selectDistribution($id);
            $model->sell($id, 1, $today, clToken());
            $model->sell($id, 1, $today, clToken());
            clEq($model->find($id)['cupos_completados_at'], null, 'La cuenta parcial no tiene fecha de llenado.');
            $devices = $class === \App\Models\ChatgptCuenta::class ? 2 : 1;
            $token = clToken();
            $saleId = $model->sell($id, $devices, $today, $token);
            $full = $model->find($id);
            clCheck($full['cupos_completados_at'] !== null, 'Debe registrar al alcanzar o superar la capacidad.');
            clEq((int) $full['capacidad_al_completar'], 3, 'Debe conservar la capacidad al llenarse.');
            clEq((int) $full['ocupados_al_completar'], 2 + $devices, 'ChatGPT debe contar los dos dispositivos de una venta.');
            clEq($model->sell($id, $devices, $today, $token), $saleId, 'El historial no debe cambiar el ID original de venta.');
            $pdo->prepare('UPDATE cuentas_distribucion_historial SET cupos_completados_at = :fecha WHERE cuenta_principal_id = :id')
                ->execute(['fecha' => $today . ' 00:00:01', 'id' => $id]);
            $history = $model->distributionHistory($id);
            $model->adjustOccupation($id, 2, 2 + $devices, 'Liberar un cupo', 'fixture', clToken());
            $model->sell($id, 1, $today, clToken());
            $model->sell($id, 1, $today, clToken());
            $model->registerPayment($id, $today, '205.00', clToken());
            $model->setFreePlan($id, true);
            $model->setFreePlan($id, false);
            $model->setActive($id, false);
            $model->setActive($id, true);
            clEq($model->distributionHistory($id), $history, 'Reabrir, volver a llenar o pagar conserva solo el primer llenado.');
        });

        $test("fechas {$label}: nuevo ciclo conserva el anterior y los reintentos no duplican fechas", static function () use ($pdo, $class, $create, $today): void {
            $model = new $class($pdo);
            $id = $create($model, 1);
            $model->selectDistribution($id);
            $model->sell($id, 1, $today, clToken());
            $previous = $model->distributionHistory($id)[0];
            $token = clToken();
            $model->startCycle($id, true, 'fixture', $token, 0);
            $account = $model->find($id);
            clCheck($account['inicio_distribucion'] !== null, 'Iniciar ciclo también selecciona y registra inicio.');
            clEq($account['cupos_completados_at'], null, 'El nuevo ciclo empieza sin llenado.');
            $history = $model->distributionHistory($id);
            clEq(count($history), 2, 'Debe conservar dos ciclos.');
            clEq($history[1], $previous, 'El registro anterior queda intacto.');
            clEq((int) $history[0]['numero_ciclo'], 2, 'La numeración es por cuenta, no el ID global.');
            $model->sell($id, 1, $today, clToken());
            $filledHistory = $model->distributionHistory($id);
            $model->startCycle($id, true, 'fixture', $token, 0);
            clEq($model->distributionHistory($id), $filledHistory, 'Reintentar un ciclo no reemplaza inicio ni llenado.');
            clEq($model->find($id)['ocupados'], 1, 'Reintentar no libera las ventas nuevas.');
            clEq((int) $filledHistory[0]['ciclo_id'], $account['ciclo_actual'], 'El detalle debe leer el ciclo actual.');
            $fresh = $create($model);
            $model->startCycle($fresh, true, 'fixture', clToken(), 0);
            clCheck($model->find($fresh)['inicio_distribucion'] !== null, 'Un ciclo puede ser la primera selección.');
        });

        $test("fechas {$label}: ajustes y reducción de capacidad registran la transición a llena una sola vez", static function () use ($pdo, $class, $create, $edit): void {
            $model = new $class($pdo);
            $id = $create($model, 5);
            $model->selectDistribution($id);
            $token = clToken();
            $adjustment = $model->adjustOccupation($id, 6, 0, 'Conteo', 'fixture', $token);
            clCheck($model->find($id)['cupos_completados_at'] !== null, 'Un ajuste que llena debe registrar fecha.');
            $again = $model->adjustOccupation($id, 6, 0, 'Conteo', 'fixture', $token);
            clEq((int) $again['id'], (int) $adjustment['id'], 'El historial no debe cambiar el ID del ajuste.');
            $history = $model->distributionHistory($id);
            $edit($model, $id, 8);
            $edit($model, $id, 4);
            clEq($model->distributionHistory($id), $history, 'Cambiar la capacidad conserva el primer llenado y su capacidad original.');
            $other = $create($model, 5);
            $model->selectDistribution($other);
            $model->adjustOccupation($other, 3, 0, 'Conteo parcial', 'fixture', clToken());
            clEq($model->find($other)['cupos_completados_at'], null, 'Un ajuste parcial no llena.');
            $edit($model, $other, 3);
            clCheck($model->find($other)['cupos_completados_at'] !== null, 'Reducir capacidad hasta la ocupación debe registrar llenado.');
            clEq((int) $model->find($other)['capacidad_al_completar'], 3, 'Debe guardar la nueva capacidad.');
        });

        $test("fechas {$label}: migración y lecturas no inventan fechas de cuentas existentes", static function () use ($pdo, $class, $create, $edit, $today): void {
            $model = new $class($pdo);
            $id = $create($model, 1);
            $model->selectDistribution($id);
            $model->sell($id, 1, $today, clToken());
            $model->startCycle($id, true, 'fixture', clToken(), 0);
            $model->sell($id, 1, $today, clToken());
            // Simula una cuenta ya seleccionada y llena antes de instalar la mejora.
            $pdo->prepare('DELETE FROM cuentas_distribucion_historial WHERE cuenta_principal_id = :id')->execute(['id' => $id]);
            run_distribucion_schema_migrations($pdo);
            run_distribucion_schema_migrations($pdo);
            $model->all();
            $model->currentDistribution();
            $model->selectDistribution($id);
            $edit($model, $id, 1);
            $model->sell($id, 1, $today, clToken());
            clEq($model->find($id)['inicio_distribucion'], null, 'Una selección repetida no reconstruye el inicio antiguo.');
            clEq($model->find($id)['cupos_completados_at'], null, 'Estar llena no permite deducir el llenado anterior.');
            clEq($model->distributionHistory($id), [], 'Migración, lecturas y ventas excedidas no inventan fechas.');
            $model->adjustOccupation($id, 0, 2, 'Corrección', 'fixture', clToken());
            $model->sell($id, 1, $today, clToken());
            clEq($model->find($id)['inicio_distribucion'], null, 'Un llenado nuevo no debe inventar el inicio desconocido.');
            clCheck($model->find($id)['cupos_completados_at'] !== null, 'Sí debe guardar un llenado ocurrido después de instalar.');
            $history = $model->distributionHistory($id);
            run_distribucion_schema_migrations($pdo);
            clEq($model->distributionHistory($id), $history, 'Repetir migración conserva las fechas nuevas.');
            $otherClass = $class === \App\Models\ChatgptCuenta::class ? \App\Models\ClaudeCuenta::class : \App\Models\ChatgptCuenta::class;
            clEq((new $otherClass($pdo))->distributionHistory($id), [], 'El historial debe estar separado por servicio.');
        });

        $test("fechas {$label}: entrega devuelve las fechas y las tres pantallas muestran el historial correcto", static function () use ($pdo, $class, $create): void {
            $service = $class === \App\Models\ChatgptCuenta::class ? 'chatgpt' : 'claude';
            $controllerClass = $service === 'chatgpt' ? DistributionChatgptController::class : DistributionClaudeController::class;
            $pdo->prepare('INSERT INTO usuarios (username, rol) VALUES (:usuario, :rol)')
                ->execute(['usuario' => 'fechas-' . $service . '-fixture', 'rol' => 'admin']);
            $_SESSION['auth'] = ['id' => (int) $pdo->lastInsertId(), 'username' => 'fechas-fixture', 'rol' => 'admin'];
            $model = new $class($pdo);
            $id = $create($model, 1);
            $unknown = $create($model);
            $model->selectDistribution($id);
            $model->sell($id, 1, date('Y-m-d'), clToken());
            $model->startCycle($id, true, 'fixture', clToken(), 0);
            $controller = new $controllerClass();
            $_POST = ['dispositivos' => '1', 'token_operacion' => $controller->formToken('entregar', $id)];
            try { $controller->deliver($id); } catch (DistributionJson $result) {
                clEq($result->status, 200, 'La entrega debe confirmarse.');
                $account = $model->find($id);
                clEq($result->data['distributionStartedAt'], display_datetime($account['inicio_distribucion']), 'La respuesta debe incluir el inicio visible.');
                clEq($result->data['distributionCompletedAt'], display_datetime($account['cupos_completados_at']), 'El último cupo devuelve la fecha para actualizar sin recargar.');
                clCheck($account['cupos_completados_at'] !== null, 'La entrega debe registrar llenado.');
            }
            clCheck(isset($result), 'Debe devolver JSON.');
            $capture = static function (callable $action): array {
                try { $action(); } catch (DistributionPage $page) { return $page->data; }
                throw new RuntimeException('No devolvió una vista.');
            };
            $render = static function (string $view, array $data): string {
                extract($data);
                ob_start();
                try {
                    require APP_PATH . '/views/layouts/header.php';
                    require APP_PATH . '/views/' . $view . '.php';
                    require APP_PATH . '/views/layouts/footer.php';
                    return (string) ob_get_contents();
                } finally { ob_end_clean(); }
            };
            $_GET = [];
            $index = $capture(static fn() => $controller->index());
            // El listado ahora está paginado; la prueba de representación usa ambas cuentas explícitamente.
            $index['rows'] = [$model->find($id), $model->find($unknown)];
            $show = $capture(static fn() => $controller->show($id));
            clEq(count($show['historialDistribucion']), 2, 'El detalle debe recibir ambos ciclos.');
            foreach ([$service . '/index' => $index, $service . '/show' => $show,
                'cuentas_compartidas/entrega' => $capture(static fn() => $controller->distribution())] as $view => $data) {
                $html = $render($view, $data);
                clCheck(str_contains($html, 'Inicio de distribución:') && str_contains($html, 'Primer llenado:'), 'Cada pantalla debe mostrar ambas fechas.');
                clCheck(str_contains($html, display_datetime($account['cupos_completados_at'])), 'Cada pantalla debe mostrar el primer llenado actual.');
                if ($view === $service . '/index') clCheck(str_contains($html, 'Sin registrar'), 'La lista debe reconocer las fechas desconocidas.');
                if ($view === $service . '/show') clCheck(str_contains($html, 'Historial de distribución'), 'El detalle debe mostrar el historial.');
                $previewDir = (string) getenv('DISTRIBUTION_PREVIEW_DIR');
                if ($previewDir !== '' && is_dir($previewDir)) {
                    file_put_contents($previewDir . '/' . $service . '-' . basename($view) . '.html', $html);
                }
            }
            unset($_SESSION['auth']);
            $_POST = [];
        });
    }
}
