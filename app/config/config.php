<?php
declare(strict_types=1);

if (!function_exists('app_load_env_file')) {
    function app_load_env_file(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $delimiterPos = strpos($line, '=');
            if ($delimiterPos === false) {
                continue;
            }

            $name = trim(substr($line, 0, $delimiterPos));
            if ($name === '') {
                continue;
            }

            // Variables del entorno del servidor tienen prioridad sobre el archivo .env
            if (getenv($name) !== false) {
                continue;
            }

            $value = trim(substr($line, $delimiterPos + 1));
            if ($value !== '' && strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

if (!function_exists('app_env')) {
    function app_env(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $_ENV)) {
            return (string) $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER)) {
            return (string) $_SERVER[$key];
        }

        $value = getenv($key);
        if ($value === false) {
            return $default;
        }

        return (string) $value;
    }
}

if (!function_exists('app_env_bool')) {
    function app_env_bool(string $key, bool $default): bool
    {
        $value = app_env($key);
        if ($value === null) {
            return $default;
        }

        $normalized = strtolower(trim($value));
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }
}

if (!function_exists('app_env_int')) {
    function app_env_int(string $key, int $default): int
    {
        $value = app_env($key);
        if ($value === null || !is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }
}

$appBasePath = dirname(__DIR__, 2);
app_load_env_file($appBasePath . '/.env');

define('APP_NAME', app_env('APP_NAME', 'Ghost Store'));
define('APP_ENV', app_env('APP_ENV', 'development'));
define('APP_DEBUG', app_env_bool('APP_DEBUG', APP_ENV !== 'production'));
define('APP_TIMEZONE', app_env('APP_TIMEZONE', 'America/La_Paz'));
define('RECUP_DAYS', app_env_int('RECUP_DAYS', 3));
define('APP_CURRENCY_CODE', app_env('APP_CURRENCY_CODE', 'BOB'));
define('APP_CURRENCY_SYMBOL', app_env('APP_CURRENCY_SYMBOL', 'Bs'));
define('APP_MONEY_DECIMALS', app_env_int('APP_MONEY_DECIMALS', 0));
define('APP_DECIMAL_SEPARATOR', app_env('APP_DECIMAL_SEPARATOR', ','));
define('APP_THOUSANDS_SEPARATOR', app_env('APP_THOUSANDS_SEPARATOR', '.'));

define('BASE_PATH', $appBasePath);
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

define('DB_HOST', app_env('DB_HOST', '127.0.0.1'));
define('DB_PORT', app_env_int('DB_PORT', 3306));
define('DB_NAME', app_env('DB_NAME', 'streaming_renovaciones'));
define('DB_USER', app_env('DB_USER', 'root'));
define('DB_PASS', app_env('DB_PASS', ''));
define('DB_CHARSET', app_env('DB_CHARSET', 'utf8mb4'));
define('DB_COLLATION', app_env('DB_COLLATION', 'utf8mb4_unicode_ci'));
define('APP_RUN_MIGRATIONS', app_env_bool('APP_RUN_MIGRATIONS', APP_ENV !== 'production'));

define(
    'DEFAULT_TEMPLATE_MENOS_2',
    'Hola {NOMBRE}, tu servicio {PLATAFORMA} ({PLAN}) vence en 3 dias ({FECHA_VENCE}). Valor de renovacion en Ghost Store: {PRECIO}.'
);
define(
    'DEFAULT_TEMPLATE_MENOS_1',
    'Hola {NOMBRE}, hoy vence tu servicio {PLATAFORMA} ({PLAN}) ({FECHA_VENCE}). Si deseas renovar hoy, el valor es {PRECIO}.'
);
define(
    'DEFAULT_TEMPLATE_RECUP',
    'Hola {NOMBRE}, tu servicio {PLATAFORMA} ({PLAN}) vencio hace 3 dias. Aun podemos recuperarlo hoy por {PRECIO}.'
);

date_default_timezone_set(APP_TIMEZONE);

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
ini_set('default_charset', 'UTF-8');

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

function normalize_whatsapp_phone_bolivia(string $value): string
{
    $digits = normalize_phone($value);
    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    if (str_starts_with($digits, '591')) {
        $local = ltrim(substr($digits, 3), '0');

        return $local === '' ? '' : '591' . $local;
    }

    $local = ltrim($digits, '0');

    return $local === '' ? '' : '591' . $local;
}

function is_valid_whatsapp_phone_bolivia(string $value): bool
{
    if (!str_starts_with($value, '591')) {
        return false;
    }

    $local = substr($value, 3);
    if (strlen($local) !== 8) {
        return false;
    }

    return $local[0] === '6' || $local[0] === '7';
}
