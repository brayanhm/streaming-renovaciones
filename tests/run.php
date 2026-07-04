<?php
declare(strict_types=1);

/**
 * Runner de pruebas minimalista (sin dependencias). Ejecutar con:
 *   php tests/run.php
 * Las pruebas son deterministas y no modifican la base (usan funciones puras
 * y lecturas; cualquier escritura va en transaccion con rollback).
 */

require_once __DIR__ . '/../app/config/config.php';
require_once APP_PATH . '/config/db.php';

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $parts = explode('\\', substr($class, 4));
    $parts[0] = strtolower($parts[0]);
    $file = APP_PATH . '/' . implode('/', $parts) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use App\Controllers\ImportacionesController;

$PASS = 0;
$FAIL = 0;

function test(string $name, callable $fn): void
{
    global $PASS, $FAIL;
    try {
        $fn();
        $PASS++;
        echo "  OK   {$name}\n";
    } catch (\Throwable $e) {
        $FAIL++;
        echo "  FAIL {$name} -> {$e->getMessage()}\n";
    }
}

function check(bool $cond, string $msg = 'condicion falsa'): void
{
    if (!$cond) {
        throw new \Exception($msg);
    }
}

function eq($actual, $expected, string $msg = ''): void
{
    if ($actual !== $expected) {
        throw new \Exception(($msg !== '' ? $msg . ' ' : '') . '(esperado ' . var_export($expected, true) . ', obtenido ' . var_export($actual, true) . ')');
    }
}

$refPriv = static function (string $method, array $args) {
    $m = new ReflectionMethod(ImportacionesController::class, $method);
    $m->setAccessible(true);
    return $m->invoke(null, ...$args);
};

echo "== Cifrado de secretos ==\n";
test('round-trip', function () {
    $enc = encrypt_secret('miClave#123');
    check(str_starts_with($enc, 'enc:'), 'deberia llevar prefijo enc:');
    eq(decrypt_secret($enc), 'miClave#123');
});
test('vacio => vacio', function () {
    eq(encrypt_secret(''), '');
    eq(decrypt_secret(''), '');
});
test('valor en claro se devuelve tal cual', function () {
    eq(decrypt_secret('textoplano'), 'textoplano');
});

echo "\n== Parseo CSV (cuenta completa) ==\n";
test('BOM + cabeceras + fecha', function () {
    $csv = "\xEF\xBB\xBFDatos del usuario final;\nNombre de usuario;Fecha de finalización\nUSR1;2026-08-03 20:46:11\n";
    $r = ImportacionesController::parseCsv($csv);
    eq(count($r['valid']), 1);
    eq($r['valid']['usr1']['fin'], '2026-08-03');
});
test('fila sin fecha se omite', function () {
    $csv = "Nombre de usuario;Fecha de finalización\nSINFECHA;\n";
    $r = ImportacionesController::parseCsv($csv);
    eq(count($r['valid']), 0);
    eq(count($r['skipped']), 1);
});
test('duplicado conserva fecha mayor', function () {
    $csv = "Nombre de usuario;Fecha de finalización\nU;2026-01-01\nU;2027-05-05\n";
    $r = ImportacionesController::parseCsv($csv);
    eq($r['valid']['u']['fin'], '2027-05-05');
    eq($r['duplicates'], 1);
});
test('str_getcsv respeta comillas', function () {
    $csv = "Nombre de usuario;Fecha de finalización\n\"U;raro\";2026-09-01\n";
    $r = ImportacionesController::parseCsv($csv);
    check(isset($r['valid']['u;raro']), 'el usuario con ; entre comillas debe quedar entero');
});

echo "\n== Categorizacion (cuenta completa) ==\n";
test('update / create / ignore', function () {
    $existing = ['u1' => ['id' => 1, 'usuario' => 'U1', 'fecha_vencimiento' => '2026-08-01']];
    $valid = [
        'u1' => ['user' => 'U1', 'fin' => '2026-09-01'], // update
        'u2' => ['user' => 'U2', 'fin' => '2026-10-01'], // create
    ];
    $plan = ImportacionesController::categorize($valid, $existing);
    eq(count($plan['update']), 1);
    eq(count($plan['create']), 1);
    eq($plan['update'][0]['delta'], 31);
});
test('misma fecha => ignore', function () {
    $existing = ['u1' => ['id' => 1, 'usuario' => 'U1', 'fecha_vencimiento' => '2026-08-01']];
    $valid = ['u1' => ['user' => 'U1', 'fin' => '2026-08-01']];
    $plan = ImportacionesController::categorize($valid, $existing);
    eq(count($plan['update']), 0);
    eq(count($plan['ignore']), 1);
});

echo "\n== Parseo + categorizacion (por dispositivos) ==\n";
test('captura password y credito', function () {
    $csv = "Nombre de usuario;Contrasena;Credito;Fecha de inicio;Fecha de finalizacion;smartTv;Estado\n"
        . "U;clave1;3;2026-06-01 00:00:00;2026-09-01 00:00:00;;Valido\n";
    $r = ImportacionesController::parseDisposCsv($csv);
    eq($r['valid'][0]['password'], 'clave1');
    eq($r['valid'][0]['credito'], 3);
    eq($r['valid'][0]['fin'], '2026-09-01');
});
test('duplicado exacto se omite, mismo usuario otra fecha se crea', function () use ($refPriv) {
    $mods = [
        ['id' => 12, 'duracion_meses' => 1, 'precio' => '15.00', 'costo' => '6.00'],
        ['id' => 13, 'duracion_meses' => 3, 'precio' => '40.00', 'costo' => '18.00'],
    ];
    $valid = [
        ['user' => 'U', 'password' => 'x', 'credito' => 3, 'inicio' => '2026-06-01', 'fin' => '2026-09-01'],
        ['user' => 'U', 'password' => 'x', 'credito' => 3, 'inicio' => '2027-01-01', 'fin' => '2027-04-01'],
    ];
    $existing = ['u|2026-06-01|2026-09-01' => true];
    $plan = ImportacionesController::categorizeDispos($valid, $existing, $mods);
    eq(count($plan['create']), 1);
    eq(count($plan['skipExisting']), 1);
    eq($plan['create'][0]['fin'], '2027-04-01');
});
test('inicio > fin se corrige derivando del vencimiento', function () {
    $mods = [['id' => 13, 'duracion_meses' => 3, 'precio' => '40.00', 'costo' => '18.00']];
    $valid = [['user' => 'U', 'password' => '', 'credito' => 3, 'inicio' => '2027-12-31', 'fin' => '2026-09-01']];
    $plan = ImportacionesController::categorizeDispos($valid, [], $mods);
    check($plan['create'][0]['inicio'] <= $plan['create'][0]['fin'], 'inicio no debe superar el vencimiento');
});
test('pickModalidadByCredito elige por cercania', function () use ($refPriv) {
    $mods = [
        ['id' => 12, 'duracion_meses' => 1, 'precio' => '15', 'costo' => '6'],
        ['id' => 13, 'duracion_meses' => 3, 'precio' => '40', 'costo' => '18'],
        ['id' => 14, 'duracion_meses' => 7, 'precio' => '90', 'costo' => '36'],
    ];
    eq((int) $refPriv('pickModalidadByCredito', [$mods, 1])['duracion_meses'], 1);
    eq((int) $refPriv('pickModalidadByCredito', [$mods, 6])['duracion_meses'], 7);
    eq((int) $refPriv('pickModalidadByCredito', [$mods, 3])['duracion_meses'], 3);
});

echo "\n== Fechas ==\n";
test('parseDate acepta Y-m-d y d/m/Y, rechaza invalidas', function () use ($refPriv) {
    eq($refPriv('parseDate', ['2026-08-03 20:46:11']), '2026-08-03');
    eq($refPriv('parseDate', ['15/08/2026']), '2026-08-15');
    eq($refPriv('parseDate', ['2026-13-40']), null);
    eq($refPriv('parseDate', ['']), null);
});

echo "\n== Cartera (lectura BD) ==\n";
test('estadisticas devuelve claves esperadas', function () {
    $s = new \App\Models\Suscripcion();
    $stats = $s->estadisticas();
    foreach (['total', 'activos', 'no_renovaron', 'vencen_7', 'vencen_30'] as $k) {
        check(array_key_exists($k, $stats), "falta clave {$k}");
    }
});

echo "\n" . ($FAIL === 0 ? '=== TODOS OK ===' : '=== HAY FALLOS ===') . " {$PASS} pass, {$FAIL} fail\n";
exit($FAIL === 0 ? 0 : 1);
