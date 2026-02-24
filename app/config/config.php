<?php
declare(strict_types=1);

define('APP_NAME', 'Ghost Store');
define('APP_ENV', 'development');
define('APP_DEBUG', true);
define('APP_TIMEZONE', 'America/La_Paz');
define('RECUP_DAYS', 7);
define('APP_CURRENCY_CODE', 'BOB');
define('APP_CURRENCY_SYMBOL', 'Bs');
define('APP_MONEY_DECIMALS', 0);
define('APP_DECIMAL_SEPARATOR', ',');
define('APP_THOUSANDS_SEPARATOR', '.');

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'streaming_renovaciones');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define(
    'DEFAULT_TEMPLATE_MENOS_2',
    'Hola {NOMBRE}, te recordamos que tu servicio {PLATAFORMA} ({PLAN}) vence el {FECHA_VENCE}. Valor de renovacion en Ghost Store: {PRECIO}.'
);
define(
    'DEFAULT_TEMPLATE_MENOS_1',
    'Hola {NOMBRE}, tu servicio {PLATAFORMA} ({PLAN}) vence manana ({FECHA_VENCE}). Valor de renovacion en Ghost Store: {PRECIO}.'
);
define(
    'DEFAULT_TEMPLATE_RECUP',
    'Hola {NOMBRE}, aun podemos reactivar tu servicio {PLATAFORMA} ({PLAN}) en Ghost Store. Valor: {PRECIO}.'
);

date_default_timezone_set(APP_TIMEZONE);

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function base_url_path(): string
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $scriptDir = rtrim($scriptDir, '/');

    $basePath = $scriptDir === '' ? '/' : $scriptDir;

    return $basePath;
}

function url(string $path = '/'): string
{
    $base = base_url_path();
    $path = '/' . ltrim($path, '/');

    return $base === '/' ? $path : $base . $path;
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $scriptDir = rtrim($scriptDir, '/');

    if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
        $uri = substr($uri, strlen($scriptDir));
    }

    if (str_starts_with($uri, '/index.php')) {
        $uri = substr($uri, strlen('/index.php'));
    }

    $uri = '/' . trim((string) $uri, '/');

    return $uri === '//' ? '/' : $uri;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(mixed $value): string
{
    return APP_CURRENCY_SYMBOL . ' ' . number_format(
        (float) $value,
        APP_MONEY_DECIMALS,
        APP_DECIMAL_SEPARATOR,
        APP_THOUSANDS_SEPARATOR
    );
}

function flash(string $type, string $message): void
{
    if (!isset($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }

    $_SESSION['_flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consume_flash(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return is_array($messages) ? $messages : [];
}

function old(string $key, string $default = ''): string
{
    if (!isset($_SESSION['_old']) || !is_array($_SESSION['_old'])) {
        return $default;
    }

    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function set_old(array $input): void
{
    $_SESSION['_old'] = $input;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function auth_user(): ?array
{
    if (!isset($_SESSION['auth']) || !is_array($_SESSION['auth'])) {
        return null;
    }

    return $_SESSION['auth'];
}

function is_logged_in(): bool
{
    return auth_user() !== null;
}

function normalize_phone(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}
