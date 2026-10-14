<?php
declare(strict_types=1);

require_once APP_PATH . '/core/icons.php';

$pageTitle = $pageTitle ?? APP_NAME;
$flashMessages = $flashMessages ?? [];
$currentPath = $currentPath ?? request_path();
$authUser = $authUser ?? auth_user();
$isLoggedIn = $authUser !== null;

if (!function_exists('is_active_menu')) {
    function is_active_menu(string $currentPath, string $menuPath): bool
    {
        return $currentPath === $menuPath || str_starts_with($currentPath, $menuPath . '/');
    }
}

$section = 'dashboard';
if (is_active_menu($currentPath, '/clientes')) {
    $section = 'clientes';
} elseif (is_active_menu($currentPath, '/plataformas')) {
    $section = 'plataformas';
} elseif (is_active_menu($currentPath, '/tipos-suscripcion') || is_active_menu($currentPath, '/modalidades')) {
    $section = 'tipos';
} elseif (is_active_menu($currentPath, '/suscripciones')) {
    $section = 'suscripciones';
} elseif (is_active_menu($currentPath, '/reportes')) {
    $section = 'reportes';
} elseif (is_active_menu($currentPath, '/cuentas-principales') || is_active_menu($currentPath, '/chatgpt') || is_active_menu($currentPath, '/claude')) {
    $section = 'cuentas';
} elseif (is_active_menu($currentPath, '/importar')) {
    $section = 'importar';
} elseif (is_active_menu($currentPath, '/usuarios')) {
    $section = 'usuarios';
} elseif (is_active_menu($currentPath, '/login')) {
    $section = 'auth';
}

$themeVersion = (string) filemtime(PUBLIC_PATH . '/css/theme.css');
$iconsVersion = (string) filemtime(PUBLIC_PATH . '/js/icons.js');
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= e(url('/img/logo.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('/css/theme.css') . '?v=' . $themeVersion) ?>" rel="stylesheet">
    <script src="<?= e(url('/js/icons.js') . '?v=' . $iconsVersion) ?>"></script>
</head>
<body class="app-body<?= $section === 'auth' ? ' app-auth' : '' ?>">
<?php
$navUser = (string) ($authUser['username'] ?? '');
$isAdmin = (string) ($authUser['rol'] ?? '') === 'admin';
$actDash      = is_active_menu($currentPath, '/dashboard') || $currentPath === '/';
$actContactar = $currentPath === '/contactar';
$actChatgpt   = is_active_menu($currentPath, '/chatgpt');
$actClaude    = is_active_menu($currentPath, '/claude') || is_active_menu($currentPath, '/cuentas-principales');
$actClientes  = is_active_menu($currentPath, '/clientes');
$actMembres   = is_active_menu($currentPath, '/suscripciones');
$actCatalogo  = is_active_menu($currentPath, '/plataformas');
$actPlanes    = is_active_menu($currentPath, '/tipos-suscripcion') || is_active_menu($currentPath, '/modalidades');
$actReportes  = is_active_menu($currentPath, '/reportes');
$actImportar  = is_active_menu($currentPath, '/importar');
$actAudit     = $currentPath === '/usuarios/auditoria';
$actUsuarios  = is_active_menu($currentPath, '/usuarios') && !$actAudit;

$sidebarMenu = [
    ['Streaming', [
        ['Panel', url('/dashboard'), $actDash, 'layout-dashboard'],
        ['Contactar hoy', url('/contactar'), $actContactar, 'message-circle'],
        ['Importar cuentas', url('/importar'), $actImportar, 'download'],
    ]],
    ['ChatGPT', [
        ['Cuentas ChatGPT', url('/chatgpt'), $actChatgpt && $currentPath !== '/chatgpt/distribucion', 'bot'],
        ['Cuenta en distribución', url('/chatgpt/distribucion'), $currentPath === '/chatgpt/distribucion', 'copy'],
    ]],
    ['Claude', [
        ['Cuentas Claude', url('/claude'), $actClaude && $currentPath !== '/claude/distribucion', 'brain'],
        ['Cuenta en distribución', url('/claude/distribucion'), $currentPath === '/claude/distribucion', 'copy'],
    ]],
    ['Gestión', [
        ['Clientes', url('/clientes'), $actClientes, 'users'],
        ['Membresías', url('/suscripciones'), $actMembres, 'calendar-clock'],
        ['Reportes', url('/reportes'), $actReportes, 'chart-column'],
    ]],
    ['Catálogo', [
        ['Plataformas', url('/plataformas'), $actCatalogo, 'layers'],
        ['Planes', url('/tipos-suscripcion'), $actPlanes, 'tag'],
    ]],
];
if ($isAdmin) {
    $sidebarMenu[] = ['Administración', [
        ['Usuarios', url('/usuarios'), $actUsuarios, 'user-cog'],
        ['Auditoría', url('/usuarios/auditoria'), $actAudit, 'scroll-text'],
    ]];
}
?>
<?php if ($section === 'auth'): ?>
<main class="container py-4">
<?php else: ?>
<div class="app-topbar">
    <?php if ($isLoggedIn): ?>
    <button class="app-burger d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Menú"><?= icon('menu') ?></button>
    <?php endif; ?>
    <a class="app-brand d-flex align-items-center" href="<?= e(url('/dashboard')) ?>">
        <img src="<?= e(url('/img/logo.png')) ?>" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
        <span><?= e(APP_NAME) ?></span>
    </a>
    <?php if ($isLoggedIn): ?>
    <div class="ms-auto d-flex align-items-center gap-2">
        <a href="<?= e(url('/perfil')) ?>" class="badge text-bg-secondary px-3 py-2 text-decoration-none app-user-badge d-none d-sm-inline"><?= e($navUser) ?></a>
        <a class="btn btn-outline-light btn-sm" href="<?= e(url('/logout')) ?>"><?= icon('log-out') ?><span class="button-label">Salir</span></a>
    </div>
    <?php endif; ?>
</div>
<div class="app-layout">
    <?php if ($isLoggedIn): ?>
    <aside class="offcanvas-lg offcanvas-start app-sidebar" id="appSidebar" tabindex="-1" aria-label="Navegación">
        <div class="offcanvas-header d-lg-none">
            <span class="fw-semibold text-secondary">Menú</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Cerrar"><?= icon('x') ?></button>
        </div>
        <div class="offcanvas-body app-sidebar-body">
            <nav class="app-nav">
                <?php foreach ($sidebarMenu as $gi => [$grupo, $items]): ?>
                    <?php $abierto = false; foreach ($items as $it) { if ($it[2]) { $abierto = true; break; } } ?>
                    <div class="app-nav-group">
                        <button class="app-nav-toggle <?= $abierto ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#grp<?= (int) $gi ?>" aria-expanded="<?= $abierto ? 'true' : 'false' ?>">
                            <span class="grp-lbl"><?= e($grupo) ?></span>
                            <span class="gs-nav-chevron"><?= icon('chevron-down') ?></span>
                        </button>
                        <div class="collapse <?= $abierto ? 'show' : '' ?>" id="grp<?= (int) $gi ?>">
                            <?php foreach ($items as [$lbl, $href, $act, $ico]): ?>
                                <a class="app-nav-link <?= $act ? 'active' : '' ?>" href="<?= e($href) ?>" title="<?= e($lbl) ?>" aria-label="<?= e($lbl) ?>"><span class="ico"><?= icon($ico) ?></span><span class="lbl"><?= e($lbl) ?></span></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>
    <?php endif; ?>
    <main class="app-main">
        <div class="app-content px-3 px-lg-4 py-4">
<?php endif; ?>
    <?php foreach ($flashMessages as $flash): ?>
        <?php $type = (string) ($flash['type'] ?? 'info'); ?>
        <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
            <?= e((string) ($flash['message'] ?? '')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"><?= icon('x') ?></button>
        </div>
    <?php endforeach; ?>

