<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/html; charset=UTF-8');
}

require_once __DIR__ . '/../app/config/config.php';
require_once APP_PATH . '/config/db.php';
require_once APP_PATH . '/config/migrations.php';
require_once APP_PATH . '/config/chatgpt_migrations.php';
require_once APP_PATH . '/config/claude_migrations.php';

if (APP_RUN_MIGRATIONS) {
    run_schema_migrations();
}

spl_autoload_register(static function (string $className): void {
    $prefix = 'App\\';

    if (strncmp($className, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($className, strlen($prefix));
    $parts = explode('\\', $relativeClass);

    if (isset($parts[0])) {
        $parts[0] = strtolower($parts[0]);
    }

    $filePath = APP_PATH . '/' . implode('/', $parts) . '.php';

    if (is_file($filePath)) {
        require_once $filePath;
    }
});

use App\Controllers\AuthController;
use App\Controllers\ClientesController;
use App\Controllers\ChatgptController;
use App\Controllers\ClaudeController;
use App\Controllers\CuentasPrincipalesController;
use App\Controllers\DashboardController;
use App\Controllers\ImportacionesController;
use App\Controllers\PlataformasController;
use App\Controllers\ReportesController;
use App\Controllers\SuscripcionesController;
use App\Controllers\TiposSuscripcionController;
use App\Controllers\UsuariosController;
use App\Core\AuthMiddleware;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = request_path();

AuthMiddleware::handle($path);

if ($method === 'POST' && !verify_csrf_token(isset($_POST['_csrf']) ? (string) $_POST['_csrf'] : null)) {
    if (str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
        http_response_code(419);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => false, 'message' => 'La sesión o el formulario expiró. Recarga la página e intenta otra vez.']);
        exit;
    }
    flash('danger', 'La sesion expiro o el formulario no es valido. Intenta nuevamente.');
    redirect($path === '/login' ? '/login' : '/dashboard');
}

$routes = [
    ['GET', '#^/$#', [DashboardController::class, 'index']],
    ['GET', '#^/dashboard$#', [DashboardController::class, 'index']],
    ['GET', '#^/dashboard-ia$#', [ChatgptController::class, 'legacyIndex']],
    ['GET', '#^/contactar$#', [DashboardController::class, 'contactar']],
    ['GET', '#^/contactar-ia$#', [ChatgptController::class, 'legacyIndex']],
    ['POST', '#^/suscripciones/whatsapp/(\d+)$#', [DashboardController::class, 'whatsapp']],
    ['POST', '#^/suscripciones/renovar/(\d+)$#', [DashboardController::class, 'renovar']],
    ['POST', '#^/suscripciones/no-renovo/(\d+)$#', [DashboardController::class, 'noRenovo']],

    ['GET', '#^/chatgpt$#', [ChatgptController::class, 'index']],
    ['GET', '#^/chatgpt/vencimientos$#', [ChatgptController::class, 'vencimientos']],
    ['POST', '#^/chatgpt/avisos$#', [ChatgptController::class, 'saveAlerts']],
    ['POST', '#^/chatgpt$#', [ChatgptController::class, 'store']],
    ['POST', '#^/chatgpt/importar-cuentas$#', [ChatgptController::class, 'importPending']],
    ['GET', '#^/chatgpt/plantillas$#', [ChatgptController::class, 'templates']],
    ['GET', '#^/chatgpt/distribucion$#', [ChatgptController::class, 'distribution']],
    ['POST', '#^/chatgpt/(\d+)/entregar$#', [ChatgptController::class, 'deliver']],
    ['GET', '#^/chatgpt/(\d+)/editar$#', [ChatgptController::class, 'edit']],
    ['GET', '#^/chatgpt/(\d+)/ocupacion$#', [ChatgptController::class, 'occupationForm']],
    ['GET', '#^/chatgpt/(\d+)/historial-distribucion$#', [ChatgptController::class, 'distributionHistory']],
    ['POST', '#^/chatgpt/(\d+)/actualizar$#', [ChatgptController::class, 'update']],
    ['POST', '#^/chatgpt/(\d+)/ocupacion$#', [ChatgptController::class, 'adjustOccupation']],
    ['POST', '#^/chatgpt/(\d+)/pagar$#', [ChatgptController::class, 'pagar']],
    ['POST', '#^/chatgpt/(\d+)/vigencia$#', [ChatgptController::class, 'configurePeriod']],
    ['POST', '#^/chatgpt/(\d+)/vender$#', [ChatgptController::class, 'vender']],
    ['POST', '#^/chatgpt/(\d+)/distribuir$#', [ChatgptController::class, 'selectDistribution']],
    ['POST', '#^/chatgpt/(\d+)/ciclo$#', [ChatgptController::class, 'startCycle']],
    ['POST', '#^/chatgpt/(\d+)/plan$#', [ChatgptController::class, 'setPlan']],
    ['POST', '#^/chatgpt/(\d+)/estado$#', [ChatgptController::class, 'toggleActive']],
    ['GET', '#^/chatgpt/(\d+)$#', [ChatgptController::class, 'show']],
    ['GET', '#^/claude$#', [ClaudeController::class, 'index']],
    ['POST', '#^/claude$#', [ClaudeController::class, 'store']],
    ['GET', '#^/claude/plantillas$#', [ClaudeController::class, 'templates']],
    ['GET', '#^/claude/distribucion$#', [ClaudeController::class, 'distribution']],
    ['POST', '#^/claude/(\d+)/entregar$#', [ClaudeController::class, 'deliver']],
    ['GET', '#^/claude/(\d+)/editar$#', [ClaudeController::class, 'edit']],
    ['GET', '#^/claude/(\d+)/ocupacion$#', [ClaudeController::class, 'occupationForm']],
    ['GET', '#^/claude/(\d+)/historial-distribucion$#', [ClaudeController::class, 'distributionHistory']],
    ['POST', '#^/claude/(\d+)/actualizar$#', [ClaudeController::class, 'update']],
    ['POST', '#^/claude/(\d+)/ocupacion$#', [ClaudeController::class, 'adjustOccupation']],
    ['POST', '#^/claude/(\d+)/pagar$#', [ClaudeController::class, 'pagar']],
    ['POST', '#^/claude/(\d+)/vender$#', [ClaudeController::class, 'vender']],
    ['POST', '#^/claude/(\d+)/distribuir$#', [ClaudeController::class, 'selectDistribution']],
    ['POST', '#^/claude/(\d+)/ciclo$#', [ClaudeController::class, 'startCycle']],
    ['POST', '#^/claude/(\d+)/plan$#', [ClaudeController::class, 'setPlan']],
    ['POST', '#^/claude/(\d+)/estado$#', [ClaudeController::class, 'toggleActive']],
    ['GET', '#^/claude/(\d+)$#', [ClaudeController::class, 'show']],

    ['GET', '#^/clientes$#', [ClientesController::class, 'index']],
    ['POST', '#^/clientes$#', [ClientesController::class, 'store']],
    ['POST', '#^/clientes/antiguo$#', [ClientesController::class, 'storeAntiguo']],
    ['GET', '#^/clientes/completar$#', [ClientesController::class, 'completar']],
    ['GET', '#^/clientes/completar/(\d+)$#', [ClientesController::class, 'completeContactForm']],
    ['POST', '#^/clientes/completar/actualizar/(\d+)$#', [ClientesController::class, 'updateMissingContact']],
    ['GET', '#^/clientes/editar/(\d+)$#', [ClientesController::class, 'edit']],
    ['GET', '#^/clientes/(\d+)$#', [ClientesController::class, 'show']],
    ['POST', '#^/clientes/actualizar/(\d+)$#', [ClientesController::class, 'update']],
    ['POST', '#^/clientes/eliminar/(\d+)$#', [ClientesController::class, 'destroy']],

    ['GET', '#^/plataformas$#', [PlataformasController::class, 'index']],
    ['POST', '#^/plataformas$#', [PlataformasController::class, 'store']],
    ['GET', '#^/plataformas/editar/(\d+)$#', [PlataformasController::class, 'edit']],
    ['POST', '#^/plataformas/actualizar/(\d+)$#', [PlataformasController::class, 'update']],
    ['POST', '#^/plataformas/eliminar/(\d+)$#', [PlataformasController::class, 'destroy']],

    ['GET', '#^/tipos-suscripcion$#', [TiposSuscripcionController::class, 'index']],
    ['POST', '#^/tipos-suscripcion$#', [TiposSuscripcionController::class, 'store']],
    ['GET', '#^/tipos-suscripcion/precios$#', [TiposSuscripcionController::class, 'precios']],
    ['POST', '#^/tipos-suscripcion/precios$#', [TiposSuscripcionController::class, 'updatePrecios']],
    ['GET', '#^/tipos-suscripcion/editar/(\d+)$#', [TiposSuscripcionController::class, 'edit']],
    ['POST', '#^/tipos-suscripcion/actualizar/(\d+)$#', [TiposSuscripcionController::class, 'update']],
    ['POST', '#^/tipos-suscripcion/eliminar/(\d+)$#', [TiposSuscripcionController::class, 'destroy']],

    // Compatibilidad legacy
    ['GET', '#^/modalidades$#', [TiposSuscripcionController::class, 'index']],
    ['POST', '#^/modalidades$#', [TiposSuscripcionController::class, 'store']],
    ['GET', '#^/modalidades/precios$#', [TiposSuscripcionController::class, 'precios']],
    ['POST', '#^/modalidades/precios$#', [TiposSuscripcionController::class, 'updatePrecios']],
    ['GET', '#^/modalidades/editar/(\d+)$#', [TiposSuscripcionController::class, 'edit']],
    ['POST', '#^/modalidades/actualizar/(\d+)$#', [TiposSuscripcionController::class, 'update']],
    ['POST', '#^/modalidades/eliminar/(\d+)$#', [TiposSuscripcionController::class, 'destroy']],

    ['GET', '#^/suscripciones$#', [SuscripcionesController::class, 'index']],
    ['POST', '#^/suscripciones$#', [SuscripcionesController::class, 'store']],
    ['GET', '#^/suscripciones/historial/(\d+)$#', [SuscripcionesController::class, 'historial']],
    ['GET', '#^/suscripciones/renovar/(\d+)$#', [SuscripcionesController::class, 'renewalForm']],
    ['GET', '#^/suscripciones/finalizacion/(\d+)$#', [SuscripcionesController::class, 'dueDateForm']],
    ['GET', '#^/suscripciones/editar/(\d+)$#', [SuscripcionesController::class, 'edit']],
    ['POST', '#^/suscripciones/actualizar/(\d+)$#', [SuscripcionesController::class, 'update']],
    ['POST', '#^/suscripciones/finalizacion/(\d+)$#', [SuscripcionesController::class, 'updateDueDate']],
    ['POST', '#^/suscripciones/eliminar/(\d+)$#', [SuscripcionesController::class, 'destroy']],

    ['GET', '#^/reportes$#', [ReportesController::class, 'index']],
    ['GET', '#^/reportes/exportar$#', [ReportesController::class, 'exportarMovimientos']],

    ['GET', '#^/cuentas-principales$#', [CuentasPrincipalesController::class, 'index']],
    ['POST', '#^/cuentas-principales$#', [CuentasPrincipalesController::class, 'store']],
    ['GET', '#^/cuentas-principales/editar/(\d+)$#', [CuentasPrincipalesController::class, 'edit']],
    ['POST', '#^/cuentas-principales/actualizar/(\d+)$#', [CuentasPrincipalesController::class, 'update']],
    ['POST', '#^/cuentas-principales/eliminar/(\d+)$#', [CuentasPrincipalesController::class, 'destroy']],
    ['POST', '#^/cuentas-principales/(\d+)/asignar$#', [CuentasPrincipalesController::class, 'asignar']],
    ['GET', '#^/cuentas-principales/(\d+)$#', [CuentasPrincipalesController::class, 'show']],

    ['GET', '#^/importar$#', [ImportacionesController::class, 'hub']],
    ['GET', '#^/importar/flujotv$#', [ImportacionesController::class, 'flujotvForm']],
    ['POST', '#^/importar/flujotv/preview$#', [ImportacionesController::class, 'flujotvPreview']],
    ['POST', '#^/importar/flujotv/aplicar$#', [ImportacionesController::class, 'flujotvApply']],
    ['GET', '#^/importar/flujotv-dispositivos$#', [ImportacionesController::class, 'disposForm']],
    ['POST', '#^/importar/flujotv-dispositivos/preview$#', [ImportacionesController::class, 'disposPreview']],
    ['POST', '#^/importar/flujotv-dispositivos/aplicar$#', [ImportacionesController::class, 'disposApply']],

    ['GET', '#^/usuarios$#', [UsuariosController::class, 'index']],
    ['GET', '#^/usuarios/auditoria$#', [UsuariosController::class, 'auditoria']],
    ['POST', '#^/usuarios$#', [UsuariosController::class, 'store']],
    ['GET', '#^/usuarios/editar/(\d+)$#', [UsuariosController::class, 'edit']],
    ['POST', '#^/usuarios/actualizar/(\d+)$#', [UsuariosController::class, 'update']],
    ['POST', '#^/usuarios/password/(\d+)$#', [UsuariosController::class, 'resetPassword']],
    ['POST', '#^/usuarios/estado/(\d+)$#', [UsuariosController::class, 'toggleActive']],
    ['POST', '#^/usuarios/eliminar/(\d+)$#', [UsuariosController::class, 'destroy']],

    ['GET', '#^/login$#', [AuthController::class, 'showLogin']],
    ['POST', '#^/login$#', [AuthController::class, 'login']],
    ['GET', '#^/logout$#', [AuthController::class, 'logout']],
    ['GET', '#^/perfil$#', [AuthController::class, 'showProfile']],
    ['POST', '#^/perfil$#', [AuthController::class, 'updateProfile']],
];

foreach ($routes as [$routeMethod, $routePattern, $handler]) {
    if ($method !== $routeMethod) {
        continue;
    }

    if (!preg_match($routePattern, $path, $matches)) {
        continue;
    }

    array_shift($matches);
    [$controllerClass, $action] = $handler;
    $controller = new $controllerClass();

    $params = array_map(static fn (string $item): int|string => ctype_digit($item) ? (int) $item : $item, $matches);
    call_user_func_array([$controller, $action], $params);
    exit;
}

http_response_code(404);
$pageTitle = '404';
$flashMessages = consume_flash();
$currentPath = request_path();
$authUser = auth_user();

require APP_PATH . '/views/layouts/header.php';
echo '<div class="card border-danger"><div class="card-body">';
echo '<h1 class="h4 mb-2">404 - Ruta no encontrada</h1>';
echo '<p class="mb-3">La ruta solicitada no existe.</p>';
echo '<a class="btn btn-primary" href="' . e(url('/dashboard')) . '">Ir al dashboard</a>';
echo '</div></div>';
require APP_PATH . '/views/layouts/footer.php';
