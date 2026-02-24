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

$section = 'dashboard';
if (is_active_menu($currentPath, '/clientes')) {
    $section = 'clientes';
} elseif (is_active_menu($currentPath, '/plataformas')) {
    $section = 'plataformas';
} elseif (is_active_menu($currentPath, '/tipos-suscripcion') || is_active_menu($currentPath, '/modalidades')) {
    $section = 'tipos';
} elseif (is_active_menu($currentPath, '/suscripciones')) {
    $section = 'suscripciones';
} elseif (is_active_menu($currentPath, '/login')) {
    $section = 'auth';
}

$sectionColors = [
    'dashboard' => ['accent' => '#111827', 'soft' => '#e5e7eb', 'head_start' => '#111827', 'head_end' => '#1f2937'],
    'clientes' => ['accent' => '#374151', 'soft' => '#f3f4f6', 'head_start' => '#374151', 'head_end' => '#4b5563'],
    'plataformas' => ['accent' => '#0f766e', 'soft' => '#ccfbf1', 'head_start' => '#0f766e', 'head_end' => '#115e59'],
    'tipos' => ['accent' => '#0369a1', 'soft' => '#e0f2fe', 'head_start' => '#0369a1', 'head_end' => '#075985'],
    'suscripciones' => ['accent' => '#312e81', 'soft' => '#e0e7ff', 'head_start' => '#312e81', 'head_end' => '#3730a3'],
    'auth' => ['accent' => '#1f2937', 'soft' => '#e5e7eb', 'head_start' => '#1f2937', 'head_end' => '#111827'],
];
$palette = $sectionColors[$section] ?? $sectionColors['dashboard'];
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
    <style>
        :root {
            --app-surface: #ffffff;
            --app-ink: #0f172a;
            --app-muted: #64748b;
            --app-border: #dbe5ef;
        }

        body.app-body {
            color: var(--app-ink);
            background:
                radial-gradient(circle at 0 0, var(--section-soft) 0, transparent 24%),
                radial-gradient(circle at 100% 20%, #f3f4f6 0, transparent 30%),
                linear-gradient(180deg, #f9fafb 0%, #eef2f7 100%);
            min-height: 100vh;
        }

        .navbar.app-navbar {
            background: linear-gradient(125deg, #020617 0%, #111827 68%, #1f2937 100%);
            border-bottom: 3px solid var(--section-accent);
        }

        .app-navbar .navbar-brand {
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .app-navbar .nav-link {
            border-radius: 999px;
            padding-left: 0.9rem;
            padding-right: 0.9rem;
            color: #dbe6f2;
        }

        .app-navbar .nav-link.active {
            background-color: var(--section-accent);
            color: #fff;
            font-weight: 700;
        }

        main.container {
            max-width: 1240px;
        }

        .card {
            border: 1px solid var(--app-border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06) !important;
        }

        .card .card-body {
            background-color: var(--app-surface);
        }

        .card > .card-body:first-child {
            border-top: 4px solid var(--section-accent);
        }

        .table-responsive {
            border: 1px solid var(--app-border);
            border-radius: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            margin-bottom: 0;
            --bs-table-striped-bg: #f4f8fc;
            --bs-table-hover-bg: var(--section-soft);
        }

        .table thead th,
        .table.table-dark thead th {
            background: linear-gradient(90deg, var(--section-head-start) 0%, var(--section-head-end) 100%);
            color: #f8fafc;
            border-bottom: 2px solid var(--section-accent);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .table td,
        .table th {
            border-color: #e2e8f0;
            vertical-align: middle;
        }

        .table tbody tr td:first-child {
            border-left: 4px solid var(--section-accent);
        }

        .table tbody tr:hover td:first-child {
            border-left-color: var(--section-head-end);
        }

        .nav-pills .nav-link.active {
            background-color: var(--section-accent);
        }

        .alert-info {
            border-color: var(--section-accent);
            background-color: var(--section-soft);
            color: #0f172a;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--section-accent);
            box-shadow: 0 0 0 0.2rem var(--section-soft);
        }

        .btn-primary {
            background-color: var(--section-accent);
            border-color: var(--section-accent);
        }

        .btn-outline-primary {
            color: var(--section-accent);
            border-color: var(--section-accent);
        }

        .btn-outline-primary:hover {
            background-color: var(--section-accent);
            border-color: var(--section-accent);
        }

        @media (max-width: 575.98px) {
            .app-navbar .navbar-brand {
                font-size: 0.95rem;
            }

            .btn.btn-lg {
                font-size: 0.95rem;
                padding-top: 0.6rem;
                padding-bottom: 0.6rem;
            }
        }
    </style>
</head>
<body
    class="app-body"
    style="
        --section-accent: <?= e((string) $palette['accent']) ?>;
        --section-soft: <?= e((string) $palette['soft']) ?>;
        --section-head-start: <?= e((string) $palette['head_start']) ?>;
        --section-head-end: <?= e((string) $palette['head_end']) ?>;
    "
>
<nav class="navbar app-navbar navbar-expand-lg navbar-dark shadow-sm">
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
                        <a class="nav-link <?= is_active_menu($currentPath, '/dashboard') || $currentPath === '/' ? 'active' : '' ?>" href="<?= e(url('/dashboard')) ?>">Ghost Panel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu($currentPath, '/clientes') ? 'active' : '' ?>" href="<?= e(url('/clientes')) ?>">Clientes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu($currentPath, '/plataformas') ? 'active' : '' ?>" href="<?= e(url('/plataformas')) ?>">Catalogo</a>
                    </li>
                    <li class="nav-item">
                        <?php $tiposActive = is_active_menu($currentPath, '/tipos-suscripcion') || is_active_menu($currentPath, '/modalidades'); ?>
                        <a class="nav-link <?= $tiposActive ? 'active' : '' ?>" href="<?= e(url('/tipos-suscripcion')) ?>">Planes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu($currentPath, '/suscripciones') ? 'active' : '' ?>" href="<?= e(url('/suscripciones')) ?>">Membresias</a>
                    </li>
                </ul>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-lg-0">
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
