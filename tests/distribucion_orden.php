<?php
declare(strict_types=1);

/** Orden de los listados de ambos servicios, dentro de la base temporal de claude.php. */
function runDistributionOrderTests(PDO $pdo, callable $test): void
{
    foreach ([\App\Models\ChatgptCuenta::class, \App\Models\ClaudeCuenta::class] as $class) {
        $service = $class === \App\Models\ChatgptCuenta::class ? 'chatgpt' : 'claude';
        $test("orden {$service}: actual, reservas listas por vencimiento, completas y bloqueadas", static function () use ($pdo, $class): void {
            $model = new $class($pdo);
            $prefix = 'Orden-' . bin2hex(random_bytes(5)) . ' ';
            $create = static function (string $label, int $daysAgo) use ($model, $prefix): int {
                return $model->create(['etiqueta' => $prefix . $label, 'correo' => bin2hex(random_bytes(8)) . '@example.invalid',
                    'password_cuenta' => 'orden-fixture-only', 'capacidad' => 3,
                    'fecha_pago' => (new DateTimeImmutable('today'))->modify('-' . $daysAgo . ' days')->format('Y-m-d'), 'monto_bs' => '200.00']);
            };
            $current = $create('Actual', 0);
            $later = $create('Posterior', 1);
            $soonZ = $create('Z cercana', 5);
            $soonA = $create('A cercana', 5);
            $model->adjustOccupation($soonA, 1, 0, 'Parcial', 'fixture', clToken());
            $sold = $create('Completa', 10);
            $model->adjustOccupation($sold, 3, 0, 'Completa', 'fixture', clToken());
            $free = $create('Gratis', 11);
            $model->setFreePlan($free, true);
            $expired = $create('Vencida', 90);
            $noPassword = $create('Sin contraseña', 12);
            $pdo->exec('UPDATE cuentas_principales SET password_cuenta = NULL WHERE id = ' . $noPassword);
            $badPassword = $create('Cifrado ilegible', 13);
            $pdo->exec("UPDATE cuentas_principales SET password_cuenta = 'enc:invalido' WHERE id = " . $badPassword);
            $pending = $create('Fechas pendientes', 14);
            $pdo->exec('UPDATE cuentas_principales SET fecha_inicio = NULL, fecha_vencimiento = NULL, fin_gracia = NULL WHERE id = ' . $pending);
            $inactive = $create('Inactiva', 15);
            $model->setActive($inactive, false);
            $model->selectDistribution($current);
            $model->adjustOccupation($current, 4, 0, 'La actual ya superó la referencia', 'fixture', clToken());
            $history = $model->distributionHistory($current);
            $rows = $model->all($prefix);
            clEq(array_map('intval', array_column(array_slice($rows, 0, 5), 'id')),
                [$current, $soonA, $soonZ, $later, $sold], 'La disponibilidad debe tener prioridad sobre el vencimiento de las bloqueadas.');
            clEq((int) $rows[count($rows) - 1]['id'], $inactive, 'La cuenta inactiva debe quedar al final.');
            $ready = array_filter($rows, static fn(array $row): bool => $row['lista_para_distribucion']);
            clEq(array_map('intval', array_column(array_values($ready), 'id')), [$soonA, $soonZ, $later], 'Solo las reservas listas y bajo la referencia son siguientes.');
            clEq(array_column($model->all($prefix), 'id'), array_column($rows, 'id'), 'Repetir la consulta debe mantener el mismo orden.');
            clEq((int) $model->currentDistribution()['id'], $current, 'Ordenar no cambia la selección, aunque la actual esté llena.');
            clEq($model->distributionHistory($current), $history, 'Ordenar no altera las fechas de distribución.');
            $pdo->prepare('UPDATE cuentas_distribucion SET cuenta_principal_id = NULL WHERE servicio = :servicio')
                ->execute(['servicio' => $model->find($current)['servicio']]);
            $withoutCurrent = $model->all($prefix);
            clEq((int) $withoutCurrent[0]['id'], $soonA, 'Sin selección, la primera debe ser la siguiente reserva lista.');
            clEq($model->currentDistribution(), null, 'Consultar la cola no selecciona automáticamente una cuenta.');
            $model->selectDistribution($current);
        });

        $test("orden {$service}: el índice identifica la siguiente y la búsqueda no inventa turnos globales", static function () use ($pdo, $class, $service): void {
            $pdo->prepare('INSERT INTO usuarios (username, rol) VALUES (:usuario, :rol)')
                ->execute(['usuario' => 'orden-' . $service . '-fixture', 'rol' => 'operador']);
            $_SESSION['auth'] = ['id' => (int) $pdo->lastInsertId(), 'username' => 'orden-fixture', 'rol' => 'operador'];
            $controllerClass = $service === 'chatgpt' ? DistributionChatgptController::class : DistributionClaudeController::class;
            $controller = new $controllerClass();
            $capture = static function () use ($controller): array {
                try { $controller->index(); } catch (DistributionPage $page) { return $page->data; }
                throw new RuntimeException('No devolvió el listado.');
            };
            $render = static function (array $data) use ($service): string {
                extract($data);
                ob_start();
                try {
                    require APP_PATH . '/views/layouts/header.php';
                    require APP_PATH . '/views/' . $service . '/index.php';
                    require APP_PATH . '/views/layouts/footer.php';
                    return (string) ob_get_contents();
                } finally { ob_end_clean(); }
            };
            $_GET = [];
            $data = $capture();
            $position = 0;
            $next = null;
            foreach ($data['rows'] as $row) {
                if ($row['lista_para_distribucion']) {
                    clEq($row['turno_distribucion'], ++$position, 'La cola debe numerarse de forma consecutiva.');
                    $next = $next ?? $row;
                } else clCheck(!isset($row['turno_distribucion']), 'Las completas, bloqueadas y actuales no tienen turno.');
            }
            clCheck($next !== null, 'El fixture debe ofrecer una siguiente cuenta.');
            $html = $render($data);
            clEq(substr_count($html, 'Siguiente para distribución'), 1, 'Solo una cuenta debe identificarse como siguiente.');
            clCheck(str_contains($html, 'En cola · 2'), 'El índice debe mostrar las posiciones posteriores.');
            $previewDir = (string) getenv('DISTRIBUTION_PREVIEW_DIR');
            if ($previewDir !== '' && is_dir($previewDir)) {
                $preview = $data;
                $preview['rows'] = array_slice($preview['rows'], 0, 4);
                file_put_contents($previewDir . '/' . $service . '-orden.html', $render($preview));
            }
            $_GET = ['q' => $next['correo']];
            $filtered = $capture();
            clEq(count($filtered['rows']), 1, 'La búsqueda debe seguir filtrando por correo.');
            clCheck(!isset($filtered['rows'][0]['turno_distribucion']), 'Un filtro no debe renumerar la cola global.');
            $filteredHtml = $render($filtered);
            clCheck(!str_contains($filteredHtml, 'Siguiente para distribución') && str_contains($filteredHtml, 'Lista para distribuir'), 'La búsqueda muestra disponibilidad sin prometer un turno global.');
            $_GET = [];
            unset($_SESSION['auth']);
        });
    }
}
