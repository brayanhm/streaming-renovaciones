<?php
declare(strict_types=1);

/**
 * Pruebas de Claude. Ejecutar: php tests/claude.php [--pure]
 * La integración crea y elimina SU PROPIA base ghost_claude_test_*.
 * CLAUDE_TEST_DB permite elegir el nombre (debe ser nuevo y tener ese prefijo).
 * No carga las migraciones generales ni lee/escribe la base de producción.
 */

$worker = in_array('--worker', $argv, true);
$pureOnly = in_array('--pure', $argv, true);
$testDatabase = $worker
    ? (string) getenv('DB_NAME')
    : ((string) getenv('CLAUDE_TEST_DB') ?: 'ghost_claude_test_' . date('YmdHis') . '_' . getmypid() . '_' . bin2hex(random_bytes(4)));
if (!preg_match('/^ghost_claude_test_[A-Za-z0-9_]{1,44}$/D', $testDatabase)) {
    fwrite(STDERR, "Nombre de base de pruebas inválido; requiere prefijo ghost_claude_test_.\n");
    exit(2);
}

foreach (['DB_NAME' => $testDatabase, 'APP_KEY' => 'claude-tests-only-key-never-for-production', 'APP_RUN_MIGRATIONS' => '0'] as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
require_once __DIR__ . '/../app/config/config.php';
// Los errores del runner se reportan aquí, sin escribir el log de la aplicación.
ini_set('log_errors', '0');
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(static function (Throwable $exception): void {
    fwrite(STDERR, 'FAIL runner aislado: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
});
require_once APP_PATH . '/config/db.php';
require_once APP_PATH . '/config/migrations.php';
require_once APP_PATH . '/config/chatgpt_migrations.php';
require_once APP_PATH . '/config/claude_migrations.php';
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $parts = explode('\\', substr($class, 4));
    $parts[0] = strtolower($parts[0]);
    $file = APP_PATH . '/' . implode('/', $parts) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use App\Models\ClaudeCuenta;
use App\Models\ChatgptCuenta;

function clPdo(bool $withDatabase = true): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
        . ($withDatabase ? ';dbname=' . DB_NAME : '') . ';charset=utf8mb4';
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function clCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function clEq(mixed $actual, mixed $expected, string $message): void
{
    clCheck($actual === $expected, $message);
}

function clReject(callable $action, string $message): void
{
    try {
        $action();
    } catch (InvalidArgumentException | DomainException | RuntimeException $exception) {
        if ($exception instanceof PDOException) {
            throw $exception;
        }
        return;
    }
    throw new RuntimeException($message);
}

function clToken(): string
{
    return bin2hex(random_bytes(32));
}

function clConcurrentSales(int $id, string $date, array $tokens, bool $canExceed = false): array
{
    // La fila se retiene hasta que ambos procesos estén listos para vender.
    $locker = clPdo();
    $locker->beginTransaction();
    $locker->query('SELECT id FROM cuentas_principales WHERE id = ' . $id . ' FOR UPDATE')->fetch();
    $children = [];
    try {
        foreach ($tokens as $token) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . session_save_path(), __FILE__, '--worker', (string) $id, '1', $date, $token, $canExceed ? '--admin' : '--operator'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            clCheck(is_resource($process), 'No pudo iniciarse el proceso de venta.');
            fclose($pipes[0]);
            $children[] = [$process, $pipes];
            stream_set_timeout($pipes[1], 10);
            clEq(trim((string) fgets($pipes[1])), 'READY', 'El proceso no inició correctamente.');
        }
        $locker->rollBack();
        $results = [];
        foreach ($children as [$process, $pipes]) {
            $results[] = trim((string) stream_get_contents($pipes[1]));
            $errors = trim((string) stream_get_contents($pipes[2]));
            fclose($pipes[1]);
            fclose($pipes[2]);
            clEq(proc_close($process), 0, 'Falló el proceso concurrente.');
            clEq($errors, '', 'El proceso concurrente produjo un error.');
        }
        $children = [];
        sort($results);
        return $results;
    } finally {
        if ($locker->inTransaction()) {
            $locker->rollBack();
        }
        foreach ($children as [$process, $pipes]) {
            if (is_resource($process)) {
                proc_terminate($process);
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
                proc_close($process);
            }
        }
    }
}

function clTest(string $name, callable $action): void
{
    global $passed, $failed;
    try {
        $action();
        $passed++;
        echo 'OK   ' . $name . PHP_EOL;
    } catch (Throwable $exception) {
        $failed++;
        // No volcamos filas, contraseñas ni argumentos del modelo.
        echo 'FAIL ' . $name . ': ' . $exception->getMessage() . PHP_EOL;
    }
}

if ($worker) {
    try {
        $workerArgs = array_slice($argv, array_search('--worker', $argv, true) + 1);
        [$accountId, $devices, $saleDate, $token] = $workerArgs;
        $model = new ClaudeCuenta(clPdo());
        echo "READY\n";
        flush();
        $model->sell((int) $accountId, (int) $devices, $saleDate, $token, in_array('--admin', $argv, true));
        echo "SOLD\n";
        exit(0);
    } catch (DomainException | InvalidArgumentException | RuntimeException $exception) {
        if ($exception instanceof PDOException) {
            fwrite(STDERR, "ERROR\n");
            exit(1);
        }
        echo "REJECTED\n";
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDERR, "ERROR\n");
        exit(1);
    }
}

$passed = 0;
$failed = 0;

clTest('mes calendario ajusta enero sin añadir gracia', static function (): void {
    clEq(ClaudeCuenta::paymentDates('2026-01-31'), [
        'fecha_pago' => '2026-01-31', 'fin_pagado' => '2026-02-28', 'fin_gracia' => null,
    ], 'Debe guardar exactamente un mes calendario sin gracia.');
});
clTest('mes calendario respeta febrero bisiesto y cambio de año', static function (): void {
    clEq(ClaudeCuenta::paymentDates('2024-01-31')['fin_pagado'], '2024-02-29', 'Debe respetar el año bisiesto.');
    clEq(ClaudeCuenta::paymentDates('2026-12-14')['fin_pagado'], '2027-01-14', 'Debe cruzar el cambio de año.');
});
clTest('fechas inexistentes y formatos ambiguos se rechazan', static function (): void {
    foreach (['2026-02-30', '14/10/2026', '', '2026-13-01', "2026-10-14\0", '12026-10-14'] as $date) {
        clReject(static fn() => ClaudeCuenta::paymentDates($date), 'Aceptó una fecha inválida.');
    }
});
clTest('vigencia termina al fin pagado incluso con una gracia heredada', static function (): void {
    $account = ['fecha_inicio' => '2026-10-14', 'fecha_vencimiento' => '2026-11-14', 'fin_gracia' => '2026-12-14'];
    foreach ([['2026-10-13', 'SIN_PAGO'], ['2026-10-14', 'PAGADO'], ['2026-11-13', 'PAGADO'], ['2026-11-14', 'GRATIS'], ['2026-12-13', 'GRATIS']] as [$date, $state]) {
        $period = ClaudeCuenta::period($account, new DateTimeImmutable($date));
        clEq($period['estado_periodo'], $state, 'Estado incorrecto en ' . $date . '.');
        clEq($period['dias_gracia'], null, 'Claude nunca debe mostrar días de gracia.');
    }
    clEq(ClaudeCuenta::period($account, new DateTimeImmutable('2026-11-13'))['dias_pagado'], 1, 'Debe quedar un día pagado.');
    $account['fin_gracia'] = null;
    clEq(ClaudeCuenta::period($account, new DateTimeImmutable('2026-10-14'))['estado_periodo'], 'PAGADO', 'Una cuenta Claude no requiere fin de gracia.');
    clEq(ClaudeCuenta::period([], new DateTimeImmutable('2026-10-14'))['estado_periodo'], 'SIN_PAGO', 'Sin pago no hay vigencia.');
});
clTest('cada cupo cuesta Bs 85 y no se ofrece una venta de dos dispositivos', static function (): void {
    clEq(ClaudeCuenta::CAPACIDAD, 5, 'Claude debe ofrecer cinco cupos.');
    clEq(ClaudeCuenta::salePrice(1), '85.00', 'Cada cupo cuesta Bs 85.');
    foreach ([-1, 0, 2, 3, 5] as $quantity) {
        clReject(static fn() => ClaudeCuenta::salePrice($quantity), 'Aceptó una cantidad no ofrecida.');
    }
});
clTest('plantillas sustituyen marcadores conocidos y conservan desconocidos', static function (): void {
    clEq(ClaudeCuenta::renderMessage('Correo {CORREO}; {CUPOS}; {DESCONOCIDA}', ['correo' => 'fixture@example.invalid', 'cupos' => 1]),
        'Correo fixture@example.invalid; 1; {DESCONOCIDA}', 'La plantilla alteró marcadores desconocidos.');
});

if ($pureOnly) {
    echo "Resultado: {$passed} correctas, {$failed} fallidas.\n";
    exit($failed === 0 ? 0 : 1);
}

function clCreateAccount(ClaudeCuenta | ChatgptCuenta $model, string $paymentDate): int
{
    $id = $model->create([
        'etiqueta' => 'Cuenta fixture ' . bin2hex(random_bytes(4)),
        'correo' => 'fixture-' . bin2hex(random_bytes(4)) . '@example.invalid',
        'password_cuenta' => 'fixture-local-password', 'fecha_pago' => $paymentDate,
        'monto_bs' => '185.50', 'notas' => 'Solo pruebas aisladas',
    ]);
    if (in_array($model->find($id)['estado_periodo'], ['PAGADO', 'GRACIA'], true)) {
        $model->selectDistribution($id);
    }
    return $id;
}

function clFixture(PDO $pdo): void
{
    $statements = [
        "CREATE TABLE plataformas (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL, tipo_servicio VARCHAR(30) NOT NULL, duraciones_disponibles VARCHAR(100) NULL, dato_renovacion VARCHAR(20) NOT NULL DEFAULT 'NO_APLICA', usa_cuentas_principales TINYINT NOT NULL DEFAULT 0, mensaje_menos_2 TEXT NULL, mensaje_menos_1 TEXT NULL, mensaje_rec_7 TEXT NULL, mensaje_rec_15 TEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE cuentas_principales (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, plataforma_id INT NOT NULL, etiqueta VARCHAR(120) NOT NULL, correo VARCHAR(190) NULL, password_cuenta TEXT NULL, capacidad INT NOT NULL DEFAULT 20, fecha_inicio DATE NULL, fecha_vencimiento DATE NULL, activo TINYINT NOT NULL DEFAULT 1, notas TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE modalidades (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, plataforma_id INT NULL, nombre_modalidad VARCHAR(100) NULL, dispositivos INT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE suscripciones (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, modalidad_id INT NULL, cuenta_principal_id INT NULL, flag_no_renovo TINYINT NOT NULL DEFAULT 0, estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }
    run_chatgpt_schema_migrations($pdo);
    run_claude_schema_migrations($pdo);
}

// Todas las escrituras están confinadas a este esquema creado por el runner.
$server = null;
$databaseCreated = false;
try {
    $server = clPdo(false);
    $exists = $server->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :name');
    $exists->execute(['name' => $testDatabase]);
    clCheck($exists->fetch() === false, 'La base de pruebas ya existe; se rechaza usarla o eliminarla.');
    $server->exec('CREATE DATABASE `' . $testDatabase . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $databaseCreated = true;
    $pdo = clPdo();
    clFixture($pdo);
    $model = new ClaudeCuenta($pdo);
    $chatgpt = new ChatgptCuenta($pdo);
    $today = new DateTimeImmutable('today');
    $todayString = $today->format('Y-m-d');
    $paymentDate = $today->modify('-2 days')->format('Y-m-d');

    clTest('crear cuenta recomienda cinco cupos y registra costo manual sin asumir USD 20', static function () use ($model, $pdo, $paymentDate): void {
        $id = clCreateAccount($model, $paymentDate);
        $account = $model->find($id);
        clEq($account['servicio'], 'CLAUDE', 'Debe identificar Claude.');
        clEq((int) $account['capacidad'], 5, 'La capacidad debe ser cinco cupos.');
        clEq((int) $account['ocupados'], 0, 'Una cuenta nueva debe estar libre.');
        clEq((int) $account['libres'], 5, 'Deben estar disponibles los cinco cupos.');
        clEq($account['recomendado'], 5, 'Debe recomendar cinco cupos.');
        clEq($account['excedidos'], 0, 'Una cuenta nueva no debe exceder la recomendación.');
        clEq($account['supera_recomendado'], false, 'Una cuenta nueva no supera lo recomendado.');
        clEq($account['hasta_recomendado'], 5, 'Deben quedar cinco cupos hasta lo recomendado.');
        clEq($account['fin_gracia'], null, 'La cuenta nueva no debe tener gracia.');
        clCheck(str_starts_with((string) $account['password_cuenta'], 'enc:'), 'Las credenciales deben quedar cifradas.');
        clEq(decrypt_secret((string) $account['password_cuenta']), 'fixture-local-password', 'La contraseña debe poder entregarse.');
        $payments = $model->payments($id);
        clEq(count($payments), 1, 'Debe registrar exactamente un pago inicial.');
        clEq($payments[0]['monto_usd'], null, 'Claude no tiene un costo USD definido por el usuario.');
        clEq($payments[0]['monto_bs'], '185.50', 'Debe conservar el monto real ingresado.');
        clEq($payments[0]['fin_gracia'], null, 'El historial no debe inventar gracia.');
        clEq(count($pdo->query("SHOW TABLES LIKE 'clientes'")->fetchAll()), 0, 'Las ventas no requieren clientes.');
    });

    clTest('montos manuales inválidos no dejan cuentas incompletas', static function () use ($model, $pdo, $paymentDate): void {
        $before = (int) $pdo->query('SELECT COUNT(*) FROM cuentas_principales')->fetchColumn();
        foreach (['0', '-5', '1.999', 'abc', 'NaN', 'Infinity'] as $amount) {
            clReject(static fn() => $model->create([
                'etiqueta' => 'Cuenta inválida', 'correo' => 'invalid-' . clToken() . '@example.invalid',
                'password_cuenta' => 'fixture-local-password', 'fecha_pago' => $paymentDate, 'monto_bs' => $amount,
            ]), 'Aceptó un costo inválido.');
        }
        clEq((int) $pdo->query('SELECT COUNT(*) FROM cuentas_principales')->fetchColumn(), $before, 'Un monto inválido dejó una cuenta incompleta.');
    });

    clTest('venta consume un cupo y vence con la cuenta; repetir token no duplica', static function () use ($model, $paymentDate): void {
        $id = clCreateAccount($model, $paymentDate);
        $token = clToken();
        $sale = $model->sell($id, 1, $paymentDate, $token);
        clEq($model->sell($id, 1, $paymentDate, $token), $sale, 'Repetir una venta debe devolver el registro original.');
        $sales = $model->sales($id);
        clEq(count($sales), 1, 'El token no debe duplicar ventas.');
        clEq($sales[0]['monto_bs'], '85.00', 'El monto de cada cupo debe ser Bs 85.');
        clEq((int) $sales[0]['dispositivos'], 1, 'Cada venta corresponde a un cupo.');
        clEq($sales[0]['fecha_vencimiento'], ClaudeCuenta::paymentDates($paymentDate)['fin_pagado'], 'La venta debe vencer junto con la cuenta.');
        clEq((int) $model->find($id)['ocupados'], 1, 'Una venta ocupa un cupo.');
        clReject(static fn() => $model->sell($id, 2, $paymentDate, clToken()), 'No debe aceptar dos dispositivos juntos.');
        $otherDate = (new DateTimeImmutable($paymentDate))->modify('+1 day')->format('Y-m-d');
        clReject(static fn() => $model->sell($id, 1, $otherDate, $token), 'No debe reutilizar un token con otra fecha.');
        $other = clCreateAccount($model, $paymentDate);
        clReject(static fn() => $model->sell($other, 1, $paymentDate, $token), 'No debe reutilizar un token en otra cuenta.');
        clEq((int) $model->find($id)['ocupados'], 1, 'Una venta rechazada no ocupa cupos.');
    });

    clTest('inactividad, fechas futuras y fechas fuera de vigencia bloquean ventas', static function () use ($model, $paymentDate, $today): void {
        $id = clCreateAccount($model, $paymentDate);
        clReject(static fn() => $model->sell($id, 1, $today->modify('-3 days')->format('Y-m-d'), clToken()), 'No debe vender antes del inicio.');
        clReject(static fn() => $model->sell($id, 1, $today->modify('+1 day')->format('Y-m-d'), clToken()), 'No debe vender con fecha futura.');
        $model->toggleActive($id);
        clReject(static fn() => $model->sell($id, 1, $paymentDate, clToken()), 'No debe vender una cuenta inactiva.');
        clEq((int) $model->find($id)['ocupados'], 0, 'Las ventas rechazadas deben conservar cupos.');
    });

    clTest('una gracia heredada nunca extiende ventas Claude después del mes pagado', static function () use ($model, $pdo, $today): void {
        $past = shift_months_clamped($today, -1)->modify('-2 days')->format('Y-m-d');
        $id = clCreateAccount($model, $past);
        $dates = ClaudeCuenta::paymentDates($past);
        $stmt = $pdo->prepare('UPDATE cuentas_principales SET fin_gracia = :gracia WHERE id = :id');
        $stmt->execute(['gracia' => $today->modify('+30 days')->format('Y-m-d'), 'id' => $id]);
        clEq($model->find($id)['estado_periodo'], 'GRATIS', 'Una gracia heredada no extiende la cuenta.');
        clReject(static fn() => $model->sell($id, 1, $dates['fin_pagado'], clToken()), 'No debe vender al límite pagado.');
        clReject(static fn() => $model->sell($id, 1, $today->format('Y-m-d'), clToken()), 'No debe vender en una supuesta gracia.');
        clReject(static fn() => $model->sell($id, 1, $past, clToken()), 'Una fecha de venta pasada no debe permitir vender una cuenta actualmente vencida.');
    });

    clTest('nuevo pago mantiene cupos e historial y elimina gracia heredada', static function () use ($model, $pdo, $paymentDate, $todayString): void {
        $id = clCreateAccount($model, $paymentDate);
        $model->sell($id, 1, $paymentDate, clToken());
        $salesBefore = $model->sales($id);
        $stmt = $pdo->prepare('UPDATE cuentas_principales SET fin_gracia = :gracia WHERE id = :id');
        $stmt->execute(['gracia' => '2099-12-31', 'id' => $id]);
        $token = clToken();
        $payment = $model->registerPayment($id, $todayString, '210.25', $token);
        clEq($model->registerPayment($id, $todayString, '210.25', $token), $payment, 'Repetir un pago debe devolver el mismo registro.');
        $account = $model->find($id);
        clEq((int) $account['ocupados'], 1, 'El pago no debe liberar cupos.');
        clEq($account['fecha_inicio'], $todayString, 'El período empieza en la fecha pagada.');
        clEq($account['fecha_vencimiento'], ClaudeCuenta::paymentDates($todayString)['fin_pagado'], 'El pago otorga exactamente un mes.');
        clEq($account['fin_gracia'], null, 'El pago debe eliminar una gracia heredada.');
        clEq($model->sales($id), $salesBefore, 'El pago no altera vencimientos históricos.');
        clEq(count($model->payments($id)), 2, 'El token no debe duplicar pagos.');
        clEq($model->payments($id)[0]['monto_usd'], null, 'El pago no presupone un precio USD.');
        clEq($model->payments($id)[0]['monto_bs'], '210.25', 'El pago conserva el costo manual.');
        clReject(static fn() => $model->registerPayment($id, $todayString, '210.26', $token), 'El token no permite cambiar monto.');
        clReject(static fn() => $model->registerPayment($id, $paymentDate, '210.25', clToken()), 'No debe retroceder al período anterior.');
    });

    clTest('administrador vende cupos adicionales y reinicia el ciclo conservando historial', static function () use ($model, $pdo, $paymentDate): void {
        $id = clCreateAccount($model, $paymentDate);
        for ($sale = 0; $sale < 5; $sale++) {
            $model->sell($id, 1, $paymentDate, clToken(), true);
        }
        $before = $model->find($id);
        clEq($before['ocupados'], 5, 'Debe comenzar en cinco cupos ocupados.');
        clEq($before['excedidos'], 0, 'Cinco cupos no superan la recomendación.');
        clEq($before['supera_recomendado'], false, 'Alcanzar lo recomendado no debe contar como exceso.');
        clEq($before['hasta_recomendado'], 0, 'Al alcanzar cinco quedan cero hasta lo recomendado.');
        $token = clToken();
        $sixth = $model->sell($id, 1, $paymentDate, $token, true);
        clEq($model->sell($id, 1, $paymentDate, $token, true), $sixth, 'Repetir la sexta venta debe ser idempotente.');
        $account = $model->find($id);
        clEq($account['ocupados'], 6, 'La sexta venta debe ocupar el sexto cupo.');
        clEq($account['recomendado'], 5, 'La recomendación debe seguir siendo cinco cupos.');
        clEq($account['excedidos'], 1, 'Debe informar un cupo por encima de lo recomendado.');
        clEq($account['supera_recomendado'], true, 'Debe informar que supera la recomendación.');
        clEq($account['hasta_recomendado'], 0, 'El contador recomendado no debe quedar negativo.');
        clEq($account['libres'], 0, 'El contador compatible no debe quedar negativo.');
        clEq((string) $account['ventas_bs'], '510.00', 'Debe incluir los Bs 85 de la sexta venta.');
        $sales = $model->sales($id);
        clEq(count($sales), 6, 'Debe conservar la sexta venta una sola vez en el historial.');
        clEq($sales[0]['monto_bs'], '85.00', 'La sexta venta sigue costando Bs 85.');
        clEq($sales[0]['fecha_vencimiento'], $before['fecha_vencimiento'], 'Superar cinco no cambia la vigencia.');
        $model->sell($id, 1, $paymentDate, clToken(), true);
        $account = $model->find($id);
        clEq($account['ocupados'], 7, 'Una cuenta que ya supera cinco debe admitir otra venta.');
        clEq($account['excedidos'], 2, 'Debe contar todos los cupos excedidos.');
        clEq((string) $account['ventas_bs'], '595.00', 'Debe incluir ventas posteriores al exceso.');
        clEq($account['fecha_inicio'], $before['fecha_inicio'], 'Las ventas no alteran el inicio del período.');
        clEq($account['fin_gracia'], null, 'El exceso no debe añadir gracia.');
        clEq($model->startCycle($id, true, 'admin-fixture', clToken()), 7, 'La liberación debe incluir todos los cupos excedidos.');
        $released = $model->find($id);
        clEq($released['ocupados'], 0, 'La liberación debe vaciar la cuenta.');
        clEq($released['excedidos'], 0, 'La liberación debe eliminar el exceso.');
        clEq($released['supera_recomendado'], false, 'La cuenta liberada no supera lo recomendado.');
        clEq($released['hasta_recomendado'], 5, 'La liberación debe restaurar el contador recomendado.');
        clEq((string) $released['ventas_bs'], '595.00', 'La liberación conserva el ingreso completo.');
        $history = $model->sales($id);
        clEq(count($history), 7, 'La liberación debe conservar todas las ventas.');
        clCheck(count(array_filter($history, static fn(array $sale): bool => $sale['liberada_at'] === null)) === 0, 'Todas las ventas deben quedar liberadas.');
        $release = $pdo->query('SELECT dispositivos_liberados FROM claude_liberaciones WHERE cuenta_principal_id = ' . $id)->fetchColumn();
        clEq((int) $release, 7, 'El historial de liberación debe registrar los siete cupos.');
        clEq($model->sell($id, 1, $paymentDate, $token, true), $sixth, 'Repetir la venta liberada debe conservar su identidad.');
        clEq($model->find($id)['ocupados'], 0, 'Repetir una venta liberada no debe ocupar cupos.');
        clEq(count($model->sales($id)), 7, 'Repetir una venta liberada no debe duplicar el historial.');
    });

    clTest('cupos heredados cuentan usuarios y se liberan junto con nuevas ventas', static function () use ($model, $pdo, $paymentDate): void {
        $id = clCreateAccount($model, $paymentDate);
        $pdo->exec("INSERT INTO modalidades (nombre_modalidad, dispositivos) VALUES ('Fixture dos', 2)");
        $modalidadId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('INSERT INTO suscripciones (modalidad_id, cuenta_principal_id, flag_no_renovo) VALUES (:modalidad, :cuenta, 0)');
        $stmt->execute(['modalidad' => $modalidadId, 'cuenta' => $id]);
        $legacyId = (int) $pdo->lastInsertId();
        $token = clToken();
        $sale = $model->sell($id, 1, $paymentDate, $token);
        clEq((int) $model->find($id)['ocupados'], 2, 'Claude cuenta un acceso heredado como un cupo, aunque su modalidad indique dos dispositivos.');
        clEq($model->startCycle($id, true, 'admin-fixture', clToken()), 2, 'Debe liberar los cupos nuevos y heredados.');
        clEq((int) $model->find($id)['libres'], 5, 'La liberación debe recuperar los cinco cupos.');
        $legacy = $pdo->query('SELECT flag_no_renovo, estado FROM suscripciones WHERE id = ' . $legacyId)->fetch();
        clEq((int) $legacy['flag_no_renovo'], 1, 'Debe marcar el cupo heredado como liberado.');
        clEq($legacy['estado'], 'VENCIDO', 'Debe finalizar el acceso heredado.');
        $sales = $model->sales($id);
        clEq(count($sales), 1, 'La liberación conserva ventas históricas.');
        clCheck($sales[0]['liberada_at'] !== null, 'La venta liberada debe quedar archivada.');
        clEq($model->sell($id, 1, $paymentDate, $token), $sale, 'Repetir una venta archivada devuelve su registro original.');
        clEq((int) $model->find($id)['ocupados'], 0, 'Repetir venta archivada no reocupa cupos.');
        clEq(count($model->payments($id)), 1, 'La liberación conserva pagos históricos.');
        clEq($model->startCycle($id, true, 'admin-fixture', clToken()), 0, 'Liberar una cuenta vacía debe ser inocuo.');
    });

    clTest('vencimiento no libera cupos hasta acción manual', static function () use ($model, $pdo, $paymentDate, $today): void {
        $id = clCreateAccount($model, $paymentDate);
        $model->sell($id, 1, $paymentDate, clToken());
        $past = shift_months_clamped($today, -2)->format('Y-m-d');
        $stmt = $pdo->prepare('UPDATE cuentas_principales SET fecha_inicio = :inicio, fecha_vencimiento = :fin WHERE id = :id');
        $stmt->execute(['inicio' => $past, 'fin' => ClaudeCuenta::paymentDates($past)['fin_pagado'], 'id' => $id]);
        $account = $model->find($id);
        clEq($account['estado_periodo'], 'GRATIS', 'Debe detectar el vencimiento.');
        clEq((int) $account['ocupados'], 1, 'El vencimiento no debe liberar automáticamente.');
        clReject(static fn() => $model->startCycle($id, true, 'admin-fixture', clToken()), 'No debe iniciar un ciclo Gratis.');
        $model->registerPayment($id, $today->format('Y-m-d'), '200.00', clToken());
        clEq($model->startCycle($id, true, 'admin-fixture', clToken()), 1, 'El pago habilita el nuevo ciclo sin borrar historial.');
    });

    clTest('edición conserva contraseña vacía y rechaza correo duplicado', static function () use ($model, $paymentDate): void {
        $first = clCreateAccount($model, $paymentDate);
        $second = clCreateAccount($model, $paymentDate);
        $account = $model->find($first);
        $secondBefore = $model->find($second);
        $payload = ['etiqueta' => 'Fixture editada', 'correo' => $account['correo'], 'password_cuenta' => '', 'notas' => 'Edición de prueba'];
        $model->update($first, $payload);
        clEq($model->find($first)['password_cuenta'], $account['password_cuenta'], 'Contraseña vacía al editar conserva el valor cifrado.');
        clReject(static fn() => $model->update($second, $payload), 'No debe duplicar correo de otra cuenta Claude.');
        clReject(static fn() => $model->create($payload + ['fecha_pago' => $paymentDate, 'monto_bs' => '200']), 'No debe crear otra cuenta Claude con el mismo correo.');
        clEq($model->find($second), $secondBefore, 'Una edición rechazada conserva la cuenta.');
    });

    clTest('Claude y ChatGPT separan lectura, escritura, pagos, ventas y plantillas', static function () use ($model, $chatgpt, $paymentDate, $todayString): void {
        $claudeId = clCreateAccount($model, $paymentDate);
        $chatgptId = clCreateAccount($chatgpt, $paymentDate);
        $claudeBefore = $model->find($claudeId);
        $chatgptBefore = $chatgpt->find($chatgptId);
        clEq($model->find($chatgptId), null, 'Claude no expone cuentas ChatGPT.');
        clEq($chatgpt->find($claudeId), null, 'ChatGPT no expone cuentas Claude.');
        clEq($model->payments($chatgptId), [], 'Claude no expone pagos ChatGPT.');
        clEq($chatgpt->payments($claudeId), [], 'ChatGPT no expone pagos Claude.');
        clEq($model->sales($chatgptId), [], 'Claude no expone ventas ChatGPT.');
        foreach ([[$model, $chatgptId], [$chatgpt, $claudeId]] as [$wrongModel, $wrongId]) {
            clReject(static fn() => $wrongModel->sell($wrongId, 1, $paymentDate, clToken()), 'Una venta no puede cruzar servicios.');
            clReject(static fn() => $wrongModel->registerPayment($wrongId, $todayString, '220', clToken()), 'Un pago no puede cruzar servicios.');
            clReject(static fn() => $wrongModel->startCycle($wrongId, true, 'admin-fixture', clToken()), 'La liberación no puede cruzar servicios.');
            clReject(static fn() => $wrongModel->toggleActive($wrongId), 'La activación no puede cruzar servicios.');
            clReject(static fn() => $wrongModel->update($wrongId, ['etiqueta' => 'Intento', 'correo' => 'wrong@example.invalid']), 'Una edición no puede cruzar servicios.');
        }
        clEq($model->find($claudeId), $claudeBefore, 'Las operaciones cruzadas modificaron Claude.');
        clEq($chatgpt->find($chatgptId), $chatgptBefore, 'Las operaciones cruzadas modificaron ChatGPT.');
        clEq($model->templates(), ['entrega_1' => '', 'renovacion' => ''], 'Claude debe empezar con sus dos textos pendientes.');
        $chatgptTemplates = $chatgpt->templates();
        $texts = ['entrega_1' => 'Claude {CORREO} / {PASSWORD}', 'renovacion' => 'Claude renovar {CORREO}; {PERSONALIZADO}'];
        $model->saveTemplates($texts);
        clEq($model->templates(), $texts, 'Claude conserva sus textos personalizados.');
        clEq($chatgpt->templates(), $chatgptTemplates, 'Los textos de Claude no deben reemplazar ChatGPT.');
        $token = clToken();
        $model->sell($claudeId, 1, $paymentDate, $token);
        $chatgpt->sell($chatgptId, 2, $paymentDate, $token);
        clEq((int) $model->find($claudeId)['ocupados'], 1, 'Claude debe conservar un cupo vendido.');
        clEq((int) $chatgpt->find($chatgptId)['ocupados'], 2, 'ChatGPT debe seguir contando dispositivos.');
    });

    clTest('migración Claude repetida conserva cuentas y no añade gracia', static function () use ($pdo, $model, $paymentDate): void {
        $id = clCreateAccount($model, $paymentDate);
        $before = $pdo->query('SELECT * FROM cuentas_principales WHERE id = ' . $id)->fetch();
        run_claude_schema_migrations($pdo);
        run_claude_schema_migrations($pdo);
        clEq($pdo->query('SELECT * FROM cuentas_principales WHERE id = ' . $id)->fetch(), $before, 'Repetir la migración no modifica una cuenta existente.');
        clEq(count($model->payments($id)), 1, 'La migración no duplica pagos.');
    });

    require_once __DIR__ . '/distribucion.php';
    runDistributionTests($pdo, $paymentDate, 'clTest');
    require_once __DIR__ . '/entregas.php';
    runDeliveryTests($pdo, 'clTest');

    clTest('dos vendedores concurrentes no superan el último cupo disponible', static function () use ($model, $paymentDate): void {
        $id = clCreateAccount($model, $paymentDate);
        for ($i = 0; $i < 4; $i++) {
            $model->sell($id, 1, $paymentDate, clToken());
        }
        clEq(clConcurrentSales($id, $paymentDate, [clToken(), clToken()]), ['REJECTED', 'SOLD'], 'Solo una venta puede consumir el último cupo.');
        clEq($model->find($id)['ocupados'], 5, 'No debe exceder la capacidad.');
        clEq($model->find($id)['estado_distribucion'], 'Llena', 'Debe pasar automáticamente a Llena.');
    });

    clTest('dos ventas concurrentes del administrador superan la capacidad y conservan ingresos', static function () use ($model, $paymentDate): void {
        $id = clCreateAccount($model, $paymentDate);
        for ($sale = 0; $sale < 4; $sale++) {
            $model->sell($id, 1, $paymentDate, clToken());
        }
        clEq(clConcurrentSales($id, $paymentDate, [clToken(), clToken()], true), ['SOLD', 'SOLD'], 'Ambas ventas deben registrarse aunque crucen lo recomendado.');
        $account = $model->find($id);
        clEq($account['ocupados'], 6, 'Ambas ventas deben alcanzar seis cupos.');
        clEq($account['excedidos'], 1, 'La concurrencia debe informar un cupo excedido.');
        clEq($account['libres'], 0, 'El contador compatible no debe quedar negativo.');
        clEq((string) $account['ventas_bs'], '510.00', 'Debe registrar los Bs 170 de ambas ventas concurrentes.');
        clEq(count($model->sales($id)), 6, 'Debe conservar ambos registros concurrentes.');
    });

    clTest('ventas concurrentes con el mismo token no duplican ocupación ni ingresos', static function () use ($model, $paymentDate): void {
        $id = clCreateAccount($model, $paymentDate);
        for ($sale = 0; $sale < 5; $sale++) {
            $model->sell($id, 1, $paymentDate, clToken());
        }
        $token = clToken();
        clEq(clConcurrentSales($id, $paymentDate, [$token, $token], true), ['SOLD', 'SOLD'], 'Ambos reintentos deben devolver una venta válida.');
        $sales = $model->sales($id);
        clEq(count($sales), 6, 'El mismo token concurrente debe registrar una sola venta.');
        clEq($model->sell($id, 1, $paymentDate, $token), (int) $sales[0]['id'], 'El token debe devolver el registro concurrente original.');
        $account = $model->find($id);
        clEq($account['ocupados'], 6, 'El mismo token debe sumar un solo cupo.');
        clEq($account['excedidos'], 1, 'Debe registrar un solo cupo excedido.');
        clEq((string) $account['ventas_bs'], '510.00', 'El mismo token no debe duplicar los Bs 85 de ingreso.');
    });
} catch (Throwable $exception) {
    $failed++;
    echo 'FAIL integración aislada: ' . $exception->getMessage() . PHP_EOL;
} finally {
    if ($databaseCreated && $server instanceof PDO) {
        // El nombre validado arriba es la única base que este runner puede borrar.
        $server->exec('DROP DATABASE `' . $testDatabase . '`');
    }
}
echo "Resultado: {$passed} correctas, {$failed} fallidas.\n";
exit($failed === 0 ? 0 : 1);
