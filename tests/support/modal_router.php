<?php
declare(strict_types=1);

// Exclusivo del runner local: nunca habilita acceso en Apache ni en la base de la aplicación.
if (PHP_SAPI !== 'cli-server' || !preg_match('/^ghost_modal_test_[a-f0-9]{16}$/D', (string) getenv('DB_NAME'))) {
    http_response_code(404);
    exit;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(?:css|js|vendor|assets|img|images|icons)/[a-zA-Z0-9_./-]+$#D', $path) && !str_contains($path, '..')
    && is_file(__DIR__ . '/../../public' . $path)) return false;
session_name('gs_modal_test');
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/../../app/config/config.php';
if (!isset($_SESSION['auth'])) $_SESSION['auth'] = ['id' => 1, 'username' => 'demo_modal', 'rol' => 'admin'];
require __DIR__ . '/../../public/index.php';
