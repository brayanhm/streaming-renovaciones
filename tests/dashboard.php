<?php
declare(strict_types=1);

// Regresión del listado completo de vigentes y su orden; no usa ni modifica la BD.
require_once __DIR__ . '/../app/config/config.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/controllers/DashboardController.php';

$controller = (new ReflectionClass(App\Controllers\DashboardController::class))->newInstanceWithoutConstructor();
$sort = new ReflectionMethod($controller, 'sortRowsForMessaging');
$sort->setAccessible(true);
$sortRows = static function (array $rows) use ($controller, $sort): array {
    $sort->invokeArgs($controller, [&$rows]);
    return $rows;
};
$current = new ReflectionMethod($controller, 'nonExpiredRows');
$current->setAccessible(true);
$counts = new ReflectionMethod($controller, 'buildDashboardCounts');
$counts->setAccessible(true);
$passed = 0;
$failed = 0;
$test = static function (string $name, callable $run) use (&$passed, &$failed): void {
    try {
        $run();
        $passed++;
        echo 'OK   ' . $name . PHP_EOL;
    } catch (Throwable $error) {
        $failed++;
        echo 'FAIL ' . $name . ': ' . $error->getMessage() . PHP_EOL;
    }
};
$eq = static function ($actual, $expected): void {
    if ($actual !== $expected) {
        throw new RuntimeException('Esperado ' . json_encode($expected) . ', obtenido ' . json_encode($actual));
    }
};

$test('se muestran todos los vencimientos futuros debajo de los próximos avisos', static function () use ($controller, $current, $counts, $sortRows, $eq): void {
    $rows = [];
    for ($id = 1; $id <= 87; $id++) {
        $rows[] = ['id' => $id, 'estado' => 'RECUP', 'fecha_vencimiento' => '2026-04-18'];
    }
    $rows[] = ['id' => 88, 'estado' => 'CONTACTAR_2D', 'fecha_vencimiento' => '2026-10-22'];
    $rows[] = ['id' => 89, 'estado' => 'CONTACTAR_2D', 'fecha_vencimiento' => '2026-10-21'];
    $rows[] = ['id' => 90, 'estado' => 'CONTACTAR_2D', 'fecha_vencimiento' => '2026-10-21'];
    $rows[] = ['id' => 91, 'estado' => 'ACTIVO', 'fecha_vencimiento' => '2026-10-24'];
    $rows[] = ['id' => 92, 'estado' => 'ESPERA', 'fecha_vencimiento' => '2026-10-26'];
    $rows[] = ['id' => 93, 'estado' => 'ACTIVO', 'fecha_vencimiento' => '2027-12-12'];
    $currentRows = $current->invoke($controller, $sortRows($rows));
    $eq(array_column($currentRows, 'id'), [90, 89, 88, 91, 92, 93]);
    $eq(count($currentRows), $counts->invoke($controller, $rows)['TODOS']);
    $eq(count($rows), 93);
    $eq($counts->invoke($controller, $rows)['VENCIDO'], 87);
});

$test('hoy y los próximos días aparecen en orden correlativo', static function () use ($controller, $current, $sortRows, $eq): void {
    $rows = $current->invoke($controller, $sortRows([
        ['id' => 1, 'estado' => 'RECUP', 'fecha_vencimiento' => '2026-04-18'],
        ['id' => 2, 'estado' => 'CONTACTAR_2D', 'fecha_vencimiento' => '2026-10-21'],
        ['id' => 3, 'estado' => 'REENVIAR_1D', 'fecha_vencimiento' => '2026-10-20'],
        ['id' => 4, 'estado' => 'CONTACTAR_2D', 'fecha_vencimiento' => '2026-10-23'],
    ]));
    $eq(array_column($rows, 'fecha_vencimiento'), ['2026-10-20', '2026-10-21', '2026-10-23']);
});

$test('Vigentes se ordena por fecha aunque los estados sean distintos', static function () use ($sortRows, $eq): void {
    $rows = $sortRows([
        ['id' => 1, 'estado' => 'ACTIVO', 'fecha_vencimiento' => '2026-10-24'],
        ['id' => 2, 'estado' => 'CONTACTAR_2D', 'fecha_vencimiento' => '2026-10-22'],
        ['id' => 3, 'estado' => 'ESPERA', 'fecha_vencimiento' => '2026-10-21'],
    ]);
    $eq(array_column($rows, 'id'), [3, 2, 1]);
});

$test('el listado excluye vencidas, recuperaciones y cuentas que no renovaron', static function () use ($controller, $current, $sortRows, $eq): void {
    $eq($current->invoke($controller, [
        ['id' => 1, 'estado' => 'RECUP', 'fecha_vencimiento' => '2026-04-18'],
        ['id' => 2, 'estado' => 'VENCIDO', 'fecha_vencimiento' => '2026-10-19'],
        ['id' => 3, 'estado' => 'ACTIVO', 'fecha_vencimiento' => '2026-11-01', 'flag_no_renovo' => 1],
    ]), []);
    $eq($sortRows([]), []);
});

$test('todas las páginas conservan la secuencia de fechas y contienen todas las vigentes', static function () use ($controller, $current, $sortRows, $eq): void {
    $today = new DateTimeImmutable('2026-10-20');
    $rows = [['id' => 100, 'estado' => 'RECUP', 'fecha_vencimiento' => '2026-04-18']];
    for ($day = 94; $day >= 0; $day--) {
        $rows[] = ['id' => $day + 1, 'estado' => $day === 0 ? 'REENVIAR_1D' : ($day <= 3 ? 'CONTACTAR_2D' : 'ACTIVO'),
            'fecha_vencimiento' => $today->modify('+' . $day . ' days')->format('Y-m-d')];
    }
    $rows = $current->invoke($controller, $sortRows($rows));
    $pages = array_chunk($rows, 30);
    $eq(array_map('count', $pages), [30, 30, 30, 5]);
    $eq(array_column(array_merge(...$pages), 'id'), range(1, 95));
});

echo "Resultado: {$passed} correctas, {$failed} fallidas." . PHP_EOL;
exit($failed === 0 ? 0 : 1);
