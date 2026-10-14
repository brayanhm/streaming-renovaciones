<?php
declare(strict_types=1);

/**
 * Pruebas de ChatGPT. Ejecutar: php tests/chatgpt.php [--pure]
 * La integración crea y elimina SU PROPIA base ghost_chatgpt_test_*.
 * CHATGPT_TEST_DB permite elegir el nombre (debe ser nuevo y tener ese prefijo).
 * No carga las migraciones generales ni lee/escribe la base de producción.
 */

$worker = in_array('--worker', $argv, true);
$pureOnly = in_array('--pure', $argv, true);
$testDatabase = $worker
    ? (string) getenv('DB_NAME')
    : ((string) getenv('CHATGPT_TEST_DB') ?: 'ghost_chatgpt_test_' . date('YmdHis') . '_' . getmypid() . '_' . bin2hex(random_bytes(4)));
if (!preg_match('/^ghost_chatgpt_test_[A-Za-z0-9_]{1,44}$/D', $testDatabase)) {
    fwrite(STDERR, "Nombre de base de pruebas inválido; requiere prefijo ghost_chatgpt_test_.\n");
    exit(2);
}

// Solo el entorno de este proceso: no modifica .env ni la clave del usuario.
foreach (['DB_NAME' => $testDatabase, 'APP_KEY' => 'chatgpt-tests-only-key-never-for-production', 'APP_RUN_MIGRATIONS' => '0'] as $key => $value) {
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

use App\Models\ChatgptCuenta;

function cgPdo(bool $withDatabase = true): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
        . ($withDatabase ? ';dbname=' . DB_NAME : '') . ';charset=utf8mb4';
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function cgCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function cgEq(mixed $actual, mixed $expected, string $message): void
{
    cgCheck($actual === $expected, $message);
}

function cgReject(callable $action, string $message): void
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

function cgToken(): string
{
    return bin2hex(random_bytes(32));
}

function cgConcurrentSales(int $id, string $date, array $tokens, bool $canExceed = false): array
{
    // La fila se retiene hasta que ambos procesos estén listos para vender.
    $locker = cgPdo();
    $locker->beginTransaction();
    $locker->query('SELECT id FROM cuentas_principales WHERE id = ' . $id . ' FOR UPDATE')->fetch();
    $children = [];
    try {
        foreach ($tokens as $token) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . session_save_path(), __FILE__, '--worker', (string) $id, '1', $date, $token, $canExceed ? '--admin' : '--operator'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            cgCheck(is_resource($process), 'No pudo iniciarse el proceso de venta.');
            fclose($pipes[0]);
            $children[] = [$process, $pipes];
            stream_set_timeout($pipes[1], 10);
            cgEq(trim((string) fgets($pipes[1])), 'READY', 'El proceso de venta no inició correctamente.');
        }
        $locker->rollBack();
        $results = [];
        foreach ($children as [$process, $pipes]) {
            $results[] = trim((string) stream_get_contents($pipes[1]));
            $errors = trim((string) stream_get_contents($pipes[2]));
            fclose($pipes[1]);
            fclose($pipes[2]);
            cgEq(proc_close($process), 0, 'Falló el proceso concurrente de venta.');
            cgEq($errors, '', 'El proceso concurrente produjo un error.');
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

function cgTest(string $name, callable $action): void
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
        $model = new ChatgptCuenta(cgPdo());
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

cgTest('mes calendario ajusta fin de enero y encadena la gracia', static function (): void {
    $dates = ChatgptCuenta::paymentDates('2026-01-31');
    cgEq($dates['fecha_pago'], '2026-01-31', 'La fecha de pago cambió.');
    cgEq($dates['fin_pagado'], '2026-02-28', 'Debe ajustar al último día de febrero.');
    cgEq($dates['fin_gracia'], '2026-03-28', 'La gracia debe empezar al acabar el mes pagado.');
});
cgTest('mes calendario respeta febrero bisiesto y cambio de año', static function (): void {
    $leap = ChatgptCuenta::paymentDates('2024-01-31');
    cgEq($leap['fin_pagado'], '2024-02-29', 'Debe respetar el año bisiesto.');
    cgEq($leap['fin_gracia'], '2024-03-29', 'Debe encadenar desde el fin pagado.');
    $year = ChatgptCuenta::paymentDates('2026-12-14');
    cgEq($year['fin_pagado'], '2027-01-14', 'El pago debe cruzar el cambio de año.');
    cgEq($year['fin_gracia'], '2027-02-14', 'La gracia debe cruzar el cambio de año.');
});
cgTest('rechaza fechas inexistentes y formatos ambiguos', static function (): void {
    foreach (['2026-02-30', '14/10/2026', '', '2026-13-01', "2026-10-14\0", '12026-10-14'] as $date) {
        cgReject(static fn() => ChatgptCuenta::paymentDates($date), 'Aceptó una fecha inválida.');
    }
});
cgTest('períodos usan límites exclusivos y contadores exactos', static function (): void {
    $account = ['fecha_inicio' => '2026-10-14', 'fecha_vencimiento' => '2026-11-14', 'fin_gracia' => '2026-12-14'];
    foreach ([
        ['2026-10-13', 'SIN_PAGO'],
        ['2026-10-14', 'PAGADO'],
        ['2026-11-13', 'PAGADO'],
        ['2026-11-14', 'GRACIA'],
        ['2026-12-13', 'GRACIA'],
        ['2026-12-14', 'GRATIS'],
    ] as [$date, $state]) {
        $period = ChatgptCuenta::period($account, new DateTimeImmutable($date));
        cgEq($period['estado_periodo'], $state, 'Estado incorrecto en ' . $date . '.');
    }
    $period = ChatgptCuenta::period($account, new DateTimeImmutable('2026-11-13'));
    cgEq($period['dias_pagado'], 1, 'Debe quedar un día pagado.');
    $period = ChatgptCuenta::period($account, new DateTimeImmutable('2026-12-14'));
    cgEq($period['dias_gracia'], 0, 'Al límite ya no quedan días de gracia.');
    cgEq(ChatgptCuenta::period([], new DateTimeImmutable('2026-10-14'))['estado_periodo'], 'SIN_PAGO', 'Una cuenta sin pago no debe aparecer vigente.');
});
cgTest('precios por dispositivos y rechazo de cantidades no ofrecidas', static function (): void {
    cgEq(ChatgptCuenta::salePrice(1), '30.00', 'Un dispositivo cuesta Bs 30.');
    cgEq(ChatgptCuenta::salePrice(2), '55.00', 'Dos dispositivos cuestan Bs 55.');
    foreach ([-1, 0, 3, 20] as $devices) {
        cgReject(static fn() => ChatgptCuenta::salePrice($devices), 'Aceptó cantidad de dispositivos inválida.');
    }
});
cgTest('plantillas sustituyen claves conocidas y conservan las desconocidas', static function (): void {
    cgEq(ChatgptCuenta::renderMessage('Correo {CORREO}; {DISPOSITIVOS}; {DESCONOCIDA}', ['correo' => 'fixture@example.invalid', 'dispositivos' => 2]),
        'Correo fixture@example.invalid; 2; {DESCONOCIDA}', 'La plantilla modificó un marcador desconocido.');
});

if ($pureOnly) {
    echo "Resultado: {$passed} correctas, {$failed} fallidas.\n";
    exit($failed === 0 ? 0 : 1);
}

function cgCreateAccount(ChatgptCuenta $model, string $paymentDate = '2026-10-14'): int
{
    $id = $model->create([
        'etiqueta' => 'Cuenta fixture ' . bin2hex(random_bytes(4)),
        'correo' => 'fixture-' . bin2hex(random_bytes(4)) . '@example.invalid',
        'password_cuenta' => 'fixture-local-password',
        'fecha_pago' => $paymentDate,
        'monto_bs' => '185.50',
        'notas' => 'Solo pruebas aisladas',
    ]);
    if (in_array($model->find($id)['estado_periodo'], ['PAGADO', 'GRACIA'], true)) {
        $model->selectDistribution($id);
    }
    return $id;
}

function cgFixture(PDO $pdo): void
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
}

// Todas las escrituras están confinadas a este esquema creado por el runner.
$server = null;
$databaseCreated = false;
try {
    $server = cgPdo(false);
    $exists = $server->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :name');
    $exists->execute(['name' => $testDatabase]);
    cgCheck($exists->fetch() === false, 'La base de pruebas ya existe; se rechaza usarla o eliminarla.');
    $server->exec('CREATE DATABASE `' . $testDatabase . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $databaseCreated = true;
    $pdo = cgPdo();
    cgFixture($pdo);
    $model = new ChatgptCuenta($pdo);
    $today = new DateTimeImmutable('today');
    $paymentDate = shift_months_clamped($today, -1)->format('Y-m-d');
    $todayString = $today->format('Y-m-d');

    cgTest('crear cuenta registra costo manual y recomendación de 20 dispositivos', static function () use ($model, $pdo, $paymentDate): void {
        $id = cgCreateAccount($model, $paymentDate);
        $account = $model->find($id);
        cgEq((int) $account['capacidad'], 20, 'La capacidad debe fijarse en 20 dispositivos.');
        cgEq((int) $account['ocupados'], 0, 'La cuenta nueva debe estar libre.');
        cgEq((int) $account['libres'], 20, 'Deben estar disponibles los 20 cupos.');
        cgEq($account['recomendado'], 20, 'Debe recomendar veinte dispositivos.');
        cgEq($account['excedidos'], 0, 'Una cuenta nueva no debe exceder la recomendación.');
        cgEq($account['supera_recomendado'], false, 'Una cuenta nueva no supera lo recomendado.');
        cgEq($account['hasta_recomendado'], 20, 'Deben quedar veinte dispositivos hasta lo recomendado.');
        cgEq((string) $account['fecha_inicio'], $paymentDate, 'La fecha de inicio debe guardar el pago.');
        cgCheck(str_starts_with((string) $account['password_cuenta'], 'enc:'), 'Las credenciales de prueba deben quedar cifradas.');
        cgEq(decrypt_secret((string) $account['password_cuenta']), 'fixture-local-password', 'La contraseña cifrada debe poder entregarse.');
        $payments = $model->payments($id);
        cgEq(count($payments), 1, 'La creación debe guardar exactamente un pago inicial.');
        cgEq($payments[0]['monto_usd'], '20.00', 'El costo base debe registrar USD 20.');
        cgEq($payments[0]['monto_bs'], '185.50', 'El costo real en bolivianos debe ser el monto manual.');
        $clientTables = $pdo->query("SHOW TABLES LIKE 'clientes'")->fetchAll();
        cgEq(count($clientTables), 0, 'La venta sin clientes no debe requerir una tabla de clientes.');
    });

    cgTest('rechaza montos manuales inválidos antes de crear la cuenta', static function () use ($model, $pdo, $paymentDate): void {
        $before = (int) $pdo->query('SELECT COUNT(*) FROM cuentas_principales')->fetchColumn();
        foreach (['0', '-5', '1.999', 'abc', 'NaN', 'Infinity'] as $amount) {
            cgReject(static fn() => $model->create([
                'etiqueta' => 'Cuenta inválida', 'correo' => 'invalid-' . cgToken() . '@example.invalid',
                'password_cuenta' => 'fixture-local-password', 'fecha_pago' => $paymentDate,
                'monto_bs' => $amount, 'notas' => '',
            ]), 'Aceptó un monto de pago inválido.');
        }
        cgEq((int) $pdo->query('SELECT COUNT(*) FROM cuentas_principales')->fetchColumn(), $before, 'Un pago inválido dejó una cuenta incompleta.');
    });

    cgTest('venta uno/dos consume dispositivos y repetir su token es idempotente', static function () use ($model, $paymentDate): void {
        $id = cgCreateAccount($model, $paymentDate);
        $token = cgToken();
        $saleOne = $model->sell($id, 1, $paymentDate, $token);
        cgEq($model->sell($id, 1, $paymentDate, $token), $saleOne, 'Repetir una venta debe devolver el mismo registro.');
        $model->sell($id, 2, $paymentDate, cgToken());
        $sales = $model->sales($id);
        cgEq(count($sales), 2, 'Repetir el token no debe duplicar el historial de ventas.');
        $amounts = array_column($sales, 'monto_bs', 'dispositivos');
        cgEq($amounts[1], '30.00', 'La venta de un dispositivo debe guardar Bs 30.');
        cgEq($amounts[2], '55.00', 'La venta de dos dispositivos debe guardar Bs 55.');
        cgEq((int) $model->find($id)['ocupados'], 3, 'Una venta de dos debe ocupar dos cupos.');
        cgEq((int) $model->find($id)['libres'], 17, 'Los cupos libres deben reflejar dispositivos.');
        cgReject(static fn() => $model->sell($id, 2, $paymentDate, $token), 'El token no puede reutilizarse con otros dispositivos.');
        $otherDay = (new DateTimeImmutable($paymentDate))->modify('+1 day')->format('Y-m-d');
        cgReject(static fn() => $model->sell($id, 1, $otherDay, $token), 'El token no puede reutilizarse con otra fecha.');
        $otherAccount = cgCreateAccount($model, $paymentDate);
        cgReject(static fn() => $model->sell($otherAccount, 1, $paymentDate, $token), 'El token no puede reutilizarse para otra cuenta.');
        cgEq((int) $model->find($id)['ocupados'], 3, 'Un token inválido cambió la ocupación.');
    });

    cgTest('administrador supera la capacidad y reinicia el ciclo sin perder historial', static function () use ($model, $pdo, $paymentDate): void {
        $id = cgCreateAccount($model, $paymentDate);
        for ($sale = 0; $sale < 9; $sale++) {
            $model->sell($id, 2, $paymentDate, cgToken(), true);
        }
        $model->sell($id, 1, $paymentDate, cgToken(), true);
        $before = $model->find($id);
        cgEq($before['ocupados'], 19, 'Debe haber diecinueve dispositivos antes de cruzar la recomendación.');
        cgEq($before['hasta_recomendado'], 1, 'Debe quedar un dispositivo hasta lo recomendado.');
        cgEq($before['excedidos'], 0, 'Diecinueve dispositivos no exceden la recomendación.');
        cgEq($before['supera_recomendado'], false, 'Diecinueve dispositivos están dentro de lo recomendado.');
        $token = cgToken();
        $crossing = $model->sell($id, 2, $paymentDate, $token, true);
        cgEq($model->sell($id, 2, $paymentDate, $token, true), $crossing, 'Repetir la venta que cruza la recomendación debe ser idempotente.');
        $account = $model->find($id);
        cgEq($account['ocupados'], 21, 'La venta de dos debe alcanzar veintiún dispositivos.');
        cgEq($account['recomendado'], 20, 'La recomendación debe seguir siendo veinte.');
        cgEq($account['excedidos'], 1, 'Debe informar un dispositivo por encima de lo recomendado.');
        cgEq($account['supera_recomendado'], true, 'Debe informar que supera la recomendación.');
        cgEq($account['hasta_recomendado'], 0, 'El contador hasta lo recomendado debe quedar en cero.');
        cgEq($account['libres'], 0, 'El contador compatible no debe ser negativo.');
        cgEq((string) $account['ventas_bs'], '580.00', 'Debe sumar todas las ventas, incluida la que supera veinte.');
        $sales = $model->sales($id);
        cgEq(count($sales), 11, 'El historial debe incluir la venta que cruza la recomendación una sola vez.');
        cgEq($sales[0]['monto_bs'], '55.00', 'La venta de dos dispositivos sigue costando Bs 55.');
        cgEq($sales[0]['fecha_vencimiento'], $before['fecha_vencimiento'], 'Superar lo recomendado no cambia la vigencia de la venta.');
        $model->sell($id, 1, $paymentDate, cgToken(), true);
        $account = $model->find($id);
        cgEq($account['ocupados'], 22, 'Una cuenta que ya supera veinte debe admitir otra venta.');
        cgEq($account['excedidos'], 2, 'Debe contar todos los dispositivos excedidos.');
        cgEq((string) $account['ventas_bs'], '610.00', 'Debe incluir también las ventas posteriores al exceso.');
        cgEq($account['fecha_inicio'], $before['fecha_inicio'], 'Las ventas no alteran el inicio del período.');
        cgEq($account['fin_gracia'], $before['fin_gracia'], 'Las ventas no alteran la gracia.');
        cgEq($model->startCycle($id, true, 'admin-fixture', cgToken()), 22, 'La liberación debe incluir todos los dispositivos, también los excedidos.');
        $released = $model->find($id);
        cgEq($released['ocupados'], 0, 'La liberación debe vaciar la cuenta.');
        cgEq($released['excedidos'], 0, 'La liberación debe eliminar el exceso.');
        cgEq($released['supera_recomendado'], false, 'La cuenta liberada no supera lo recomendado.');
        cgEq($released['hasta_recomendado'], 20, 'La liberación debe restaurar el contador recomendado.');
        cgEq((string) $released['ventas_bs'], '610.00', 'La liberación debe conservar el ingreso histórico completo.');
        $history = $model->sales($id);
        cgEq(count($history), 12, 'La liberación debe conservar todas las ventas.');
        cgCheck(count(array_filter($history, static fn(array $sale): bool => $sale['liberada_at'] === null)) === 0, 'Todas las ventas deben quedar liberadas.');
        $release = $pdo->query('SELECT dispositivos_liberados FROM chatgpt_liberaciones WHERE cuenta_principal_id = ' . $id)->fetchColumn();
        cgEq((int) $release, 22, 'El historial de liberación debe registrar veintidós dispositivos.');
        cgEq($model->sell($id, 2, $paymentDate, $token, true), $crossing, 'Repetir la venta liberada debe conservar su identidad.');
        cgEq($model->find($id)['ocupados'], 0, 'Repetir una venta liberada no debe ocupar dispositivos.');
        cgEq(count($model->sales($id)), 12, 'Repetir una venta liberada no debe duplicar el historial.');
    });

    cgTest('ventas rechazan cuenta inactiva y fechas fuera de vigencia', static function () use ($model, $paymentDate, $today): void {
        $id = cgCreateAccount($model, $paymentDate);
        $beforeStart = (new DateTimeImmutable($paymentDate))->modify('-1 day')->format('Y-m-d');
        cgReject(static fn() => $model->sell($id, 1, $beforeStart, cgToken()), 'No debe vender antes del inicio de cuenta.');
        $model->toggleActive($id);
        cgReject(static fn() => $model->sell($id, 1, $paymentDate, cgToken()), 'No debe vender una cuenta inactiva.');
        $expiredDate = shift_months_clamped($today, -3)->format('Y-m-d');
        $expired = cgCreateAccount($model, $expiredDate);
        $dates = ChatgptCuenta::paymentDates($expiredDate);
        cgReject(static fn() => $model->sell($expired, 1, $dates['fin_gracia'], cgToken()), 'No debe vender al finalizar la gracia.');
        $active = cgCreateAccount($model, $paymentDate);
        cgReject(static fn() => $model->sell($active, 1, $today->modify('+1 day')->format('Y-m-d'), cgToken()), 'No debe vender con fecha futura.');
        cgEq((int) $model->find($id)['ocupados'], 0, 'Las ventas rechazadas no deben consumir cupos.');
    });

    cgTest('pago nuevo reinicia fechas sin liberar cupos y su token es idempotente', static function () use ($model, $paymentDate, $todayString): void {
        $id = cgCreateAccount($model, $paymentDate);
        $model->sell($id, 2, $paymentDate, cgToken());
        $previousSales = $model->sales($id);
        $token = cgToken();
        $payment = $model->registerPayment($id, $todayString, '210.25', $token);
        cgEq($model->registerPayment($id, $todayString, '210.25', $token), $payment, 'Repetir el pago debe devolver el mismo registro.');
        $account = $model->find($id);
        cgEq((int) $account['ocupados'], 2, 'Registrar pago no debe liberar cupos.');
        $dates = ChatgptCuenta::paymentDates($todayString);
        cgEq($account['fecha_inicio'], $todayString, 'El período nuevo debe comenzar el día pagado.');
        cgEq($account['fecha_vencimiento'], $dates['fin_pagado'], 'El período debe incluir un mes pagado.');
        cgEq($account['fin_gracia'], $dates['fin_gracia'], 'Cada pago debe incluir un mes de gracia.');
        cgEq($model->sales($id), $previousSales, 'El nuevo pago no debe alterar vencimientos de ventas previas.');
        cgEq(count($model->payments($id)), 2, 'Repetir el token no debe duplicar pagos.');
        cgEq($model->payments($id)[0]['monto_bs'], '210.25', 'Debe conservar el costo manual del nuevo pago.');
        cgReject(static fn() => $model->registerPayment($id, $todayString, '210.26', $token), 'El token de pago no puede reutilizarse con otro monto.');
        cgReject(static fn() => $model->registerPayment($id, $paymentDate, '210.25', $token), 'El token de pago no puede reutilizarse con otra fecha.');
        cgReject(static fn() => $model->registerPayment($id, $paymentDate, '210.25', cgToken()), 'No debe retroceder la vigencia con un pago anterior.');
        $other = cgCreateAccount($model, $paymentDate);
        cgReject(static fn() => $model->registerPayment($other, $todayString, '210.25', $token), 'El token de pago no puede reutilizarse para otra cuenta.');
        foreach (['0', '-2', '1.999', 'abc'] as $amount) {
            cgReject(static fn() => $model->registerPayment($id, $todayString, $amount, cgToken()), 'Aceptó monto inválido en un nuevo pago.');
        }
    });

    cgTest('cupos heredados cuentan dispositivos y solo se liberan manualmente', static function () use ($model, $pdo, $paymentDate): void {
        $id = cgCreateAccount($model, $paymentDate);
        $pdo->exec('INSERT INTO modalidades (nombre_modalidad, dispositivos) VALUES (\'Fixture dos\', 2)');
        $modalidadId = (int) $pdo->lastInsertId();
        $legacy = $pdo->prepare('INSERT INTO suscripciones (modalidad_id, cuenta_principal_id, flag_no_renovo) VALUES (:modalidad, :cuenta, 0)');
        $legacy->execute(['modalidad' => $modalidadId, 'cuenta' => $id]);
        $legacyId = (int) $pdo->lastInsertId();
        $saleToken = cgToken();
        $saleId = $model->sell($id, 1, $paymentDate, $saleToken);
        cgEq((int) $model->find($id)['ocupados'], 3, 'La ocupación debe incluir los dos dispositivos heredados.');
        $released = $model->startCycle($id, true, 'admin-fixture', cgToken());
        cgEq($released, 3, 'Debe liberar dispositivos heredados y nuevos.');
        cgEq((int) $model->find($id)['ocupados'], 0, 'La liberación manual debe vaciar la cuenta.');
        cgEq((int) $model->find($id)['libres'], 20, 'La liberación manual debe recuperar 20 cupos.');
        $legacyState = $pdo->query('SELECT flag_no_renovo, estado FROM suscripciones WHERE id = ' . $legacyId)->fetch();
        cgEq((int) $legacyState['flag_no_renovo'], 1, 'La liberación debe marcar el acceso heredado.');
        cgEq($legacyState['estado'], 'VENCIDO', 'La liberación debe finalizar el acceso heredado.');
        $sales = $model->sales($id);
        cgEq(count($sales), 1, 'Liberar cupos debe conservar el historial de ventas.');
        cgCheck($sales[0]['liberada_at'] !== null, 'La venta liberada debe quedar archivada.');
        cgEq($model->sell($id, 1, $paymentDate, $saleToken), $saleId, 'Repetir una venta archivada debe devolver el mismo registro.');
        cgEq((int) $model->find($id)['ocupados'], 0, 'Repetir el token de una venta archivada no debe reocupar cupos.');
        cgEq(count($model->payments($id)), 1, 'La liberación debe conservar los pagos.');
        cgEq($model->startCycle($id, true, 'admin-fixture', cgToken()), 0, 'Liberar de nuevo debe ser inocuo.');
    });

    cgTest('ventas vencen con el período pagado o de gracia y siguen ocupando hasta liberación', static function () use ($model, $pdo, $paymentDate, $today): void {
        $id = cgCreateAccount($model, $paymentDate);
        $dates = ChatgptCuenta::paymentDates($paymentDate);
        $paidSale = $model->sell($id, 1, $paymentDate, cgToken());
        $graceSale = $model->sell($id, 2, $dates['fin_pagado'], cgToken());
        $salesById = array_column($model->sales($id), null, 'id');
        cgEq($salesById[$paidSale]['fecha_vencimiento'], $dates['fin_pagado'], 'Una venta pagada debe terminar al concluir el mes pagado.');
        cgEq($salesById[$graceSale]['fecha_vencimiento'], $dates['fin_gracia'], 'Una venta en gracia debe terminar al concluir la gracia.');
        $expiredDate = shift_months_clamped($today, -3)->format('Y-m-d');
        $expiredDates = ChatgptCuenta::paymentDates($expiredDate);
        $stmt = $pdo->prepare('UPDATE cuentas_principales SET fecha_inicio = :inicio, fecha_vencimiento = :fin, fin_gracia = :gracia WHERE id = :id');
        $stmt->execute(['inicio' => $expiredDate, 'fin' => $expiredDates['fin_pagado'], 'gracia' => $expiredDates['fin_gracia'], 'id' => $id]);
        $account = $model->find($id);
        cgEq($account['estado_periodo'], 'GRATIS', 'El contador debe detectar el período vencido.');
        cgEq((int) $account['ocupados'], 3, 'El vencimiento no debe liberar dispositivos automáticamente.');
        cgReject(static fn() => $model->startCycle($id, true, 'admin-fixture', cgToken()), 'No debe iniciar un ciclo Gratis.');
        $model->registerPayment($id, $today->format('Y-m-d'), '200.00', cgToken());
        cgEq($model->startCycle($id, true, 'admin-fixture', cgToken()), 3, 'El pago habilita el nuevo ciclo sin borrar historial.');
    });

    cgTest('edición conserva contraseña vacía y rechaza correo duplicado', static function () use ($model, $paymentDate): void {
        $first = cgCreateAccount($model, $paymentDate);
        $second = cgCreateAccount($model, $paymentDate);
        $account = $model->find($first);
        $secondBefore = $model->find($second);
        $payload = ['etiqueta' => 'Fixture editada', 'correo' => $account['correo'], 'password_cuenta' => '', 'notas' => 'Edición de prueba'];
        $model->update($first, $payload);
        cgEq($model->find($first)['password_cuenta'], $account['password_cuenta'], 'Una contraseña vacía al editar debe conservar el valor cifrado.');
        cgReject(static fn() => $model->update($second, $payload), 'No debe permitir duplicar el correo en otra cuenta.');
        cgReject(static fn() => $model->create($payload + ['fecha_pago' => $paymentDate, 'monto_bs' => '200']), 'No debe permitir crear otra cuenta con el mismo correo.');
        cgEq($model->find($second), $secondBefore, 'Una edición rechazada no debe modificar la cuenta.');
    });

    cgTest('plantillas iniciales de entrega conservan textos personalizados y vacíos al migrar', static function () use ($model, $pdo): void {
        $defaults = require APP_PATH . '/config/chatgpt_templates.php';
        cgEq($model->templates(), $defaults + ['renovacion' => ''], 'Las entregas deben incluir las instrucciones iniciales y la renovación debe quedar pendiente.');
        $templates = ['entrega_1' => 'Correo: {CORREO} / {PASSWORD}', 'entrega_2' => 'Dos dispositivos {FECHA_VENCE}', 'renovacion' => 'Renovar {CORREO}; {PERSONALIZADO}'];
        $model->saveTemplates($templates);
        cgEq($model->templates(), $templates, 'Los textos configurados deben conservarse exactamente.');
        run_chatgpt_schema_migrations($pdo);
        cgEq($model->templates(), $templates, 'La migración no debe sobrescribir textos personalizados.');
        $model->saveTemplates(['entrega_1' => '', 'entrega_2' => '', 'renovacion' => '']);
        run_chatgpt_schema_migrations($pdo);
        cgEq($model->templates(), ['entrega_1' => '', 'entrega_2' => '', 'renovacion' => ''], 'La migración debe respetar también los textos guardados vacíos.');
    });

    cgTest('migración clasifica cuentas heredadas y completa gracia sin alterar Claude', static function () use ($pdo, $model): void {
        $insertPlatform = $pdo->prepare('INSERT INTO plataformas (nombre, tipo_servicio) VALUES (:nombre, :tipo)');
        $insertAccount = $pdo->prepare('INSERT INTO cuentas_principales (plataforma_id, etiqueta, correo, capacidad, fecha_inicio, fecha_vencimiento) VALUES (:plataforma, :etiqueta, :correo, 5, :inicio, :fin)');
        $ids = [];
        foreach (['ChatGPT legado fixture' => 'CHATGPT', 'Claude legado fixture' => 'CLAUDE'] as $name => $service) {
            $insertPlatform->execute(['nombre' => $name, 'tipo' => 'RENOVABLE']);
            $platformId = (int) $pdo->lastInsertId();
            $insertAccount->execute(['plataforma' => $platformId, 'etiqueta' => $service . ' fixture', 'correo' => strtolower($service) . '-legacy@example.invalid', 'inicio' => '2026-01-31', 'fin' => '2026-02-28']);
            $ids[$service] = (int) $pdo->lastInsertId();
        }
        run_chatgpt_schema_migrations($pdo);
        $chatgpt = $pdo->query('SELECT servicio, fecha_vencimiento, fin_gracia, capacidad FROM cuentas_principales WHERE id = ' . $ids['CHATGPT'])->fetch();
        cgEq($chatgpt['servicio'], 'CHATGPT', 'La migración debe identificar la cuenta ChatGPT heredada.');
        cgEq($chatgpt['fecha_vencimiento'], '2026-02-28', 'La migración debe conservar el vencimiento pagado original.');
        cgEq($chatgpt['fin_gracia'], '2026-03-28', 'La gracia heredada debe ajustarse al mes calendario.');
        $claude = $pdo->query('SELECT servicio, fecha_inicio, fecha_vencimiento, fin_gracia, capacidad FROM cuentas_principales WHERE id = ' . $ids['CLAUDE'])->fetch();
        cgEq($claude['servicio'], 'CLAUDE', 'La migración debe clasificar Claude.');
        cgEq($claude['fin_gracia'], null, 'La migración ChatGPT no debe asignar gracia a Claude.');
        cgEq((int) $claude['capacidad'], 5, 'La migración no debe cambiar cupos de Claude.');
        cgEq($model->find($ids['CLAUDE']), null, 'El módulo ChatGPT no debe exponer cuentas Claude.');
        run_chatgpt_schema_migrations($pdo);
        cgEq($pdo->query('SELECT servicio, fecha_inicio, fecha_vencimiento, fin_gracia, capacidad FROM cuentas_principales WHERE id = ' . $ids['CLAUDE'])->fetch(), $claude, 'Repetir la migración debe ser inocuo para Claude.');
    });

    cgTest('dos vendedores concurrentes no superan el último cupo disponible', static function () use ($model, $paymentDate): void {
        $id = cgCreateAccount($model, $paymentDate);
        for ($i = 0; $i < 9; $i++) {
            $model->sell($id, 2, $paymentDate, cgToken());
        }
        $model->sell($id, 1, $paymentDate, cgToken());
        cgEq(cgConcurrentSales($id, $paymentDate, [cgToken(), cgToken()]), ['REJECTED', 'SOLD'], 'Solo una venta puede consumir el último cupo.');
        cgEq($model->find($id)['ocupados'], 20, 'No debe exceder la capacidad.');
        cgEq($model->find($id)['estado_distribucion'], 'Llena', 'Debe pasar automáticamente a Llena.');
    });

    cgTest('dos ventas concurrentes del administrador superan la capacidad y conservan ingresos', static function () use ($model, $paymentDate): void {
        $id = cgCreateAccount($model, $paymentDate);
        for ($sale = 0; $sale < 9; $sale++) {
            $model->sell($id, 2, $paymentDate, cgToken());
        }
        $model->sell($id, 1, $paymentDate, cgToken());
        cgEq(cgConcurrentSales($id, $paymentDate, [cgToken(), cgToken()], true), ['SOLD', 'SOLD'], 'Ambas ventas deben registrarse aunque crucen lo recomendado.');
        $account = $model->find($id);
        cgEq($account['ocupados'], 21, 'Ambas ventas deben alcanzar veintiún dispositivos.');
        cgEq($account['excedidos'], 1, 'La concurrencia debe informar un dispositivo excedido.');
        cgEq($account['libres'], 0, 'El contador compatible no debe ser negativo.');
        cgEq((string) $account['ventas_bs'], '585.00', 'Debe registrar los Bs 60 de ambas ventas concurrentes.');
        cgEq(count($model->sales($id)), 12, 'Debe conservar ambos registros concurrentes.');
    });

    cgTest('ventas concurrentes con el mismo token no duplican ocupación ni ingresos', static function () use ($model, $paymentDate): void {
        $id = cgCreateAccount($model, $paymentDate);
        for ($sale = 0; $sale < 10; $sale++) {
            $model->sell($id, 2, $paymentDate, cgToken());
        }
        $before = $model->find($id);
        cgEq($before['ocupados'], 20, 'Debe comenzar en veinte dispositivos.');
        cgEq($before['supera_recomendado'], false, 'Alcanzar la recomendación no debe contar como exceso.');
        cgEq($before['hasta_recomendado'], 0, 'Al alcanzar veinte quedan cero hasta lo recomendado.');
        $token = cgToken();
        cgEq(cgConcurrentSales($id, $paymentDate, [$token, $token], true), ['SOLD', 'SOLD'], 'Ambos reintentos deben devolver una venta válida.');
        $sales = $model->sales($id);
        cgEq(count($sales), 11, 'El mismo token concurrente debe registrar una sola venta.');
        cgEq($model->sell($id, 1, $paymentDate, $token), (int) $sales[0]['id'], 'El token debe devolver el registro concurrente original.');
        $account = $model->find($id);
        cgEq($account['ocupados'], 21, 'El mismo token debe sumar un solo dispositivo.');
        cgEq($account['excedidos'], 1, 'Debe registrar un solo dispositivo excedido.');
        cgEq((string) $account['ventas_bs'], '580.00', 'El mismo token no debe duplicar los Bs 30 de ingreso.');
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
