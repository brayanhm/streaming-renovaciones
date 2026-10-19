<?php
declare(strict_types=1);

/** php tests/modals.php [--preview]. Solo esquema y datos ficticios en una base efímera propia. */
$database = 'ghost_modal_test_' . bin2hex(random_bytes(8));
foreach (['DB_NAME' => $database, 'APP_RUN_MIGRATIONS' => '0', 'APP_KEY' => 'modal-tests-only-key'] as $key => $value) {
    putenv($key . '=' . $value); $_ENV[$key] = $value; $_SERVER[$key] = $value;
}
require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/config/db.php';
require APP_PATH . '/config/migrations.php';
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $parts = explode('\\', substr($class, 4)); $parts[0] = strtolower($parts[0]);
    require_once APP_PATH . '/' . implode('/', $parts) . '.php';
});
$server = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false; $process = null; $temporary = null; $pipes = []; $passed = 0; $failed = 0;
$check = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); };
$test = static function (string $name, callable $run) use (&$passed, &$failed): void {
    try { $run(); ++$passed; echo "OK   {$name}\n"; }
    catch (Throwable $error) { ++$failed; echo 'FAIL ' . $name . ': ' . $error->getMessage() . "\n"; }
};
try {
    $server->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); $created = true;
    $pdo = db();
    // Extraer solo DDL. Ningún INSERT, dato personal o credencial del volcado se importa.
    $schema = file_get_contents(BASE_PATH . '/streaming_renovaciones.sql');
    preg_match_all('/^(?:CREATE|ALTER) TABLE `([a-z0-9_]+)`[^;]+;/m', $schema, $statements, PREG_SET_ORDER);
    $tables = ['auditoria','clientes','cuentas_principales','login_intentos','modalidades','movimientos','plataformas','suscripciones','usuarios'];
    foreach ($statements as $statement) if (in_array($statement[1], $tables, true)) $pdo->exec($statement[0]);
    run_schema_migrations();
    $pdo->exec("INSERT INTO usuarios (id, username, password_hash, rol, activo) VALUES (1,'demo_modal','fixture-no-login','admin',1)");
    $pdo->exec("INSERT INTO clientes (id,nombre,telefono,notas) VALUES (1,'Cliente demostración','59170000000','Datos ficticios')");
    foreach ([App\Models\ChatgptCuenta::class, App\Models\ClaudeCuenta::class] as $class) {
        $model = new $class($pdo);
        $id = $model->create(['etiqueta' => 'Cuenta demostración', 'correo' => strtolower((new ReflectionClass($class))->getShortName()) . '@example.invalid',
            'password_cuenta' => 'fixture-only', 'fecha_pago' => date('Y-m-d'), 'monto_bs' => '100']);
        $model->selectDistribution($id);
    }
    $temporary = sys_get_temp_dir() . '/ghost-modal-test-' . bin2hex(random_bytes(6));
    mkdir($temporary);
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($socket, false); fclose($socket);
    $process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . $temporary, '-S', $address, '-t', PUBLIC_PATH, __DIR__ . '/support/modal_router.php'],
        [0 => ['pipe','r'], 1 => ['file',$temporary . '/server.log','a'], 2 => ['file',$temporary . '/server.log','a']], $pipes, BASE_PATH);
    $check(is_resource($process), 'No pudo iniciarse el servidor aislado.');
    for ($attempt = 0; $attempt < 40; ++$attempt) {
        $ready = @stream_socket_client('tcp://' . $address, $errno, $errstr, .1);
        if ($ready) { fclose($ready); break; }
        usleep(50000);
    }
    $base = 'http://' . $address; $cookie = '';
    $request = static function (string $path, ?array $data = null, bool $modal = false) use ($base, &$cookie): array {
        $headers = ['Connection: close'];
        if ($cookie !== '') $headers[] = 'Cookie: ' . $cookie;
        if ($modal) $headers = array_merge($headers, ['X-GS-Modal: 1','Accept: application/json']);
        if ($data !== null) $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $context = stream_context_create(['http' => ['method' => $data === null ? 'GET' : 'POST', 'header' => implode("\r\n", $headers),
            'content' => $data === null ? '' : http_build_query($data), 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10]]);
        $body = file_get_contents($base . $path, false, $context);
        $responseHeaders = $http_response_header;
        foreach ($responseHeaders as $header) if (preg_match('/^Set-Cookie: (gs_modal_test=[^;]+)/i', $header, $match)) $cookie = $match[1];
        preg_match('/\s(\d{3})\s/', $responseHeaders[0], $match);
        return [(int) $match[1], (string) $body, $responseHeaders];
    };
    [$code, $html] = $request('/plataformas?q=Modal');
    preg_match('/name="_csrf" value="([^"]+)"/', $html, $match); $csrf = $match[1] ?? '';
    $test('GET conserva filtros y ofrece creación local y edición en modal', static function () use ($code,$html,$csrf,$check): void {
        $check($code === 200 && $csrf !== '', 'No se renderizó el listado autenticado.');
        $check(str_contains($html, 'id="gs-form-modal"') && str_contains($html,'data-open-panel="nueva-plataforma"'), 'Faltan modal o disparador.');
    });
    $payload = ['_csrf' => $csrf, 'nombre' => 'Modal demostración', 'tipo_servicio' => 'RENOVABLE', 'dato_renovacion' => 'USUARIO', 'duraciones_disponibles' => '1,3'];
    $test('POST inválido devuelve campos y conserva el formulario sin redirección HTTP', static function () use ($request,$payload,$check): void {
        [$code,$body,$headers] = $request('/plataformas', array_replace($payload,['duraciones_disponibles' => '0,abc']), true);
        $result = json_decode($body, true);
        $check($code === 422 && $result['ok'] === false && isset($result['fields']['duraciones_disponibles']), 'No devolvió la validación del campo.');
        $check(!preg_grep('/^Location:/i',$headers), 'El modal no debe redirigir ante errores.');
        $check(!str_contains($body,'"_csrf"') && !str_contains($body,'"_old"'), 'La respuesta expuso datos del formulario.');
    });
    $test('crear en modal guarda una sola plataforma y conserva el aviso para el listado', static function () use ($request,$payload,$pdo,$check): void {
        [$code,$body] = $request('/plataformas',$payload,true); $result = json_decode($body,true);
        $check($code === 200 && $result['ok'] === true,'No se guardó la plataforma.');
        $check((int)$pdo->query("SELECT COUNT(*) FROM plataformas WHERE nombre='Modal demostración'")->fetchColumn() === 1,'Cantidad incorrecta.');
        [$code,$html] = $request('/plataformas?q=Modal');
        $check(str_contains($html,'data-flash-type="success"') && !str_contains($html,'id="form-feedback-data"'),'Se perdió el aviso o quedó validación antigua.');
    });
    $platform = (int)$pdo->query("SELECT id FROM plataformas WHERE nombre='Modal demostración'")->fetchColumn();
    $plan = (int)$pdo->query('SELECT id FROM modalidades WHERE plataforma_id='.$platform.' LIMIT 1')->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO suscripciones (cliente_id,plataforma_id,modalidad_id,fecha_inicio,fecha_vencimiento,usuario_proveedor) VALUES (1,?,?,?,?,'usuario_demostracion')");
    $stmt->execute([$platform,$plan,date('Y-m-d'),date('Y-m-d',strtotime('+1 month'))]);
    $subscription = (int)$pdo->lastInsertId();
    $test('editar carga todos los formularios admitidos con un contenedor identificable', static function () use ($request,$platform,$pdo,$subscription,$check): void {
        $accounts = $pdo->query('SELECT id,servicio FROM cuentas_principales')->fetchAll();
        $plan = (int)$pdo->query('SELECT id FROM modalidades LIMIT 1')->fetchColumn();
        $paths = ['/clientes/editar/1','/usuarios/editar/1','/plataformas/editar/'.$platform,'/tipos-suscripcion/editar/'.$plan,
            '/suscripciones/editar/'.$subscription,'/tipos-suscripcion/precios?plataforma_id='.$platform];
        foreach ($accounts as $account) $paths[] = '/'.strtolower($account['servicio']).'/'.$account['id'].'/editar';
        foreach ($paths as $path) {
            [$code,$body] = $request($path);
            $check($code === 200 && str_contains($body,'data-modal-page') && str_contains($body,'method="post"'), 'Edición no disponible: '.$path);
        }
    });
    $test('asignar vigencia desde clientes ofrece el cliente seleccionado y su inicializador', static function () use ($request,$check): void {
        [$code,$html] = $request('/suscripciones?cliente_id=1');
        $check($code === 200 && str_contains($html,'data-form-behavior="create-subscription"'),'Falta el comportamiento del formulario remoto.');
        $check((bool)preg_match('/value="1"\s+selected/', $html),'No se preseleccionó el cliente.');
    });
    $test('editar desde modal actualiza y los envíos normales conservan su redirección', static function () use ($request,$payload,$platform,$pdo,$check): void {
        [$code,$body] = $request('/plataformas/actualizar/'.$platform, array_replace($payload,['nombre'=>'Modal editado']),true);
        $check($code === 200 && json_decode($body,true)['ok'],'Edición fallida.');
        $check($pdo->query('SELECT nombre FROM plataformas WHERE id='.$platform)->fetchColumn() === 'Modal editado','El nombre no cambió.');
        [$code,,$headers] = $request('/plataformas/actualizar/'.$platform,$payload);
        $check($code === 302 && (bool)preg_grep('/^Location:/i',$headers),'Se alteró el flujo sin modal.');
    });
    $test('CSRF inválido se rechaza sin guardar', static function () use ($request,$payload,$pdo,$check): void {
        [$code,$body] = $request('/plataformas',array_replace($payload,['_csrf'=>'invalido','nombre'=>'No guardar']),true);
        $check($code === 419 && !json_decode($body,true)['ok'],'Se aceptó el CSRF inválido.');
        $check((int)$pdo->query("SELECT COUNT(*) FROM plataformas WHERE nombre='No guardar'")->fetchColumn() === 0,'Se guardó el formulario rechazado.');
    });
    $test('respuesta de sesión expirada requiere iniciar sesión y no expone campos enviados', static function () use ($check): void {
        $_SESSION['_old'] = ['new_password'=>'secreto-de-prueba'];
        flash('warning','Debes iniciar sesión.');
        $result = modal_form_result('/login');
        $check(!$result['ok'] && $result['login'] && $result['redirect'] === url('/login'),'Respuesta de sesión incorrecta.');
        $check(!isset($_SESSION['_old']) && !str_contains(json_encode($result),'secreto-de-prueba'),'La respuesta filtró el contenido enviado.');
    });
    require __DIR__ . '/modals_extended.php';
    require __DIR__ . '/uiux.php';
    echo "Resultado: {$passed} correctas, {$failed} fallidas.\n";
    if (in_array('--preview',$argv,true) && $failed === 0) {
        echo 'Vista de prueba: '.$base.'/plataformas?q=Modal' . "\nPresiona Enter para cerrar y eliminar la base de pruebas.\n";
        fgets(STDIN);
    }
} finally {
    if (is_resource($process)) { foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe); proc_terminate($process); proc_close($process); }
    if ($created) $server->exec('DROP DATABASE `' . $database . '`');
    if ($temporary !== null && is_dir($temporary)) {
        foreach (glob($temporary.'/*') as $file) if (is_file($file)) unlink($file);
        rmdir($temporary);
    }
}
exit($failed === 0 ? 0 : 1);
