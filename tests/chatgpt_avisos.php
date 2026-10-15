<?php
declare(strict_types=1);

// Usa exclusivamente una base nueva y efímera, nunca la base instalada.
$name = 'ghost_alerts_test_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
if (!preg_match('/^ghost_alerts_test_[0-9]{14}_[a-f0-9]{8}$/D', $name)) exit(2);
foreach (['DB_NAME' => $name, 'APP_RUN_MIGRATIONS' => '0'] as $key => $value) {
    putenv($key . '=' . $value); $_ENV[$key] = $value; $_SERVER[$key] = $value;
}
require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/config/db.php';
require APP_PATH . '/config/migrations.php';
require APP_PATH . '/config/chatgpt_avisos_migrations.php';
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $parts = explode('\\', substr($class, 4)); $parts[0] = strtolower($parts[0]);
    $file = APP_PATH . '/' . implode('/', $parts) . '.php'; if (is_file($file)) require_once $file;
});
use App\Models\ChatgptAvisos;
use App\Models\ChatgptCuenta;
use App\Models\ClaudeCuenta;
final class AlertTestRedirect extends Exception {}
final class AlertController extends App\Controllers\ChatgptController
{
    protected function redirect(string $path): void { throw new AlertTestRedirect($path); }
    public function token(string $purpose = 'avisos'): string { return $this->issueToken($purpose, 0); }
}
$passed = 0; $failed = 0;
$test = static function (string $name, callable $run) use (&$passed, &$failed): void {
    try { $run(); $passed++; echo 'OK   ' . $name . PHP_EOL; }
    catch (Throwable $error) { $failed++; echo 'FAIL ' . $name . ': ' . $error->getMessage() . PHP_EOL; }
};
$eq = static function ($actual, $expected): void { if ($actual !== $expected) throw new RuntimeException(json_encode([$actual, $expected], JSON_UNESCAPED_UNICODE)); };
$server = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
try {
    $server->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4'); $created = true;
    $pdo = db();
    $pdo->exec('CREATE TABLE cuentas_principales (id INT PRIMARY KEY, servicio VARCHAR(20), etiqueta VARCHAR(100), correo VARCHAR(150),
        fecha_inicio DATE NULL, fecha_vencimiento DATE NULL, fin_gracia DATE NULL, plan_gratis INT DEFAULT 0,
        revision_vigencia_pendiente INT DEFAULT 0, activo INT DEFAULT 1)');
    $pdo->exec('CREATE TABLE usuarios (id INT PRIMARY KEY, username VARCHAR(50), rol VARCHAR(20), activo INT)');
    $pdo->exec("INSERT INTO usuarios VALUES (1, 'admin-fixture', 'admin', 1), (2, 'operador-fixture', 'operador', 1)");
    $pdo->exec('CREATE TABLE auditoria (id INT AUTO_INCREMENT PRIMARY KEY, usuario VARCHAR(60), accion VARCHAR(60), detalle VARCHAR(255), ip VARCHAR(45))');
    $model = new ChatgptAvisos($pdo); $today = new DateTimeImmutable('2026-10-15');
    $test('configuración inicial: lectura antes de migrar conserva los dos plazos de tres días', static fn() => $eq($model->config(), ChatgptAvisos::DEFAULTS));
    run_chatgpt_avisos_schema_migrations($pdo);
    $test('migración idempotente conserva la configuración del administrador', static function () use ($pdo, $model, $eq): void {
        $model->saveConfig(['aviso_fin_pagado' => '0', 'aviso_fin_gracia' => '7']);
        run_chatgpt_avisos_schema_migrations($pdo); run_chatgpt_avisos_schema_migrations($pdo);
        $eq($model->config(), ['aviso_fin_pagado' => 0, 'aviso_fin_gracia' => 7]);
    });
    $test('plazos inválidos rechazan el cambio completo', static function () use ($model, $eq): void {
        $before = $model->config();
        foreach ([-1, 61, '1.5', 'texto', [], null] as $value) {
            try { $model->saveConfig(['aviso_fin_pagado' => 4, 'aviso_fin_gracia' => $value]); }
            catch (App\Core\ValidationException $error) { $eq(isset($error->fields['aviso_fin_gracia']), true); $eq($model->config(), $before); continue; }
            throw new RuntimeException('Se aceptó un plazo inválido.');
        }
    });
    $test('gracia de la captura y marca Gratis tienen causas y contadores separados', static function () use ($today, $eq): void {
        $row = ['fecha_inicio' => '2026-09-11', 'fecha_vencimiento' => '2026-10-11', 'fin_gracia' => '2026-11-11'];
        $normal = ChatgptCuenta::period($row, $today);
        $eq($normal['estado_periodo'], 'GRACIA'); $eq($normal['estado_vigencia'], 'GRACIA'); $eq($normal['dias_gracia'], 27);
        $manual = ChatgptCuenta::period($row + ['plan_gratis' => 1], $today);
        $eq($manual['estado_periodo'], 'GRATIS'); $eq($manual['estado_vigencia'], 'GRACIA'); $eq($manual['origen_gratis'], 'MANUAL'); $eq($manual['dias_gracia'], 27);
        $expired = ChatgptCuenta::period($row, new DateTimeImmutable('2026-11-11'));
        $eq($expired['estado_periodo'], 'GRATIS'); $eq($expired['origen_gratis'], 'VENCIMIENTO');
        $eq(ClaudeCuenta::period($row, $today)['estado_periodo'], 'GRATIS');
    });
    $insert = static function (int $id, string $paid, array $overrides = []) use ($pdo): void {
        $date = new DateTimeImmutable($paid);
        $row = array_replace(['id' => $id, 'servicio' => 'CHATGPT', 'etiqueta' => 'Cuenta ' . $id, 'correo' => $id . '@example.invalid',
            'fecha_inicio' => shift_months_clamped($date, -1)->format('Y-m-d'), 'fecha_vencimiento' => $paid,
            'fin_gracia' => shift_months_clamped($date, 1)->format('Y-m-d'), 'plan_gratis' => 0, 'revision_vigencia_pendiente' => 0, 'activo' => 1], $overrides);
        $pdo->prepare('INSERT INTO cuentas_principales (' . implode(',', array_keys($row)) . ') VALUES (' . implode(',', array_fill(0, count($row), '?')) . ')')->execute(array_values($row));
    };
    foreach ([1 => '2026-10-19', 2 => '2026-10-18', 3 => '2026-10-15', 4 => '2026-10-16', 5 => '2026-09-15', 6 => '2026-09-18', 7 => '2026-09-19', 8 => '2026-09-14'] as $id => $paid) $insert($id, $paid);
    $insert(9, '2026-10-15', ['servicio' => 'CLAUDE']);
    $insert(10, '2026-10-15', ['activo' => 0]);
    $insert(11, '2026-10-15', ['revision_vigencia_pendiente' => 1]);
    $insert(12, '2026-10-11', ['plan_gratis' => 1]);
    $insert(13, '2026-11-01', ['fecha_inicio' => '2026-10-20']);
    $insert(14, '2026-10-18', ['fin_gracia' => null]);
    $model->saveConfig(ChatgptAvisos::DEFAULTS);
    $test('listas independientes se ordenan por la fecha próxima, no por selección ni por alta', static function () use ($model, $today, $eq): void {
        $result = $model->resumen($today);
        $eq(array_column($result['pagado'], 'id'), [3, 4, 2, 1]);
        $eq(array_column($result['gracia'], 'id'), [5, 6, 7, 12, 3, 4, 2, 1]);
    });
    $test('tres días incluyen el límite y el día de cierre en ambas listas', static function () use ($model, $today, $eq): void {
        $r = $model->resumen($today); $eq($r['urgentes_pagado'], 3); $eq($r['urgentes_gracia'], 2);
        $eq($r['pagado'][0]['dias'], 0); $eq($r['pagado'][0]['estado_vigencia'], 'GRACIA');
        $eq($r['gracia'][0]['dias'], 0); $eq($r['gracia'][0]['estado_periodo'], 'GRATIS');
    });
    $test('plazos independientes y cero días modifican solo el tipo de aviso elegido', static function () use ($model, $today, $eq): void {
        $model->saveConfig(['aviso_fin_pagado' => 0, 'aviso_fin_gracia' => 4]);
        $r = $model->resumen($today); $eq($r['urgentes_pagado'], 1); $eq($r['urgentes_gracia'], 3);
        $model->saveConfig(ChatgptAvisos::DEFAULTS);
    });
    $test('vencidas, inactivas, fechas incompletas y Claude se distinguen sin avisos falsos', static function () use ($model, $today, $eq): void {
        $r = $model->resumen($today); $eq(array_column($r['vencidas'], 'id'), [8]);
        $eq($r['inactivas'], 1); $eq($r['fechas_pendientes'], 3);
        $eq($r['vencidas'][0]['dias'], -1);
    });
    $test('consultar avisos conserva las cuentas, incluida la marca Gratis manual', static function () use ($pdo, $model, $today, $eq): void {
        $before = $pdo->query('SELECT * FROM cuentas_principales ORDER BY id')->fetchAll();
        $r = $model->resumen($today); $eq($r['gracia'][3]['estado_periodo'], 'GRATIS'); $eq($r['gracia'][3]['estado_vigencia'], 'GRACIA');
        $eq($pdo->query('SELECT * FROM cuentas_principales ORDER BY id')->fetchAll(), $before);
    });
    $test('cambio de mes y año conserva límites diarios de avisos', static function () use ($eq): void {
        $r = ChatgptCuenta::period(['fecha_inicio' => '2026-11-01', 'fecha_vencimiento' => '2026-12-01', 'fin_gracia' => '2027-01-01'], new DateTimeImmutable('2026-12-29'));
        $eq($r['estado_vigencia'], 'GRACIA'); $eq($r['dias_gracia'], 3);
    });
    $_SERVER['SCRIPT_NAME'] = '/index.php'; $_SERVER['REQUEST_URI'] = '/chatgpt/avisos'; $_SERVER['REQUEST_METHOD'] = 'POST';
    $test('administrador guarda los avisos con un comprobante válido', static function () use ($model, $eq): void {
        $_SESSION['auth'] = ['id' => 1, 'rol' => 'admin']; $controller = new AlertController();
        $_POST = ['token_operacion' => $controller->token(), 'aviso_fin_pagado' => '2', 'aviso_fin_gracia' => '6'];
        try { $controller->saveAlerts(); } catch (AlertTestRedirect $redirect) { $eq($redirect->getMessage(), '/chatgpt/vencimientos#configurar-avisos'); }
        $eq($model->config(), ['aviso_fin_pagado' => 2, 'aviso_fin_gracia' => 6]);
    });
    $test('un formulario rechazado conserva los plazos y señala el campo inválido', static function () use ($model, $eq): void {
        $before = $model->config(); $_SESSION['auth'] = ['id' => 1, 'rol' => 'admin']; $controller = new AlertController();
        $_POST = ['token_operacion' => $controller->token(), 'aviso_fin_pagado' => '61', 'aviso_fin_gracia' => '5'];
        try { $controller->saveAlerts(); } catch (AlertTestRedirect $redirect) {}
        $eq($model->config(), $before); $eq(old('aviso_fin_pagado'), '61'); $eq(old('aviso_fin_gracia'), '5');
        $eq(isset($_SESSION['_form_feedback']['fields']['aviso_fin_pagado']), true);
        clear_old(); consume_flash();
    });
    $test('operador no puede cambiar plazos aunque su sesión diga admin', static function () use ($model, $eq): void {
        $before = $model->config(); $_SESSION['auth'] = ['id' => 2, 'rol' => 'admin']; $controller = new AlertController();
        $_POST = ['token_operacion' => $controller->token(), 'aviso_fin_pagado' => '60', 'aviso_fin_gracia' => '60'];
        try { $controller->saveAlerts(); } catch (AlertTestRedirect $redirect) {}
        $eq($model->config(), $before); clear_old(); consume_flash();
    });
    $test('un comprobante de otra acción no cambia la configuración', static function () use ($model, $eq): void {
        $before = $model->config(); $_SESSION['auth'] = ['id' => 1, 'rol' => 'admin']; $controller = new AlertController();
        $_POST = ['token_operacion' => $controller->token('pagar'), 'aviso_fin_pagado' => '60', 'aviso_fin_gracia' => '60'];
        try { $controller->saveAlerts(); } catch (AlertTestRedirect $redirect) {}
        $eq($model->config(), $before);
    });
} finally {
    if ($created) $server->exec('DROP DATABASE `' . $name . '`');
}
echo "Resultado: {$passed} correctas, {$failed} fallidas.\n";
exit($failed === 0 ? 0 : 1);
