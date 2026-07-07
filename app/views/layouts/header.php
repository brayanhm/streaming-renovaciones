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
} elseif (is_active_menu($currentPath, '/cuentas-principales') || is_active_menu($currentPath, '/dashboard-ia') || is_active_menu($currentPath, '/contactar-ia')) {
    $section = 'cuentas';
} elseif (is_active_menu($currentPath, '/importar')) {
    $section = 'importar';
} elseif (is_active_menu($currentPath, '/usuarios')) {
    $section = 'usuarios';
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
    'cuentas'       => ['accent' => '#10b981', 'soft' => '#04201a', 'head_start' => '#07362b', 'head_end' => '#0a4d3d'],
    'importar'      => ['accent' => '#84cc16', 'soft' => '#0e1a00', 'head_start' => '#1a3300', 'head_end' => '#274d00'],
    'usuarios'      => ['accent' => '#ff6b6b', 'soft' => '#2a0808', 'head_start' => '#4a1010', 'head_end' => '#661515'],
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

        /* ── Sidebar layout ── */
        .app-topbar {
            position: sticky; top: 0; z-index: 1035;
            display: flex; align-items: center; gap: .75rem;
            padding: .45rem 1rem;
            background: linear-gradient(90deg,#030810 0%,#060e1c 50%,#030810 100%);
            border-bottom: 2px solid var(--section-accent);
            box-shadow: 0 2px 22px rgba(0,0,0,.7);
        }
        .app-brand img { height: 32px; max-width: 130px; object-fit: contain; filter: drop-shadow(0 0 8px rgba(0,212,255,.45)); }
        .app-burger {
            color: var(--gs-cyan); border: 1px solid rgba(0,212,255,.3); background: transparent;
            font-size: 1.1rem; line-height: 1; padding: .2rem .55rem; border-radius: 6px;
        }
        .app-user-badge { font-family: var(--gs-font); }

        .app-layout { display: flex; align-items: flex-start; }
        .app-sidebar { background: linear-gradient(180deg,#050b16 0%,#070f1e 100%); border-right: 1px solid var(--gs-border); }
        @media (min-width: 992px) {
            .app-sidebar {
                width: 244px; flex: 0 0 244px;
                position: sticky; top: 50px; align-self: flex-start;
                height: calc(100vh - 50px); overflow-y: auto;
            }
        }
        .app-sidebar .offcanvas-header { border-bottom: 1px solid var(--gs-border); }
        .app-sidebar-body { padding: .8rem .6rem; }
        .app-main { flex: 1 1 auto; min-width: 0; position: relative; z-index: 1; }
        .app-content { max-width: 1600px; margin-left: 0; margin-right: auto; }

        .app-nav-group { margin-bottom: .3rem; }
        .app-nav-toggle {
            width: 100%; background: transparent; border: 0; cursor: pointer;
            color: var(--gs-dim); font-family: var(--gs-font); font-weight: 700;
            font-size: .7rem; letter-spacing: .1em; text-transform: uppercase;
            padding: .5rem .6rem; border-radius: 6px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .app-nav-toggle::after { content: '▾'; font-size: .7rem; transition: transform .18s; opacity: .7; }
        .app-nav-toggle.collapsed::after { transform: rotate(-90deg); }
        .app-nav-toggle:hover { color: #90b8d8; background: rgba(255,255,255,.03); }
        .app-nav-link {
            display: block; text-decoration: none;
            color: #8fb0d4; font-family: var(--gs-font); font-weight: 600;
            font-size: .9rem; padding: .48rem .7rem .48rem 1.15rem; margin: .12rem 0;
            border-radius: 6px; border-left: 2px solid transparent;
            transition: color .15s, background .15s, border-color .15s;
        }
        .app-nav-link:hover { color: #d0e8ff; background: rgba(255,255,255,.04); }
        .app-nav-link.active {
            color: var(--section-accent); background: rgba(255,255,255,.06);
            border-left-color: var(--section-accent);
            text-shadow: 0 0 10px var(--section-accent); font-weight: 700;
        }

        /* ── Iconos del menú ── */
        .app-nav-link { display: flex; align-items: center; }
        .app-nav-link .ico { display: inline-block; width: 1.4rem; text-align: center; margin-right: .55rem; flex-shrink: 0; font-size: .98rem; }

        /* ── Riel de iconos que se expande al pasar el mouse (escritorio) ── */
        @media (min-width: 992px) {
            /* El sidebar reserva SOLO el ancho del riel (64px); su contenido flota y se expande. */
            .app-sidebar {
                flex: 0 0 64px !important; width: 64px !important;
                position: sticky; top: 50px; align-self: flex-start;
                height: calc(100vh - 50px);
                overflow: visible !important;
                z-index: 1041; /* por encima de .app-main para que el menú expandido flote sobre el contenido */
            }
            .app-sidebar > .offcanvas-body {
                display: block;
                position: absolute; top: 0; left: 0;
                width: 64px; height: 100%;
                padding: .8rem .5rem;
                overflow: hidden;
                background: linear-gradient(180deg,#050b16 0%,#070f1e 100%);
                border-right: 1px solid var(--gs-border);
                transition: width .2s ease, box-shadow .2s ease;
                z-index: 1040;
            }
            /* Al pasar el mouse: el contenido del riel se expande flotando sobre la página */
            .app-sidebar:hover > .offcanvas-body {
                width: 244px; overflow-y: auto;
                box-shadow: 6px 0 34px rgba(0,0,0,.55);
                border-right-color: var(--section-accent);
            }
            /* Riel: todos los items visibles como iconos, sin encabezados ni etiquetas */
            .app-sidebar .collapse { display: block !important; height: auto !important; }
            .app-nav-toggle { display: none; pointer-events: none; }
            .app-nav-link { justify-content: center; }
            .app-nav-link .ico { margin-right: 0; }
            .app-nav-link .lbl { display: none; }
            /* Al hover: menú completo con textos y encabezados */
            .app-sidebar:hover .app-nav-toggle { display: flex; }
            .app-sidebar:hover .app-nav-link { justify-content: flex-start; }
            .app-sidebar:hover .app-nav-link .ico { margin-right: .55rem; }
            .app-sidebar:hover .app-nav-link .lbl { display: inline; }
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
<?php
$navUser = (string) ($authUser['username'] ?? '');
$isAdmin = (string) ($authUser['rol'] ?? '') === 'admin';
$actDash      = is_active_menu($currentPath, '/dashboard') || $currentPath === '/';
$actContactar = $currentPath === '/contactar';
$actPanelIa   = is_active_menu($currentPath, '/dashboard-ia');
$actContactIa = $currentPath === '/contactar-ia';
$actCuentas   = is_active_menu($currentPath, '/cuentas-principales');
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
        ['Panel', url('/dashboard'), $actDash, '📺'],
        ['Contactar hoy', url('/contactar'), $actContactar, '💬'],
        ['Importar cuentas', url('/importar'), $actImportar, '📥'],
    ]],
    ['Inteligencia Artificial', [
        ['Panel IA', url('/dashboard-ia'), $actPanelIa, '🤖'],
        ['Contactar IA', url('/contactar-ia'), $actContactIa, '🗨️'],
        ['Cuentas IA', url('/cuentas-principales'), $actCuentas, '🔑'],
    ]],
    ['Gestión', [
        ['Clientes', url('/clientes'), $actClientes, '👥'],
        ['Membresías', url('/suscripciones'), $actMembres, '🎫'],
        ['Reportes', url('/reportes'), $actReportes, '📊'],
    ]],
    ['Catálogo', [
        ['Plataformas', url('/plataformas'), $actCatalogo, '🗂️'],
        ['Planes', url('/tipos-suscripcion'), $actPlanes, '🏷️'],
    ]],
];
if ($isAdmin) {
    $sidebarMenu[] = ['Administración', [
        ['Usuarios', url('/usuarios'), $actUsuarios, '🛡️'],
        ['Auditoría', url('/usuarios/auditoria'), $actAudit, '📝'],
    ]];
}
?>
<?php if ($section === 'auth'): ?>
<main class="container py-4">
<?php else: ?>
<div class="app-topbar">
    <?php if ($isLoggedIn): ?>
    <button class="app-burger d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Menú">☰</button>
    <?php endif; ?>
    <a class="app-brand d-flex align-items-center" href="<?= e(url('/dashboard')) ?>">
        <img src="<?= e(url('/img/logo.png')) ?>" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
    </a>
    <?php if ($isLoggedIn): ?>
    <div class="ms-auto d-flex align-items-center gap-2">
        <a href="<?= e(url('/perfil')) ?>" class="badge text-bg-secondary px-3 py-2 text-decoration-none app-user-badge d-none d-sm-inline"><?= e($navUser) ?></a>
        <a class="btn btn-outline-light btn-sm" href="<?= e(url('/logout')) ?>">Salir</a>
    </div>
    <?php endif; ?>
</div>
<div class="app-layout">
    <?php if ($isLoggedIn): ?>
    <aside class="offcanvas-lg offcanvas-start app-sidebar" id="appSidebar" tabindex="-1" aria-label="Navegación">
        <div class="offcanvas-header d-lg-none">
            <span class="fw-semibold text-secondary">Menú</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body app-sidebar-body">
            <nav class="app-nav">
                <?php foreach ($sidebarMenu as $gi => [$grupo, $items]): ?>
                    <?php $abierto = false; foreach ($items as $it) { if ($it[2]) { $abierto = true; break; } } ?>
                    <div class="app-nav-group">
                        <button class="app-nav-toggle <?= $abierto ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#grp<?= (int) $gi ?>" aria-expanded="<?= $abierto ? 'true' : 'false' ?>">
                            <span class="grp-lbl"><?= e($grupo) ?></span>
                        </button>
                        <div class="collapse <?= $abierto ? 'show' : '' ?>" id="grp<?= (int) $gi ?>">
                            <?php foreach ($items as [$lbl, $href, $act, $ico]): ?>
                                <a class="app-nav-link <?= $act ? 'active' : '' ?>" href="<?= e($href) ?>" title="<?= e($lbl) ?>"><span class="ico"><?= $ico ?></span><span class="lbl"><?= e($lbl) ?></span></a>
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
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endforeach; ?>

