<?php
declare(strict_types=1);
require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/models/BaseModel.php';
require APP_PATH . '/models/ReporteFinanciero.php';

$passed = 0; $failed = 0;
$test = static function (string $name, callable $run) use (&$passed, &$failed): void {
    try { $run(); $passed++; echo 'OK   ' . $name . PHP_EOL; }
    catch (Throwable $error) { $failed++; echo 'FAIL ' . $name . ': ' . $error->getMessage() . PHP_EOL; }
};
$eq = static function ($actual, $expected): void { if ($actual !== $expected) throw new RuntimeException(json_encode([$actual,$expected],JSON_UNESCAPED_UNICODE)); };
$amount = static function ($actual, $expected): void { if (abs((float) $actual - (float) $expected) > 0.00001) throw new RuntimeException('Importe incorrecto.'); };
$name = 'ghost_ui_test_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
if (!preg_match('/^ghost_ui_test_[0-9]{14}_[a-f0-9]{8}$/D',$name)) throw new RuntimeException('Nombre de base inválido.');
$server = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$created = false;
try {
    $server->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4'); $created = true;
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . $name . ';charset=utf8mb4',DB_USER,DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $pdo->exec('CREATE TABLE plataformas (id INT PRIMARY KEY, nombre VARCHAR(100))');
    $pdo->exec('CREATE TABLE suscripciones (id INT PRIMARY KEY, plataforma_id INT)');
    $pdo->exec('CREATE TABLE movimientos (id INT PRIMARY KEY, suscripcion_id INT, plataforma_id INT, plataforma_nombre VARCHAR(100), fecha DATETIME, monto DECIMAL(12,2), costo DECIMAL(12,2))');
    foreach (['chatgpt','claude'] as $service) {
        $pdo->exec('CREATE TABLE ' . $service . '_ventas (id INT PRIMARY KEY, fecha_venta DATE, monto_bs DECIMAL(12,2), liberada_at DATETIME NULL)');
        $pdo->exec('CREATE TABLE ' . $service . '_pagos (id INT PRIMARY KEY, fecha_pago DATE, monto_bs DECIMAL(12,2))');
    }
    $pdo->exec("INSERT INTO plataformas VALUES (1,'FlujoTV')");
    $pdo->exec("INSERT INTO suscripciones VALUES (1,1)");
    $pdo->exec("INSERT INTO movimientos VALUES (1,1,1,'FlujoTV','2026-10-15 10:00:00',150.10,70.05),
        (2,NULL,1,'FlujoTV','2025-11-01 00:00:00',80,20),(3,NULL,1,'FlujoTV','2025-10-31 23:59:59',1000,500),
        (4,NULL,1,'FlujoTV','2026-11-01 00:00:00',999,99)");
    $pdo->exec("INSERT INTO chatgpt_ventas VALUES (1,'2026-10-14',55,NULL),(2,'2026-10-13',30,'2026-10-14 10:00:00')");
    $pdo->exec("INSERT INTO chatgpt_pagos VALUES (1,'2026-09-15',100.25),(2,'2026-10-15',50.10)");
    $pdo->exec("INSERT INTO claude_ventas VALUES (1,'2026-09-20',85,NULL)");
    $pdo->exec("INSERT INTO claude_pagos VALUES (1,'2026-09-01',100.50),(2,'2026-10-01',50)");
    $report = new App\Models\ReporteFinanciero($pdo); $today = new DateTimeImmutable('2026-10-15');
    $test('reporte: incluye renovaciones y ambos servicios de IA sin duplicar los pagos como ventas',static function () use ($report,$today,$eq,$amount): void {
        $r=$report->resumen('todos',12,$today); $eq($r['totalPeriodo']['renovaciones'],5);
        $amount($r['totalPeriodo']['total_monto'],400.10); $amount($r['totalPeriodo']['total_costo'],390.90); $amount($r['totalPeriodo']['total_utilidad'],9.20);
    });
    $test('reporte: filtrar ChatGPT conserva ventas liberadas y separa ingreso y costo por fecha',static function () use ($report,$today,$eq,$amount): void {
        $r=$report->resumen('chatgpt',12,$today); $eq($r['totalPeriodo']['renovaciones'],2); $eq(count($r['porPlataforma']),1);
        $amount($r['totalPeriodo']['total_monto'],85); $amount($r['totalPeriodo']['total_costo'],150.35);
        $amount($r['mesActual']['total_utilidad'],34.90); $eq($r['porMes'][0]['mes'],9); $amount($r['porMes'][0]['total_utilidad'],-100.25);
    });
    $test('reporte: Claude con un pago y ninguna venta este mes muestra saldo negativo y cero ventas',static function () use ($report,$today,$eq,$amount): void {
        $r=$report->resumen('claude',12,$today); $eq($r['mesActual']['renovaciones'],0); $amount($r['mesActual']['total_utilidad'],-50);
        $amount($r['totalPeriodo']['total_utilidad'],-65.50);
    });
    $test('reporte: límites exactos del período excluyen meses anteriores y el mes futuro',static function () use ($report,$today,$eq,$amount): void {
        $r=$report->resumen('streaming',12,$today); $eq($r['desde'],'2025-11-01'); $eq($r['hasta'],'2026-10-31');
        $eq($r['totalPeriodo']['renovaciones'],2); $amount($r['totalPeriodo']['total_monto'],230.10);
        $short=$report->resumen('todos',6,$today); $eq($short['desde'],'2026-05-01'); $amount($short['totalPeriodo']['total_monto'],320.10);
    });
    $test('reporte: período vacío devuelve ceros y no inventa meses ni operaciones',static function () use ($report,$eq): void {
        $r=$report->resumen('todos',12,new DateTimeImmutable('2031-01-15'));
        $eq($r['porMes'],[]); $eq($r['porPlataforma'],[]); $eq($r['mesActual']['renovaciones'],0); $eq((float)$r['totalPeriodo']['total_monto'],0.0);
    });
    $test('reporte: servicio o intervalo no permitido se rechaza antes de consultar',static function () use ($report,$today): void {
        foreach ([["chatgpt' OR 1=1",12],['todos',120]] as [$service,$months]) {
            try { $report->resumen($service,$months,$today); } catch (InvalidArgumentException $e) { continue; }
            throw new RuntimeException('Se aceptó un filtro inválido.');
        }
    });
    $test('reporte: leer y filtrar conserva todos los registros originales',static function () use ($report,$today,$pdo,$eq): void {
        foreach (array_keys(App\Models\ReporteFinanciero::SERVICIOS) as $service) $report->resumen($service,24,$today);
        $eq((int)$pdo->query('SELECT COUNT(*) FROM movimientos')->fetchColumn(),4);
        $eq((int)$pdo->query('SELECT COUNT(*) FROM chatgpt_ventas')->fetchColumn(),2);
        $eq((int)$pdo->query('SELECT COUNT(*) FROM claude_pagos')->fetchColumn(),2);
    });
    $test('formularios: recupera nombre, correo y notas únicamente para su acción y excluye credenciales',static function () use ($eq): void {
        $_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['REQUEST_URI']='/chatgpt/1/actualizar'; $_SERVER['REQUEST_METHOD']='POST';
        set_old(['etiqueta'=>'Edición de prueba','correo'=>'fixture@example.invalid','notas'=>'Conservar notas','capacidad'=>'0',
            'password_cuenta'=>'fixture-secreto','new_password'=>'fixture-nuevo','_csrf'=>'fixture-csrf','token_operacion'=>'fixture-token']);
        $eq(form_value('/chatgpt/1/actualizar','etiqueta'),'Edición de prueba');
        $eq(form_value('/claude/1/actualizar','etiqueta','Guardado'),'Guardado');
        foreach (['password_cuenta','new_password','_csrf','token_operacion'] as $key) $eq(old($key),'');
        set_field_errors(['capacidad'=>'Capacidad inválida.']); flash('danger','Revisa la capacidad.');
        $eq($_SESSION['_form_feedback']['fields'],['capacidad'=>'Capacidad inválida.']);
        $eq($_SESSION['_form_feedback']['message'],'Revisa la capacidad.');
        clear_old(); $eq(old('etiqueta'),''); $eq(isset($_SESSION['_form_feedback']),false);
    });
    $test('presentación: estados legibles y fechas locales conservan límites y fechas inválidas',static function () use ($eq): void {
        $eq(status_label('REENVIAR_1D'),'Vence hoy'); $eq(status_label('CONTACTAR_2D'),'Por vencer');
        $eq(display_date('2026-10-15'),'15/10/2026'); $eq(display_date('2026-02-30'),'—'); $eq(display_date(null),'—');
    });
} finally {
    if ($created) $server->exec('DROP DATABASE `' . $name . '`');
}
echo "Resultado: {$passed} correctas, {$failed} fallidas.\n";
exit($failed===0?0:1);
