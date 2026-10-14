<?php
declare(strict_types=1);

/**
 * Pruebas de textos ChatGPT: php tests/chatgpt_textos.php
 * No carga db.php, ejecuta migraciones ni construye modelos/controladores.
 * Todas las credenciales utilizadas son fixtures del proceso de pruebas.
 */
foreach (['APP_KEY' => 'chatgpt-texts-tests-only-key', 'APP_RUN_MIGRATIONS' => '0'] as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
require_once __DIR__ . '/../app/config/config.php';
ini_set('log_errors', '0');
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(static function (Throwable $exception): void {
    fwrite(STDERR, 'FAIL runner de textos: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
});
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $parts = explode('\\', substr($class, 4));
    $parts[0] = strtolower($parts[0]);
    $file = APP_PATH . '/' . implode('/', $parts) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use App\Controllers\ChatgptController;
use App\Controllers\CuentasCompartidasController;
use App\Models\ChatgptCuenta;

function textCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function textEq(mixed $actual, mixed $expected, string $message): void
{
    textCheck($actual === $expected, $message);
}

function textTest(string $name, callable $action): void
{
    global $passed, $failed;
    try {
        $action();
        $passed++;
        echo 'OK   ' . $name . PHP_EOL;
    } catch (Throwable $exception) {
        $failed++;
        // Reporta la condición fallida sin volcar credenciales ni plantillas.
        echo 'FAIL ' . $name . ': ' . $exception->getMessage() . PHP_EOL;
    }
}

$passed = 0;
$failed = 0;
$defaults = require APP_PATH . '/config/chatgpt_templates.php';
$controller = (new ReflectionClass(ChatgptController::class))->newInstanceWithoutConstructor();
$messageContext = new ReflectionMethod(CuentasCompartidasController::class, 'messageContext');
$fixture = ['etiqueta' => 'Cuenta fixture de textos', 'correo' => 'delivery-fixture@example.invalid'];
$fixturePassword = 'fixture-password-for-delivery';
$contextFor = static function (int $devices, ?array $account = null, ?string $password = null) use (
    $messageContext, $controller, $fixture, $fixturePassword
): array {
    return $messageContext->invoke($controller, $account ?? $fixture, $password ?? $fixturePassword, $devices,
        '2026-11-14', ChatgptCuenta::salePrice($devices));
};

textTest('predeterminados contienen solamente entregas configurables', static function () use ($defaults): void {
    textCheck(is_array($defaults), 'El archivo debe devolver un arreglo de plantillas.');
    $keys = array_keys($defaults);
    sort($keys);
    textEq($keys, ['entrega_1', 'entrega_2'], 'Cambió el conjunto de entregas predeterminadas.');
    foreach ($defaults as $template) {
        textCheck(is_string($template) && trim($template) !== '', 'Una entrega predeterminada está vacía.');
    }
});

textTest('correo y contraseña son marcadores sin credenciales fijas', static function () use ($defaults): void {
    foreach ($defaults as $template) {
        foreach (['CORREO', 'PASSWORD', 'DISPOSITIVOS', 'DISPOSITIVO_LABEL'] as $key) {
            textCheck(str_contains($template, '{' . $key . '}'), 'Falta un marcador requerido de entrega.');
        }
        textEq(substr_count($template, '{CORREO}'), 1, 'El correo debe configurarse una sola vez.');
        textEq(substr_count($template, '{PASSWORD}'), 1, 'La contraseña debe configurarse una sola vez.');
        textEq(substr_count($template, '📧 Correo: '), 1, 'No debe existir un correo adicional fijo.');
        textEq(substr_count($template, '🔑 Contraseña: '), 1, 'No debe existir una contraseña adicional fija.');
        textCheck(preg_match('/(?:^|\R)📧 Correo: \{CORREO\}(?:\R|$)/u', $template) === 1,
            'El campo correo debe contener únicamente su marcador.');
        textCheck(preg_match('/(?:^|\R)🔑 Contraseña: \{PASSWORD\}(?:\R|$)/u', $template) === 1,
            'El campo contraseña debe contener únicamente su marcador.');
        textEq(preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $template), 0,
            'Un predeterminado contiene un correo fijo.');
        preg_match_all('/\{([A-Z_]+)\}/', $template, $matches);
        $keys = array_values(array_unique($matches[1]));
        sort($keys);
        textEq($keys, ['CORREO', 'DISPOSITIVOS', 'DISPOSITIVO_LABEL', 'PASSWORD'],
            'Un predeterminado incluye un marcador sin configurar.');
    }
});

textTest('contexto usa credenciales actuales y singular para un dispositivo', static function () use (
    $contextFor, $fixture, $fixturePassword
): void {
    $context = $contextFor(1);
    textEq($context['CORREO'], $fixture['correo'], 'El contexto debe usar el correo de la cuenta.');
    textEq($context['PASSWORD'], $fixturePassword, 'El contexto debe usar la contraseña recibida.');
    textEq($context['DISPOSITIVOS'], '1', 'La entrega individual debe indicar un dispositivo.');
    textEq($context['DISPOSITIVO_LABEL'], 'dispositivo', 'La entrega individual debe usar singular.');
});

textTest('contexto usa plural para dos dispositivos', static function () use ($contextFor): void {
    $context = $contextFor(2);
    textEq($context['DISPOSITIVOS'], '2', 'La entrega doble debe indicar dos dispositivos.');
    textEq($context['DISPOSITIVO_LABEL'], 'dispositivos', 'La entrega doble debe usar plural.');
});

foreach ([1 => '1 dispositivo', 2 => '2 dispositivos'] as $devices => $devicePhrase) {
    textTest('entrega de ' . $devices . ' reemplaza los marcadores y conserva el texto', static function () use (
        $defaults, $contextFor, $fixture, $fixturePassword, $devices, $devicePhrase
    ): void {
        $template = $defaults['entrega_' . $devices];
        $rendered = ChatgptCuenta::renderMessage($template, $contextFor($devices));
        textEq(substr_count($rendered, $fixture['correo']), 1, 'Debe entregar el correo de esta cuenta.');
        textEq(substr_count($rendered, $fixturePassword), 1, 'Debe entregar la contraseña de esta cuenta.');
        textCheck(str_contains($rendered, $devicePhrase), 'El texto final no refleja el límite de dispositivos.');
        textEq(preg_match('/\{[A-Z_]+\}/', $rendered), 0, 'Quedó un marcador sin reemplazar.');
        $restored = str_replace(
            [$fixture['correo'], $fixturePassword, $devicePhrase],
            ['{CORREO}', '{PASSWORD}', '{DISPOSITIVOS} {DISPOSITIVO_LABEL}'],
            $rendered
        );
        textEq($restored, $template, 'La entrega modificó instrucciones, saltos de línea o puntuación.');
        textCheck(str_contains($rendered, '60 segundos'), 'Falta la instrucción de espera del proveedor.');
        textCheck(preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $rendered) === 1,
            'La entrega debe preservar los emojis del texto.');
    });
}

textTest('ambas entregas solo varían en cantidad y concordancia', static function () use ($defaults, $contextFor): void {
    $one = ChatgptCuenta::renderMessage($defaults['entrega_1'], $contextFor(1));
    $two = ChatgptCuenta::renderMessage($defaults['entrega_2'], $contextFor(2));
    textEq(str_replace('1 dispositivo', '[DISPOSITIVOS]', $one),
        str_replace('2 dispositivos', '[DISPOSITIVOS]', $two),
        'La entrega doble cambió las credenciales o las instrucciones comunes.');
});

textTest('credenciales con HTML, PHP y marcadores permanecen como texto literal', static function () use (
    $defaults, $contextFor
): void {
    $account = ['etiqueta' => 'Fixture HTML', 'correo' => '<script>alert("fixture")</script>@example.invalid'];
    $password = '<?php throw new RuntimeException("fixture"); ?> & "\' {CORREO}';
    foreach ([1, 2] as $devices) {
        $rendered = ChatgptCuenta::renderMessage($defaults['entrega_' . $devices], $contextFor($devices, $account, $password));
        textCheck(str_contains($rendered, $account['correo']), 'Se alteró el correo literal del fixture.');
        textCheck(str_contains($rendered, $password), 'Se alteró o evaluó la contraseña literal del fixture.');
        textEq(substr_count($rendered, '{CORREO}'), 1, 'Los marcadores dentro de una contraseña no se sustituyen de nuevo.');
        textCheck(!str_contains($rendered, '&lt;script&gt;'), 'El renderizador de mensajes debe devolver texto plano.');
    }
});

echo "Resultado: {$passed} correctas, {$failed} fallidas. Sin acceso a base de datos.\n";
exit($failed === 0 ? 0 : 1);
