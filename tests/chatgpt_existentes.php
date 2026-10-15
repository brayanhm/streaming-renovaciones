<?php
declare(strict_types=1);

/**
 * Pruebas de cuentas ChatGPT existentes: php tests/chatgpt_existentes.php [--pure].
 * Crea y elimina exclusivamente una base NUEVA ghost_chatgpt_existing_test_*.
 * CHATGPT_EXISTING_TEST_DB permite elegir su nombre, nunca reutilizar una base.
 * Todos los correos y secretos son fixtures; no carga migraciones generales.
 */

$pureOnly = in_array('--pure', $argv, true);
$testDatabase = (string) getenv('CHATGPT_EXISTING_TEST_DB')
    ?: 'ghost_chatgpt_existing_test_' . date('YmdHis') . '_' . getmypid() . '_' . bin2hex(random_bytes(4));
if (!preg_match('/^ghost_chatgpt_existing_test_[A-Za-z0-9_]{1,36}$/D', $testDatabase)) {
    fwrite(STDERR, "Nombre de base inválido; requiere prefijo ghost_chatgpt_existing_test_.\n");
    exit(2);
}

// Solo este proceso: no modifica .env, la clave ni la base del usuario.
foreach (['DB_NAME' => $testDatabase, 'APP_KEY' => 'chatgpt-existing-tests-only-key', 'APP_RUN_MIGRATIONS' => '0'] as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
require_once __DIR__ . '/../app/config/config.php';
// Reemplaza los handlers de config.php para no escribir storage/logs/app.log.
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

use App\Models\ChatgptCuenta;
use App\Models\ClaudeCuenta;

function cgeCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function cgeEq(mixed $actual, mixed $expected, string $message): void
{
    cgeCheck($actual === $expected, $message);
}

function cgeReject(callable $action, string $message): void
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

function cgeToken(): string
{
    return bin2hex(random_bytes(32));
}

function cgePdo(bool $withDatabase = true): PDO
{
    return new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT
        . ($withDatabase ? ';dbname=' . DB_NAME : '') . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function cgeTest(string $name, callable $action): void
{
    global $passed, $failed;
    try {
        $action();
        $passed++;
        echo 'OK   ' . $name . PHP_EOL;
    } catch (Throwable $exception) {
        $failed++;
        // No volcamos argumentos, filas ni credenciales.
        echo 'FAIL ' . $name . ': ' . $exception->getMessage() . PHP_EOL;
    }
}

function cgeSnapshot(PDO $pdo, ?array $tables = null): array
{
    $tables ??= ['plataformas', 'cuentas_principales', 'modalidades', 'suscripciones', 'clientes',
        'chatgpt_pagos', 'chatgpt_ventas', 'chatgpt_liberaciones', 'chatgpt_plantillas',
        'claude_pagos', 'claude_ventas', 'claude_liberaciones', 'claude_plantillas'];
    $snapshot = [];
    foreach ($tables as $table) {
        $order = str_ends_with($table, '_plantillas') ? 'clave' : 'id';
        $snapshot[$table] = $pdo->query('SELECT * FROM ' . $table . ' ORDER BY ' . $order)->fetchAll();
    }
    return $snapshot;
}

function cgeLedgers(PDO $pdo): array
{
    return cgeSnapshot($pdo, ['clientes', 'modalidades', 'suscripciones',
        'chatgpt_pagos', 'chatgpt_ventas', 'chatgpt_liberaciones',
        'claude_pagos', 'claude_ventas', 'claude_liberaciones']);
}

function cgeRaw(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT * FROM cuentas_principales WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    cgeCheck(is_array($row), 'No se encontró la cuenta fixture.');
    return $row;
}

function cgeWithoutDates(array $row): array
{
    // Configurar confirma la vigencia y actualiza metadata; lo demás se conserva.
    unset($row['fecha_inicio'], $row['fecha_vencimiento'], $row['fin_gracia'], $row['revision_vigencia_pendiente'], $row['updated_at']);
    return $row;
}

function cgePending(ChatgptCuenta $model): int
{
    $result = $model->importPendingEmails(['pending-' . bin2hex(random_bytes(5)) . '@example.invalid']);
    cgeEq($result['creadas'], 1, 'El fixture pendiente debe crear una cuenta.');
    return $result['ids'][0];
}

function cgeCredentials(ChatgptCuenta $model, PDO $pdo, int $id): void
{
    $row = cgeRaw($pdo, $id);
    $model->update($id, ['etiqueta' => $row['etiqueta'], 'correo' => $row['correo'],
        'password_cuenta' => 'fixture-only-local-secret', 'notas' => $row['notas'] ?? '']);
}

function cgePaid(ChatgptCuenta | ClaudeCuenta $model, string $date, string $email = ''): int
{
    $id = $model->create(['etiqueta' => 'Cuenta aislada fixture',
        'correo' => $email ?: 'paid-' . bin2hex(random_bytes(5)) . '@example.invalid',
        'password_cuenta' => 'fixture-only-local-secret', 'fecha_pago' => $date,
        'monto_bs' => '185.50', 'notas' => 'Fixture de conservación']);
    if (in_array($model->find($id)['estado_periodo'], ['PAGADO', 'GRACIA'], true)) {
        $model->selectDistribution($id);
    }
    return $id;
}

function cgeLegacy(PDO $pdo, int $id): void
{
    $pdo->exec("INSERT INTO modalidades (nombre_modalidad, dispositivos) VALUES ('Fixture dos dispositivos', 2)");
    $modeId = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO clientes (nombre, correo) VALUES ('Cliente fixture', 'client-fixture@example.invalid')");
    $clientId = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO suscripciones (modalidad_id, cuenta_principal_id, cliente_id) VALUES (:mode, :account, :client)');
    $stmt->execute(['mode' => $modeId, 'account' => $id, 'client' => $clientId]);
}

function cgeFixture(PDO $pdo): void
{
    $statements = [
        "CREATE TABLE plataformas (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL, tipo_servicio VARCHAR(30) NOT NULL, duraciones_disponibles VARCHAR(100) NULL, dato_renovacion VARCHAR(20) NOT NULL DEFAULT 'NO_APLICA', usa_cuentas_principales TINYINT NOT NULL DEFAULT 0, mensaje_menos_2 TEXT NULL, mensaje_menos_1 TEXT NULL, mensaje_rec_7 TEXT NULL, mensaje_rec_15 TEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE cuentas_principales (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, plataforma_id INT NOT NULL, etiqueta VARCHAR(120) NOT NULL, correo VARCHAR(190) NULL, password_cuenta TEXT NULL, capacidad INT NOT NULL DEFAULT 20, fecha_inicio DATE NULL, fecha_vencimiento DATE NULL, activo TINYINT NOT NULL DEFAULT 1, notas TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE modalidades (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, plataforma_id INT NULL, nombre_modalidad VARCHAR(100) NULL, dispositivos INT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE clientes (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL, correo VARCHAR(150) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE suscripciones (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, modalidad_id INT NULL, cuenta_principal_id INT NULL, cliente_id INT NULL, flag_no_renovo TINYINT NOT NULL DEFAULT 0, estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }
    run_chatgpt_schema_migrations($pdo);
    run_claude_schema_migrations($pdo);
}

$passed = 0;
$failed = 0;

cgeTest('gracia conocida sin inicio pagado usa límites exclusivos', static function (): void {
    $account = ['fecha_inicio' => null, 'fecha_vencimiento' => '2026-01-31', 'fin_gracia' => '2026-02-28'];
    foreach ([['2026-01-30', 'SIN_PAGO'], ['2026-01-31', 'GRACIA'],
        ['2026-02-27', 'GRACIA'], ['2026-02-28', 'GRATIS']] as [$date, $state]) {
        $period = ChatgptCuenta::period($account, new DateTimeImmutable($date));
        cgeEq($period['estado_periodo'], $state, 'Estado incorrecto para una gracia sin inicio pagado.');
        cgeEq($period['dias_pagado'], null, 'No debe inventar días pagados desconocidos.');
    }
    cgeEq(ChatgptCuenta::period($account, new DateTimeImmutable('2026-02-27'))['dias_gracia'], 1, 'Debe quedar un día de gracia.');
    cgeEq(ChatgptCuenta::period($account, new DateTimeImmutable('2026-02-28'))['dias_gracia'], 0, 'Al límite debe quedar cero días de gracia.');
    cgeEq(ChatgptCuenta::period(['fecha_inicio' => null, 'fecha_vencimiento' => '2026-01-31'])['estado_periodo'], 'SIN_PAGO', 'Una fecha incompleta no permite inventar gracia.');
});

cgeTest('Claude conserva SIN_PAGO cuando falta el inicio', static function (): void {
    $account = ['fecha_inicio' => null, 'fecha_vencimiento' => '2026-01-31', 'fin_gracia' => '2026-02-28'];
    cgeEq(ClaudeCuenta::period($account, new DateTimeImmutable('2026-01-31'))['estado_periodo'], 'SIN_PAGO', 'Claude no debe adoptar la gracia de ChatGPT.');
});

cgeTest('revisión pendiente impide considerar vigente una cuenta con fechas completas', static function (): void {
    $account = ['fecha_inicio' => '2026-01-31', 'fecha_vencimiento' => '2026-02-28',
        'fin_gracia' => '2026-03-28', 'revision_vigencia_pendiente' => 1];
    cgeEq(ChatgptCuenta::period($account, new DateTimeImmutable('2026-02-14'))['estado_periodo'], 'SIN_PAGO', 'Las fechas pendientes de revisión no deben permitir vender.');
    $account['revision_vigencia_pendiente'] = 0;
    cgeEq(ChatgptCuenta::period($account, new DateTimeImmutable('2026-02-14'))['estado_periodo'], 'PAGADO', 'Las fechas confirmadas deben usar su período normal.');
});

if ($pureOnly) {
    echo "Resultado: {$passed} correctas, {$failed} fallidas.\n";
    exit($failed === 0 ? 0 : 1);
}

$server = null;
$databaseCreated = false;
try {
    cgeEq(DB_NAME, $testDatabase, 'La configuración debe usar exclusivamente el esquema aislado.');
    cgeEq(APP_RUN_MIGRATIONS, false, 'Las migraciones generales deben estar desactivadas.');
    $server = cgePdo(false);
    $exists = $server->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :name');
    $exists->execute(['name' => $testDatabase]);
    cgeCheck($exists->fetch() === false, 'La base ya existe; se rechaza usarla o eliminarla.');
    $server->exec('CREATE DATABASE `' . $testDatabase . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $databaseCreated = true;
    $pdo = cgePdo();
    cgeEq($pdo->query('SELECT DATABASE()')->fetchColumn(), $testDatabase, 'La conexión debe pertenecer al nuevo esquema de pruebas.');
    cgeFixture($pdo);
    $model = new ChatgptCuenta($pdo);
    $claude = new ClaudeCuenta($pdo);
    $today = new DateTimeImmutable('today');
    $todayString = $today->format('Y-m-d');
    $recent = $today->modify('-7 days')->format('Y-m-d');

    cgeTest('lista vacía devuelve cero cuentas sin crear plataformas o movimientos', static function () use ($model, $pdo): void {
        $snapshot = cgeSnapshot($pdo);
        cgeEq($model->importPendingEmails([]), ['creadas' => 0, 'existentes' => 0, 'ids' => []], 'La lista vacía debe devolver un resultado vacío.');
        cgeEq(cgeSnapshot($pdo), $snapshot, 'La lista vacía no debe escribir ninguna tabla.');
    });

    cgeTest('importación normaliza correos, deduplica y deja datos pendientes sin movimientos', static function () use ($model, $pdo): void {
        $ledgers = cgeLedgers($pdo);
        $result = $model->importPendingEmails(['  FIRST-fixture@example.invalid  ', 'second-fixture@example.invalid', 'first-FIXTURE@example.invalid']);
        cgeEq($result['creadas'], 2, 'Debe crear dos correos únicos.');
        cgeEq($result['existentes'], 0, 'Las repeticiones del lote no son cuentas existentes.');
        cgeEq(count($result['ids']), 2, 'Debe devolver un id por correo único.');
        cgeCheck($result['ids'][0] !== $result['ids'][1], 'Los correos distintos deben tener ids distintos.');
        foreach ($result['ids'] as $index => $id) {
            cgeCheck(is_int($id), 'Los ids deben ser enteros.');
            $account = cgeRaw($pdo, $id);
            cgeEq($account['correo'], ['first-fixture@example.invalid', 'second-fixture@example.invalid'][$index], 'Debe respetar el orden normalizado de primera aparición.');
            cgeEq($account['servicio'], 'CHATGPT', 'La importación debe marcar ChatGPT.');
            cgeEq((int) $account['activo'], 1, 'Una cuenta pendiente debe estar activa.');
            cgeEq((int) $account['capacidad'], 20, 'Debe guardar la recomendación de veinte dispositivos.');
            cgeEq((int) $account['revision_vigencia_pendiente'], 1, 'La cuenta nueva debe requerir revisión de vigencia.');
            foreach (['password_cuenta', 'fecha_inicio', 'fecha_vencimiento', 'fin_gracia'] as $field) {
                cgeEq($account[$field], null, 'No debe inventar credenciales o fechas.');
            }
            cgeCheck(str_contains(strtolower((string) $account['notas']), 'pendiente'), 'Las notas deben indicar la revisión pendiente.');
            cgeEq($model->find($id)['estado_periodo'], 'SIN_PAGO', 'Una cuenta importada debe estar pendiente de fechas.');
            cgeEq(count($model->payments($id)), 0, 'Importar no debe registrar un pago.');
            cgeEq(count($model->sales($id)), 0, 'Importar no debe registrar una venta.');
        }
        cgeEq(cgeLedgers($pdo), $ledgers, 'Importar no debe tocar clientes, suscripciones o movimientos.');
        $snapshot = cgeSnapshot($pdo);
        cgeEq($model->importPendingEmails(['SECOND-fixture@example.invalid', 'first-fixture@example.invalid', 'second-fixture@example.invalid']),
            ['creadas' => 0, 'existentes' => 2, 'ids' => [$result['ids'][1], $result['ids'][0]]], 'La repetición debe devolver los ids originales en el orden del nuevo lote.');
        cgeEq(cgeSnapshot($pdo), $snapshot, 'Repetir la importación no debe escribir ningún campo.');
    });

    cgeTest('correo largo válido conserva correo completo y limita la etiqueta a cien', static function () use ($model, $pdo): void {
        $email = str_repeat('a', 64) . '@' . str_repeat('b', 63) . '.' . str_repeat('c', 13) . '.invalid';
        cgeEq(strlen($email), 150, 'El correo fixture debe alcanzar el límite admitido.');
        cgeCheck(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, 'El correo largo fixture debe ser válido.');
        $id = $model->importPendingEmails([$email])['ids'][0];
        $account = cgeRaw($pdo, $id);
        cgeEq($account['correo'], $email, 'No debe truncar el correo válido.');
        cgeEq($account['etiqueta'], substr($email, 0, 100), 'La etiqueta debe limitarse a cien caracteres.');
    });

    cgeTest('importación existente preserva todos los campos, clientes y movimientos históricos', static function () use ($model, $pdo, $recent): void {
        $id = cgePaid($model, $recent, 'Preserved-fixture@example.invalid');
        $model->sell($id, 2, $recent, cgeToken());
        cgeLegacy($pdo, $id);
        $model->setActive($id, false);
        $snapshot = cgeSnapshot($pdo);
        cgeEq($model->importPendingEmails([' preserved-FIXTURE@example.invalid ', 'PRESERVED-fixture@example.invalid']),
            ['creadas' => 0, 'existentes' => 1, 'ids' => [$id]], 'Debe identificar la cuenta existente sin crear otra.');
        cgeEq(cgeSnapshot($pdo), $snapshot, 'Reimportar no debe cambiar ningún campo, cliente o movimiento.');
        cgeEq($model->find($id)['ocupados'], 4, 'Debe preservar dispositivos vendidos y heredados.');
        cgeEq((int) $model->find($id)['activo'], 0, 'No debe reactivar una cuenta existente.');
    });

    cgeTest('mismo correo en Claude no impide importar ChatGPT ni modifica Claude', static function () use ($model, $claude, $pdo, $recent): void {
        $email = 'shared-service-fixture@example.invalid';
        $claudeId = cgePaid($claude, $recent, $email);
        $claude->sell($claudeId, 1, $recent, cgeToken());
        $before = cgeRaw($pdo, $claudeId);
        $ledgers = cgeLedgers($pdo);
        $result = $model->importPendingEmails([strtoupper($email)]);
        cgeEq($result['creadas'], 1, 'El correo de Claude debe poder crear una cuenta ChatGPT independiente.');
        cgeEq($result['existentes'], 0, 'Claude no cuenta como un ChatGPT existente.');
        cgeCheck($result['ids'][0] !== $claudeId, 'Debe asignar una cuenta distinta para ChatGPT.');
        cgeEq(cgeRaw($pdo, $claudeId), $before, 'La importación no debe modificar la cuenta Claude.');
        cgeEq(cgeLedgers($pdo), $ledgers, 'La importación debe conservar los movimientos de ambos servicios.');
    });

    cgeTest('correos inválidos rechazan el lote completo sin escrituras parciales', static function () use ($model, $pdo): void {
        $overlong = str_repeat('a', 64) . '@' . str_repeat('b', 63) . '.' . str_repeat('c', 14) . '.invalid';
        cgeEq(strlen($overlong), 151, 'El fixture debe exceder el límite por un carácter.');
        cgeCheck(filter_var($overlong, FILTER_VALIDATE_EMAIL) !== false, 'El fixture debe aislar el límite de longitud.');
        $invalid = ['', '   ', 'invalid', 'fixture@', 'fixture@example.invalid extra', "fixture\0@example.invalid", $overlong, null, 123, true, []];
        foreach ($invalid as $value) {
            $snapshot = cgeSnapshot($pdo);
            cgeReject(static fn() => $model->importPendingEmails(['rollback-fixture@example.invalid', $value]), 'Aceptó un correo o tipo inválido.');
            cgeEq(cgeSnapshot($pdo), $snapshot, 'El lote inválido dejó una cuenta o movimiento parcial.');
        }
    });

    cgeTest('admite doscientos correos y rechaza más entradas antes de deduplicar', static function () use ($model, $pdo): void {
        $emails = array_map(static fn(int $index): string => 'batch-fixture-' . $index . '@example.invalid', range(1, 200));
        $ledgers = cgeLedgers($pdo);
        $result = $model->importPendingEmails($emails);
        cgeEq($result['creadas'], 200, 'Debe admitir doscientas entradas válidas.');
        cgeEq(count(array_unique($result['ids'])), 200, 'Debe devolver doscientos ids distintos.');
        cgeEq($result['existentes'], 0, 'El lote de doscientos debe contener nuevas cuentas.');
        cgeEq(cgeLedgers($pdo), $ledgers, 'El lote completo no debe inventar movimientos.');
        foreach ([$emails + [200 => 'batch-extra@example.invalid'], array_fill(0, 201, 'duplicate-batch@example.invalid')] as $tooMany) {
            $snapshot = cgeSnapshot($pdo);
            cgeReject(static fn() => $model->importPendingEmails($tooMany), 'Aceptó más de doscientas entradas originales.');
            cgeEq(cgeSnapshot($pdo), $snapshot, 'Un lote demasiado grande no debe escribir nada.');
        }
    });

    cgeTest('configurar PAGO ajusta enero y febrero bisiesto sin registrar pagos', static function () use ($model, $pdo): void {
        foreach ([['2026-01-31', '2026-02-28', '2026-03-28'], ['2024-01-31', '2024-02-29', '2024-03-29']] as [$start, $paid, $grace]) {
            $id = cgePending($model);
            $before = cgeRaw($pdo, $id);
            $ledgers = cgeLedgers($pdo);
            $model->configureExistingPeriod($id, 'PAGO', $start);
            $account = cgeRaw($pdo, $id);
            cgeEq($account['fecha_inicio'], $start, 'PAGO debe guardar el inicio conocido.');
            cgeEq($account['fecha_vencimiento'], $paid, 'PAGO debe ajustar el mes pagado.');
            cgeEq($account['fin_gracia'], $grace, 'PAGO debe encadenar la gracia desde el fin pagado.');
            cgeEq((int) $account['revision_vigencia_pendiente'], 0, 'Configurar PAGO debe confirmar la vigencia.');
            cgeEq(cgeWithoutDates($account), cgeWithoutDates($before), 'Configurar fechas debe conservar los demás campos.');
            cgeEq(cgeLedgers($pdo), $ledgers, 'Configurar PAGO no representa un pago real.');
        }
    });

    cgeTest('configurar GRACIA conserva inicio desconocido y ajusta el mes calendario', static function () use ($model, $pdo): void {
        foreach ([['2026-01-31', '2026-02-28'], ['2024-01-31', '2024-02-29'], ['1000-01-31', '1000-02-28']] as [$start, $end]) {
            $id = cgePending($model);
            $ledgers = cgeLedgers($pdo);
            $model->configureExistingPeriod($id, 'GRACIA', $start);
            $account = cgeRaw($pdo, $id);
            cgeEq($account['fecha_inicio'], null, 'GRACIA no debe reconstruir el inicio pagado desconocido.');
            cgeEq($account['fecha_vencimiento'], $start, 'El fin pagado conocido debe ser el inicio de gracia.');
            cgeEq($account['fin_gracia'], $end, 'La gracia debe sumar un mes calendario ajustado.');
            cgeEq((int) $account['revision_vigencia_pendiente'], 0, 'Configurar GRACIA debe confirmar la vigencia.');
            cgeEq(cgeLedgers($pdo), $ledgers, 'Configurar GRACIA no debe inventar historial de pagos.');
        }
    });

    cgeTest('origen, fecha inválida o futura no modifican ningún dato', static function () use ($model, $pdo, $today, $todayString): void {
        $id = cgePending($model);
        foreach (['pago', 'gracia', ' PAGO', 'GRACIA ', '', 'OTRO'] as $origin) {
            $snapshot = cgeSnapshot($pdo);
            cgeReject(static fn() => $model->configureExistingPeriod($id, $origin, $todayString), 'Aceptó un origen distinto de PAGO o GRACIA exactos.');
            cgeEq(cgeSnapshot($pdo), $snapshot, 'El origen inválido modificó datos.');
        }
        $invalidDates = ['', '2026-02-30', '2026-13-01', '14/10/2026', '2026-1-31', '0999-12-31',
            '12026-01-31', "2026-01-31\0", ' ' . $todayString, $todayString . ' ', $today->modify('+1 day')->format('Y-m-d')];
        foreach (['PAGO', 'GRACIA'] as $origin) {
            foreach ($invalidDates as $date) {
                $snapshot = cgeSnapshot($pdo);
                cgeReject(static fn() => $model->configureExistingPeriod($id, $origin, $date), 'Aceptó una fecha inválida o futura.');
                cgeEq(cgeSnapshot($pdo), $snapshot, 'La fecha rechazada modificó datos.');
            }
        }
    });

    cgeTest('configurar fechas bloquea ids ajenos a ChatGPT y preserva Claude', static function () use ($model, $claude, $pdo, $recent, $todayString): void {
        $claudeId = cgePaid($claude, $recent);
        foreach ([$claudeId, 2147483647, 0] as $id) {
            $snapshot = cgeSnapshot($pdo);
            cgeReject(static fn() => $model->configureExistingPeriod($id, 'PAGO', $todayString), 'Aceptó un id inexistente o perteneciente a Claude.');
            cgeEq(cgeSnapshot($pdo), $snapshot, 'La operación rechazada modificó una cuenta ajena.');
        }
    });

    cgeTest('revisión de fechas con pagos existentes se rechaza y conserva credenciales, actividad, cupos e historiales', static function () use ($model, $pdo, $recent, $todayString): void {
        $id = cgePaid($model, $recent);
        $model->sell($id, 2, $recent, cgeToken());
        cgeLegacy($pdo, $id);
        $model->setActive($id, false);
        $before = cgeWithoutDates(cgeRaw($pdo, $id));
        $ledgers = cgeLedgers($pdo);
        foreach (['GRACIA', 'PAGO'] as $origin) {
            $snapshot = cgeSnapshot($pdo);
            cgeReject(static fn() => $model->configureExistingPeriod($id, $origin, $todayString), 'No debe revisar fechas que ya tienen un pago registrado.');
            cgeEq(cgeSnapshot($pdo), $snapshot, 'La revisión rechazada no debe cambiar fechas ni datos.');
            cgeEq((int) cgeRaw($pdo, $id)['revision_vigencia_pendiente'], 0, 'Confirmar las fechas debe quitar la revisión pendiente.');
            cgeEq(cgeWithoutDates(cgeRaw($pdo, $id)), $before, 'Las fechas no deben cambiar campos de la cuenta.');
            cgeEq(cgeLedgers($pdo), $ledgers, 'Las fechas no deben alterar pagos, ventas, clientes o suscripciones.');
            cgeEq($model->find($id)['ocupados'], 4, 'Las fechas no deben liberar dispositivos.');
            cgeEq((int) $model->find($id)['activo'], 0, 'Las fechas no deben reactivar la cuenta.');
        }
    });

    cgeTest('credenciales completas con fechas pendientes todavía impiden vender', static function () use ($model, $pdo, $todayString): void {
        $id = cgePending($model);
        cgeCredentials($model, $pdo, $id);
        $snapshot = cgeSnapshot($pdo);
        cgeReject(static fn() => $model->sell($id, 1, $todayString, cgeToken()), 'Vendió una cuenta sin fechas configuradas.');
        cgeEq(cgeSnapshot($pdo), $snapshot, 'La venta rechazada dejó movimientos.');
    });

    cgeTest('revisión pendiente con pagos conserva datos y exige un pago real para recuperar vigencia', static function () use ($model, $pdo, $recent, $todayString): void {
        foreach (['PAGO', 'GRACIA', 'PAGO_REAL'] as $confirmation) {
            $id = cgePaid($model, $recent);
            cgeEq((int) cgeRaw($pdo, $id)['revision_vigencia_pendiente'], 0, 'Crear una cuenta con pago conocido debe confirmar la vigencia.');
            $model->sell($id, 2, $recent, cgeToken());
            cgeLegacy($pdo, $id);
            $knownDates = cgeRaw($pdo, $id);
            $pdo->prepare('UPDATE cuentas_principales SET revision_vigencia_pendiente = 1 WHERE id = :id')->execute(['id' => $id]);
            $pending = cgeRaw($pdo, $id);
            foreach (['fecha_inicio', 'fecha_vencimiento', 'fin_gracia', 'password_cuenta', 'correo'] as $field) {
                cgeEq($pending[$field], $knownDates[$field], 'Marcar revisión debe conservar datos conocidos.');
            }
            cgeEq($model->find($id)['estado_periodo'], 'SIN_PAGO', 'La revisión pendiente debe bloquear la vigencia aunque existan fechas y credenciales.');
            $snapshot = cgeSnapshot($pdo);
            cgeEq($model->importPendingEmails([strtoupper($pending['correo']), $pending['correo']]),
                ['creadas' => 0, 'existentes' => 1, 'ids' => [$id]], 'Reimportar debe identificar la cuenta pendiente existente.');
            cgeEq(cgeSnapshot($pdo), $snapshot, 'Reimportar no debe quitar la revisión ni modificar los datos existentes.');
            cgeReject(static fn() => $model->sell($id, 1, $todayString, cgeToken()), 'Vendió una cuenta pendiente de revisión con fechas completas.');
            cgeEq(cgeSnapshot($pdo), $snapshot, 'La revisión pendiente debe rechazar ventas sin movimientos.');
            $ledgers = cgeLedgers($pdo);
            $existingSales = $model->sales($id);
            $legacy = cgeSnapshot($pdo, ['clientes', 'modalidades', 'suscripciones']);
            $payments = $model->payments($id);
            if ($confirmation === 'PAGO_REAL') {
                $model->registerPayment($id, $todayString, '211.35', cgeToken());
                $afterPayments = $model->payments($id);
                cgeEq(count($afterPayments), count($payments) + 1, 'Confirmar con pago real debe registrar exactamente un pago nuevo.');
                cgeEq($afterPayments[0]['fecha_pago'], $todayString, 'El nuevo pago debe guardar su fecha real.');
                cgeEq($afterPayments[0]['monto_bs'], '211.35', 'El nuevo pago debe guardar su monto real.');
                cgeEq(array_slice($afterPayments, 1), $payments, 'Confirmar con un pago real debe conservar el historial previo.');
            } else {
                cgeReject(static fn() => $model->configureExistingPeriod($id, $confirmation, $recent), 'Una revisión pendiente no permite alterar fechas de pagos existentes.');
                cgeEq(cgeSnapshot($pdo), $snapshot, 'La revisión rechazada debe conservar todos los datos.');
                cgeEq(cgeLedgers($pdo), $ledgers, 'La revisión rechazada debe preservar todos los movimientos y clientes.');
                continue;
            }
            $confirmed = cgeRaw($pdo, $id);
            cgeEq((int) $confirmed['revision_vigencia_pendiente'], 0, 'La confirmación debe quitar la revisión pendiente.');
            cgeEq(cgeWithoutDates($confirmed), cgeWithoutDates($pending), 'La confirmación debe conservar los demás datos de la cuenta.');
            cgeEq($model->find($id)['ocupados'], 4, 'La confirmación no debe liberar dispositivos anteriores.');
            cgeEq($model->sales($id), $existingSales, 'La confirmación no debe alterar ventas anteriores.');
            cgeEq(cgeSnapshot($pdo, ['clientes', 'modalidades', 'suscripciones']), $legacy, 'La confirmación no debe alterar clientes o suscripciones.');
            $model->sell($id, 1, $todayString, cgeToken());
            cgeEq($model->find($id)['ocupados'], 5, 'La cuenta confirmada debe permitir una venta nueva.');
        }
    });

    cgeTest('fechas válidas no permiten vender sin contraseña entregable', static function () use ($model, $pdo, $todayString): void {
        $id = cgePending($model);
        $model->configureExistingPeriod($id, 'PAGO', $todayString);
        $update = $pdo->prepare('UPDATE cuentas_principales SET password_cuenta = :password WHERE id = :id');
        foreach ([null, '', '   ', 'enc:invalid-fixture'] as $password) {
            $update->execute(['password' => $password, 'id' => $id]);
            $snapshot = cgeSnapshot($pdo);
            cgeReject(static fn() => $model->sell($id, 1, $todayString, cgeToken()), 'Vendió una cuenta sin contraseña entregable.');
            cgeEq(cgeSnapshot($pdo), $snapshot, 'Una contraseña no entregable dejó una venta.');
        }
    });

    cgeTest('cifrado de otra clave e email inválido rechazan ventas sin movimientos', static function () use ($model, $pdo, $todayString): void {
        $id = cgePending($model);
        $model->configureExistingPeriod($id, 'PAGO', $todayString);
        // Cifrado válido con una clave de fixture distinta; evita depender de padding azaroso.
        $wrongKeySecret = '';
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $iv = random_bytes(16);
            $cipher = openssl_encrypt('fixture-other-key-' . $attempt, 'aes-256-cbc',
                hash('sha256', 'fixture-unrelated-test-key', true), OPENSSL_RAW_DATA, $iv);
            cgeCheck(is_string($cipher), 'No pudo prepararse el cifrado fixture.');
            $wrongKeySecret = 'enc:' . base64_encode($iv . $cipher);
            if (decrypt_secret($wrongKeySecret) === '') {
                break;
            }
        }
        cgeEq(decrypt_secret($wrongKeySecret), '', 'El fixture debe ser ilegible con la clave del runner.');
        $pdo->prepare('UPDATE cuentas_principales SET password_cuenta = :password WHERE id = :id')->execute(['password' => $wrongKeySecret, 'id' => $id]);
        $snapshot = cgeSnapshot($pdo);
        cgeReject(static fn() => $model->sell($id, 1, $todayString, cgeToken()), 'Vendió credenciales cifradas con otra clave.');
        cgeEq(cgeSnapshot($pdo), $snapshot, 'Una clave incorrecta dejó una venta.');
        cgeCredentials($model, $pdo, $id);
        $pdo->prepare('UPDATE cuentas_principales SET correo = :email WHERE id = :id')->execute(['email' => 'invalid-fixture-email', 'id' => $id]);
        $snapshot = cgeSnapshot($pdo);
        cgeReject(static fn() => $model->sell($id, 1, $todayString, cgeToken()), 'Vendió una cuenta sin email válido.');
        cgeEq(cgeSnapshot($pdo), $snapshot, 'Un correo inválido dejó una venta.');
    });

    cgeTest('gracia sin inicio pagado permite venta actual y rechaza fechas anteriores al ancla', static function () use ($model, $pdo, $today, $todayString): void {
        $id = cgePending($model);
        cgeCredentials($model, $pdo, $id);
        $model->configureExistingPeriod($id, 'GRACIA', $todayString);
        $previous = $today->modify('-1 day')->format('Y-m-d');
        $snapshot = cgeSnapshot($pdo);
        cgeReject(static fn() => $model->sell($id, 1, $previous, cgeToken()), 'Aceptó una venta anterior al inicio conocido de gracia.');
        cgeReject(static fn() => $model->registerPayment($id, $previous, '199.99', cgeToken()), 'Aceptó un pago que retrocede el inicio conocido de gracia.');
        cgeEq(cgeSnapshot($pdo), $snapshot, 'Las fechas anteriores al ancla dejaron movimientos.');
        $model->selectDistribution($id);
        $saleId = $model->sell($id, 2, $todayString, cgeToken());
        $sale = array_column($model->sales($id), null, 'id')[$saleId];
        cgeEq($sale['fecha_vencimiento'], shift_months_clamped($today, 1)->format('Y-m-d'), 'La venta en gracia debe vencer al terminar la gracia configurada.');
        cgeEq($model->find($id)['estado_periodo'], 'GRACIA', 'Debe mostrar la gracia vigente sin inicio pagado.');
        cgeEq($model->find($id)['ocupados'], 2, 'La venta válida debe ocupar dos dispositivos.');
        cgeEq(count($model->payments($id)), 0, 'Una venta en gracia no debe crear un pago desconocido.');
    });

    cgeTest('pago real posterior registra solo su monto y fecha sin liberar los cupos existentes', static function () use ($model, $pdo, $recent, $todayString): void {
        $id = cgePending($model);
        cgeCredentials($model, $pdo, $id);
        $model->configureExistingPeriod($id, 'GRACIA', $recent);
        $model->selectDistribution($id);
        $model->sell($id, 2, $todayString, cgeToken());
        cgeLegacy($pdo, $id);
        cgeEq(count($model->payments($id)), 0, 'Las fechas manuales no deben inventar pagos previos.');
        $sales = $model->sales($id);
        $legacy = cgeSnapshot($pdo, ['clientes', 'modalidades', 'suscripciones']);
        $token = cgeToken();
        $paymentId = $model->registerPayment($id, $todayString, '210.25', $token);
        cgeEq($model->registerPayment($id, $todayString, '210.25', $token), $paymentId, 'El pago real debe conservar la idempotencia.');
        $payments = $model->payments($id);
        cgeEq(count($payments), 1, 'Solo debe existir el pago real, una vez.');
        cgeEq((int) $payments[0]['id'], $paymentId, 'Debe devolver el id del pago real.');
        cgeEq($payments[0]['monto_bs'], '210.25', 'Debe guardar el monto real de esta operación.');
        cgeEq($payments[0]['monto_usd'], '20.00', 'El pago real conserva el costo base de ChatGPT.');
        $dates = ChatgptCuenta::paymentDates($todayString);
        foreach ($dates as $field => $value) {
            cgeEq($payments[0][$field], $value, 'El pago real debe guardar únicamente sus propias fechas.');
        }
        $account = $model->find($id);
        cgeEq($account['fecha_inicio'], $todayString, 'El pago real debe establecer el inicio conocido.');
        cgeEq($account['fecha_vencimiento'], $dates['fin_pagado'], 'El pago real debe renovar el mes pagado.');
        cgeEq($account['fin_gracia'], $dates['fin_gracia'], 'El pago real debe renovar la gracia.');
        cgeEq((int) $account['revision_vigencia_pendiente'], 0, 'El pago real debe dejar la vigencia confirmada.');
        cgeEq($account['ocupados'], 4, 'El pago real no debe liberar cupos vendidos o heredados.');
        cgeEq($model->sales($id), $sales, 'El pago real no debe modificar las ventas existentes.');
        cgeEq(cgeSnapshot($pdo, ['clientes', 'modalidades', 'suscripciones']), $legacy, 'El pago real no debe modificar clientes o suscripciones.');
    });

    cgeTest('reintento de venta liberada devuelve el registro aunque falten credenciales y fechas', static function () use ($model, $pdo, $todayString): void {
        $id = cgePaid($model, $todayString);
        $token = cgeToken();
        $saleId = $model->sell($id, 2, $todayString, $token);
        cgeEq($model->startCycle($id, true, 'fixture-runner', cgeToken(), $model->currentCycleId($id)), 2, 'Debe liberar los dispositivos fixture.');
        $pdo->prepare('UPDATE cuentas_principales SET password_cuenta = NULL, correo = :email, activo = 0, fecha_inicio = NULL, fecha_vencimiento = NULL, fin_gracia = NULL, revision_vigencia_pendiente = 1 WHERE id = :id')
            ->execute(['email' => 'invalid-fixture-email', 'id' => $id]);
        $snapshot = cgeSnapshot($pdo);
        cgeEq($model->sell($id, 2, $todayString, $token), $saleId, 'El token original debe devolver la venta liberada antes de exigir credenciales actuales.');
        cgeEq(cgeSnapshot($pdo), $snapshot, 'El reintento no debe duplicar movimientos ni reocupar dispositivos.');
        cgeEq($model->find($id)['ocupados'], 0, 'El reintento de venta liberada debe mantener cero dispositivos.');
        cgeReject(static fn() => $model->sell($id, 2, $todayString, cgeToken()), 'Un token nuevo no debe heredar el permiso del reintento.');
        cgeEq(cgeSnapshot($pdo), $snapshot, 'La nueva venta rechazada no debe escribir nada.');
    });
} catch (Throwable $exception) {
    $failed++;
    echo 'FAIL integración aislada: ' . $exception->getMessage() . PHP_EOL;
} finally {
    if ($databaseCreated && $server instanceof PDO) {
        // Solo borra el nombre validado que este mismo runner acaba de crear.
        $server->exec('DROP DATABASE `' . $testDatabase . '`');
    }
}
echo "Resultado: {$passed} correctas, {$failed} fallidas.\n";
exit($failed === 0 ? 0 : 1);
