<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once APP_PATH . '/config/db.php';
require_once APP_PATH . '/config/migrations.php';

run_schema_migrations();

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
use App\Controllers\DashboardController;
use App\Controllers\PlataformasController;
use App\Controllers\SuscripcionesController;
use App\Controllers\TiposSuscripcionController;
use App\Core\AuthMiddleware;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = request_path();

AuthMiddleware::handle($path);

$routes = [
    ['GET', '#^/$#', [DashboardController::class, 'index']],
    ['GET', '#^/dashboard$#', [DashboardController::class, 'index']],
    ['GET', '#^/suscripciones/whatsapp/(\d+)$#', [DashboardController::class, 'whatsapp']],
    ['POST', '#^/suscripciones/renovar/(\d+)$#', [DashboardController::class, 'renovar']],
    ['POST', '#^/suscripciones/no-renovo/(\d+)$#', [DashboardController::class, 'noRenovo']],

    ['GET', '#^/clientes$#', [ClientesController::class, 'index']],
    ['POST', '#^/clientes$#', [ClientesController::class, 'store']],
    ['GET', '#^/clientes/editar/(\d+)$#', [ClientesController::class, 'edit']],
    ['POST', '#^/clientes/actualizar/(\d+)$#', [ClientesController::class, 'update']],
    ['POST', '#^/clientes/eliminar/(\d+)$#', [ClientesController::class, 'destroy']],

    ['GET', '#^/plataformas$#', [PlataformasController::class, 'index']],
    ['POST', '#^/plataformas$#', [PlataformasController::class, 'store']],
    ['GET', '#^/plataformas/editar/(\d+)$#', [PlataformasController::class, 'edit']],
    ['POST', '#^/plataformas/actualizar/(\d+)$#', [PlataformasController::class, 'update']],
    ['POST', '#^/plataformas/eliminar/(\d+)$#', [PlataformasController::class, 'destroy']],

    ['GET', '#^/tipos-suscripcion$#', [TiposSuscripcionController::class, 'index']],
    ['POST', '#^/tipos-suscripcion$#', [TiposSuscripcionController::class, 'store']],
    ['GET', '#^/tipos-suscripcion/editar/(\d+)$#', [TiposSuscripcionController::class, 'edit']],
    ['POST', '#^/tipos-suscripcion/actualizar/(\d+)$#', [TiposSuscripcionController::class, 'update']],
    ['POST', '#^/tipos-suscripcion/eliminar/(\d+)$#', [TiposSuscripcionController::class, 'destroy']],

    // Compatibilidad legacy
    ['GET', '#^/modalidades$#', [TiposSuscripcionController::class, 'index']],
    ['POST', '#^/modalidades$#', [TiposSuscripcionController::class, 'store']],
    ['GET', '#^/modalidades/editar/(\d+)$#', [TiposSuscripcionController::class, 'edit']],
    ['POST', '#^/modalidades/actualizar/(\d+)$#', [TiposSuscripcionController::class, 'update']],
    ['POST', '#^/modalidades/eliminar/(\d+)$#', [TiposSuscripcionController::class, 'destroy']],

    ['GET', '#^/suscripciones$#', [SuscripcionesController::class, 'index']],
    ['POST', '#^/suscripciones$#', [SuscripcionesController::class, 'store']],
    ['GET', '#^/suscripciones/editar/(\d+)$#', [SuscripcionesController::class, 'edit']],
    ['POST', '#^/suscripciones/actualizar/(\d+)$#', [SuscripcionesController::class, 'update']],
    ['POST', '#^/suscripciones/eliminar/(\d+)$#', [SuscripcionesController::class, 'destroy']],

    ['GET', '#^/login$#', [AuthController::class, 'showLogin']],
    ['POST', '#^/login$#', [AuthController::class, 'login']],
    ['GET', '#^/logout$#', [AuthController::class, 'logout']],
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
