<?php
declare(strict_types=1);

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
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e(url('/dashboard')) ?>">
            <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php if ($isLoggedIn): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Mostrar navegacion">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu($currentPath, '/dashboard') || $currentPath === '/' ? 'active' : '' ?>" href="<?= e(url('/dashboard')) ?>">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu($currentPath, '/clientes') ? 'active' : '' ?>" href="<?= e(url('/clientes')) ?>">Clientes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu($currentPath, '/plataformas') ? 'active' : '' ?>" href="<?= e(url('/plataformas')) ?>">Plataformas</a>
                    </li>
                    <li class="nav-item">
                        <?php $tiposActive = is_active_menu($currentPath, '/tipos-suscripcion') || is_active_menu($currentPath, '/modalidades'); ?>
                        <a class="nav-link <?= $tiposActive ? 'active' : '' ?>" href="<?= e(url('/tipos-suscripcion')) ?>">Tipos suscripcion</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu($currentPath, '/suscripciones') ? 'active' : '' ?>" href="<?= e(url('/suscripciones')) ?>">Suscripciones</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-bg-secondary px-3 py-2"><?= e((string) ($authUser['username'] ?? '')) ?></span>
                    <a class="btn btn-outline-light btn-sm" href="<?= e(url('/logout')) ?>">Salir</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>
<main class="container py-4">
    <?php foreach ($flashMessages as $flash): ?>
        <?php $type = (string) ($flash['type'] ?? 'info'); ?>
        <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
            <?= e((string) ($flash['message'] ?? '')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endforeach; ?>
