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
} elseif (is_active_menu($currentPath, '/reportes')) {
    $section = 'reportes';
} elseif (is_active_menu($currentPath, '/login')) {
    $section = 'auth';
}

$sectionColors = [
    'dashboard'     => ['accent' => '#00d4ff', 'soft' => '#001824', 'head_start' => '#002d47', 'head_end' => '#004060'],
    'clientes'      => ['accent' => '#e040fb', 'soft' => '#1a0022', 'head_start' => '#350040', 'head_end' => '#4d005c'],
    'plataformas'   => ['accent' => '#00ff88', 'soft' => '#001a0e', 'head_start' => '#003320', 'head_end' => '#004a2d'],
    'tipos'         => ['accent' => '#ffc107', 'soft' => '#1a1200', 'head_start' => '#332400', 'head_end' => '#4a3500'],
    'suscripciones' => ['accent' => '#4d9fff', 'soft' => '#001233', 'head_start' => '#001d47', 'head_end' => '#002a60'],
    'reportes'      => ['accent' => '#a855f7', 'soft' => '#150030', 'head_start' => '#2a0060', 'head_end' => '#3d0080'],
    'auth'          => ['accent' => '#00d4ff', 'soft' => '#001824', 'head_start' => '#002d47', 'head_end' => '#004060'],
];
$palette = $sectionColors[$section] ?? $sectionColors['dashboard'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= e(url('/img/logo.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Exo+2:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ── Ghost Store · Gamer Theme ── */
        :root {
            --gs-bg:      #060c14;
            --gs-surface: #0c1a2e;
            --gs-border:  rgba(0,212,255,.13);
            --gs-cyan:    #00d4ff;
            --gs-magenta: #e040fb;
            --gs-text:    #c8dff4;
            --gs-dim:     #7090b8;
            --gs-font:    'Rajdhani','Exo 2',system-ui,sans-serif;
        }

        body.app-body {
            font-family: var(--gs-font);
            color: var(--gs-text);
            background:
                radial-gradient(ellipse at 15% 0%,   rgba(0,212,255,.055) 0%, transparent 52%),
                radial-gradient(ellipse at 85% 100%, rgba(224,64,251,.055) 0%, transparent 52%),
                linear-gradient(180deg, #060c14 0%, #080f1c 100%);
            min-height: 100vh;
        }
        body.app-body::before {
            content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
            background:repeating-linear-gradient(0deg,transparent,transparent 3px,rgba(0,0,0,.055) 3px,rgba(0,0,0,.055) 4px);
        }

        /* Navbar */
        .navbar.app-navbar {
            background: linear-gradient(90deg,#030810 0%,#060e1c 50%,#030810 100%);
            border-bottom: 2px solid var(--section-accent);
            box-shadow: 0 0 40px rgba(0,0,0,.8), 0 2px 30px rgba(0,212,255,.07);
            position: sticky; top: 0; z-index: 1030;
        }
        .app-navbar .navbar-brand {
            font-family: var(--gs-font); font-weight: 700;
            color: var(--gs-cyan) !important;
            filter: drop-shadow(0 0 8px rgba(0,212,255,.5));
        }
        .app-navbar .nav-link {
            font-family: var(--gs-font); font-weight: 600;
            font-size: .82rem; letter-spacing: .06em; text-transform: uppercase;
            color: #7898c0 !important;
            border-radius: 4px; padding: .38rem .85rem;
            border: 1px solid transparent;
            transition: color .16s,background .16s,border-color .16s;
        }
        .app-navbar .nav-link:hover { color: #b0d0f0 !important; background: rgba(255,255,255,.035); border-color: rgba(255,255,255,.06); }
        .app-navbar .nav-link.active {
            color: var(--section-accent) !important;
            background: rgba(255,255,255,.045);
            border-color: var(--section-accent);
            text-shadow: 0 0 10px var(--section-accent);
            font-weight: 700;
        }
        .app-navbar .navbar-toggler { border-color: rgba(0,212,255,.2); }
        .app-navbar .navbar-toggler-icon { filter: invert(.65) sepia(1) saturate(5) hue-rotate(170deg); }

        /* Main */
        main.container { max-width: 1240px; position: relative; z-index: 1; }

        /* Cards */
        .card {
            background: var(--gs-surface) !important;
            border: 1px solid var(--gs-border) !important;
            border-radius: 10px !important;
            box-shadow: 0 8px 32px rgba(0,0,0,.6), inset 0 1px 0 rgba(255,255,255,.025) !important;
            overflow: hidden;
        }
        .card .card-body { background: transparent !important; color: var(--gs-text); }
        .card > .card-body:first-child { border-top: 2px solid var(--section-accent); }

        /* Typography */
        h1,h2,h3,h4,h5,h6,.h1,.h2,.h3,.h4,.h5,.h6 {
            font-family: var(--gs-font); font-weight: 700;
            color: #d0e8ff; letter-spacing: .03em;
        }

        /* Tables */
        .table-responsive {
            border: 1px solid var(--gs-border) !important;
            border-radius: 8px; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch;
        }
        .table {
            margin-bottom: 0;
            --bs-table-bg: transparent;
            --bs-table-striped-bg: rgba(0,212,255,.03);
            --bs-table-hover-bg: rgba(255,255,255,.02);
            color: var(--gs-text);
        }
        .table thead th, .table.table-dark thead th {
            background: linear-gradient(90deg, var(--section-head-start) 0%, var(--section-head-end) 100%) !important;
            color: #c8e8ff; border-bottom: 2px solid var(--section-accent) !important;
            text-transform: uppercase; letter-spacing: .08em;
            font-size: .71rem; font-weight: 700; font-family: var(--gs-font);
        }
        .table td,.table th { border-color: rgba(0,212,255,.07) !important; vertical-align: middle; color: var(--gs-text); }
        .table tbody tr td:first-child { border-left: 3px solid var(--section-accent) !important; }
        .table tbody tr:hover td:first-child { border-left-color: var(--gs-magenta) !important; }

        /* Nav pills */
        .nav-pills .nav-link {
            font-family: var(--gs-font); font-weight: 600; font-size: .82rem;
            letter-spacing: .05em; color: var(--gs-dim);
            border: 1px solid transparent; border-radius: 4px;
        }
        .nav-pills .nav-link.active {
            background: rgba(255,255,255,.05) !important;
            color: var(--section-accent) !important;
            border-color: var(--section-accent);
            text-shadow: 0 0 8px var(--section-accent);
        }
        .nav-pills .nav-link:not(.active):hover { color: #90b8d8; }

        /* Alerts */
        .alert { border-radius: 8px; }
        .alert-success { background:rgba(0,255,136,.08)!important; border-color:rgba(0,255,136,.35)!important; color:#55ffaa!important; }
        .alert-danger  { background:rgba(255,50,80,.1)!important;  border-color:rgba(255,50,80,.45)!important;  color:#ff8899!important; }
        .alert-warning { background:rgba(255,193,7,.08)!important; border-color:rgba(255,193,7,.4)!important;  color:#ffd060!important; }
        .alert-info    { background:rgba(0,212,255,.07)!important; border-color:rgba(0,212,255,.3)!important;  color:#70dcff!important; }
        .btn-close { filter: invert(1) brightness(.55); }

        /* Forms */
        .form-control,.form-select,textarea.form-control {
            background: #080f1c !important; border: 1px solid rgba(0,212,255,.17) !important;
            color: #b8d0e8 !important; border-radius: 6px;
        }
        .form-control::placeholder { color: #3a5878 !important; }
        .form-control:focus,.form-select:focus {
            background: #0a1526 !important; border-color: var(--section-accent) !important;
            box-shadow: 0 0 0 .18rem rgba(0,212,255,.16), 0 0 12px rgba(0,212,255,.09) !important;
            color: #d8eeff !important;
        }
        .form-control:-webkit-autofill,.form-control:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 100px #080f1c inset !important;
            -webkit-text-fill-color: #b8d0e8 !important;
        }
        .form-label { color: #88aad0; font-size:.83rem; font-weight:600; letter-spacing:.04em; font-family:var(--gs-font); margin-bottom:.3rem; }
        .form-text { color:#5a7ea8 !important; font-size:.78rem; }
        .form-check-label { color: var(--gs-text); }
        .form-check-input { background-color:#080f1c; border-color:rgba(0,212,255,.28); }
        .form-check-input:checked { background-color:var(--section-accent); border-color:var(--section-accent); }

        /* Buttons */
        .btn { font-family:var(--gs-font); font-weight:600; letter-spacing:.05em; transition:box-shadow .2s,transform .15s,background .2s; }
        .btn-primary {
            background: linear-gradient(135deg, var(--section-accent), var(--section-head-end)) !important;
            border-color: var(--section-accent) !important; color:#000 !important;
            box-shadow: 0 0 16px rgba(0,212,255,.2);
        }
        .btn-primary:hover { box-shadow:0 0 28px rgba(0,212,255,.42)!important; transform:translateY(-1px); color:#000!important; }
        .btn-outline-primary { color:var(--section-accent)!important; border-color:var(--section-accent)!important; background:transparent!important; }
        .btn-outline-primary:hover { background:rgba(0,212,255,.1)!important; box-shadow:0 0 12px rgba(0,212,255,.18); }
        .btn-success { background:linear-gradient(135deg,#00ff88,#00a855)!important; border-color:#00cc66!important; color:#000!important; box-shadow:0 0 16px rgba(0,255,136,.18); }
        .btn-success:hover { box-shadow:0 0 28px rgba(0,255,136,.38)!important; transform:translateY(-1px); }
        .btn-warning { background:linear-gradient(135deg,#ffc107,#e6a800)!important; border-color:#ffc107!important; color:#000!important; }
        .btn-outline-secondary { color:#7898c0!important; border-color:rgba(120,152,192,.38)!important; background:transparent!important; }
        .btn-outline-secondary:hover { background:rgba(120,152,192,.1)!important; color:#b0d0f0!important; border-color:rgba(120,152,192,.6)!important; }
        .btn-outline-danger { color:#ff6070!important; border-color:rgba(255,96,112,.38)!important; background:transparent!important; }
        .btn-outline-danger:hover { background:rgba(255,50,80,.11)!important; }
        .btn-outline-light { color:#607898!important; border-color:rgba(96,120,152,.22)!important; background:transparent!important; }
        .btn-outline-light:hover { background:rgba(255,255,255,.055)!important; color:#b0d0f0!important; }

        /* Badges */
        .badge.text-bg-secondary { background:rgba(96,120,152,.18)!important; color:#90b0d0!important; border:1px solid rgba(96,120,152,.3); font-family:var(--gs-font); font-weight:600; letter-spacing:.04em; }
        .badge.text-bg-primary   { background:rgba(0,212,255,.14)!important;  color:var(--gs-cyan)!important;  border:1px solid rgba(0,212,255,.25); }
        .badge.text-bg-success   { background:rgba(0,255,136,.11)!important;  color:#00ee77!important;          border:1px solid rgba(0,255,136,.22); }
        .badge.text-bg-danger    { background:rgba(255,50,80,.12)!important;  color:#ff6070!important;          border:1px solid rgba(255,50,80,.22); }
        .badge.text-bg-warning   { background:rgba(255,193,7,.11)!important;  color:#ffc107!important;          border:1px solid rgba(255,193,7,.22); }

        /* Misc */
        hr { border-color: rgba(0,212,255,.1); }
        .bg-light { background: #08111e !important; }
        .bg-white { background: var(--gs-surface) !important; }
        .text-secondary { color:#6888a8!important; }
        .text-success   { color:#00ff88!important; }
        .text-danger    { color:#ff6070!important; }
        .text-warning   { color:#ffc107!important; }
        .text-muted     { color:#5070a0!important; }

        /* Pagination */
        .page-link { background:#08111e; border-color:rgba(0,212,255,.13); color:#7898c0; font-family:var(--gs-font); font-weight:600; }
        .page-link:hover { background:rgba(0,212,255,.08); color:var(--gs-cyan); border-color:rgba(0,212,255,.28); }
        .page-item.disabled .page-link { background:#060c14; color:#1e3a54; border-color:rgba(0,212,255,.06); }
        .page-item.active .page-link { background:var(--section-accent); border-color:var(--section-accent); color:#000; }

        /* Scrollbar */
        ::-webkit-scrollbar { width:6px; height:6px; }
        ::-webkit-scrollbar-track { background:#060c14; }
        ::-webkit-scrollbar-thumb { background:rgba(0,212,255,.26); border-radius:3px; }
        ::-webkit-scrollbar-thumb:hover { background:rgba(0,212,255,.48); }

        @media (max-width:575.98px) {
            .app-navbar .navbar-brand { font-size:.88rem; }
            .btn.btn-lg { font-size:.9rem; padding-top:.55rem; padding-bottom:.55rem; }
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
<?php if ($section !== 'auth'): ?>
<nav class="navbar app-navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= e(url('/dashboard')) ?>">
            <img src="<?= e(url('/img/logo.png')) ?>" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>" height="36" style="max-width:140px;object-fit:contain;filter:drop-shadow(0 0 10px rgba(0,212,255,.45));">
        </a>
        <?php if ($isLoggedIn): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Mostrar navegación">
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
                        <a class="nav-link <?= is_active_menu($currentPath, '/plataformas') ? 'active' : '' ?>" href="<?= e(url('/plataformas')) ?>">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <?php $tiposActive = is_active_menu($currentPath, '/tipos-suscripcion') || is_active_menu($currentPath, '/modalidades'); ?>
                        <a class="nav-link <?= $tiposActive ? 'active' : '' ?>" href="<?= e(url('/tipos-suscripcion')) ?>">Planes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu($currentPath, '/suscripciones') ? 'active' : '' ?>" href="<?= e(url('/suscripciones')) ?>">Membresías</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu($currentPath, '/reportes') ? 'active' : '' ?>" href="<?= e(url('/reportes')) ?>">Reportes</a>
                    </li>
                </ul>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-lg-0">
                    <a href="<?= e(url('/perfil')) ?>" class="badge text-bg-secondary px-3 py-2 text-decoration-none">
                        <?= e((string) ($authUser['username'] ?? '')) ?>
                    </a>
                    <a class="btn btn-outline-light btn-sm" href="<?= e(url('/logout')) ?>">Salir</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>
<main class="container py-4">
    <?php foreach ($flashMessages as $flash): ?>
        <?php $type = (string) ($flash['type'] ?? 'info'); ?>
        <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
            <?= e((string) ($flash['message'] ?? '')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endforeach; ?>

