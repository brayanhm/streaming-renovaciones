<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CuentaCompartida;
use DateTimeImmutable;

abstract class CuentasCompartidasController extends Controller
{
    protected const MODEL = \App\Models\ChatgptCuenta::class;
    protected const SERVICE = 'chatgpt';
    protected const LABEL = 'ChatGPT';
    protected const UNIT = 'dispositivos';
    protected CuentaCompartida $cuentas;

    public function __construct()
    {
        $user = (new \App\Models\User())->findById((int) (auth_user()['id'] ?? 0));
        if ($user === null || (int) ($user['activo'] ?? 1) !== 1) {
            unset($_SESSION['auth']);
            flash('warning', 'Inicia sesión con una cuenta activa para continuar.');
            $this->redirect('/login');
        }
        $_SESSION['auth']['rol'] = (string) $user['rol'];
        $modelClass = static::MODEL;
        $this->cuentas = new $modelClass();
    }

    public function legacyIndex(): void
    {
        $this->redirect($this->path());
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $rows = $this->cuentas->all($search);
        $summary = ['cuentas' => count($rows), 'ocupados' => 0, 'libres' => 0, 'ventas_bs' => 0.0, 'pagos_bs' => 0.0];
        foreach ($rows as $row) {
            foreach (['ocupados', 'libres', 'ventas_bs', 'pagos_bs'] as $key) {
                $summary[$key] += (float) $row[$key];
            }
        }
        $this->render(static::SERVICE . '/index', [
            'pageTitle' => static::LABEL, 'rows' => $rows, 'summary' => $summary,
            'search' => $search, 'canAdmin' => $this->canAdmin(),
            'creationToken' => $this->issueToken('crear', 0),
            'importToken' => static::SERVICE === 'chatgpt' ? $this->issueToken('importar', 0) : '',
        ]);
    }

    public function show(int $id): void
    {
        $modelClass = static::MODEL;
        $cuenta = $this->account($id);
        $templates = $this->cuentas->templates();
        $password = decrypt_secret((string) ($cuenta['password_cuenta'] ?? ''));
        $credentialsReady = filter_var((string) ($cuenta['correo'] ?? ''), FILTER_VALIDATE_EMAIL) !== false && trim($password) !== '';
        $sales = $this->cuentas->sales($id);
        $saleTexts = [];
        foreach ($sales as $sale) {
            $saleTexts[(int) $sale['id']] = $credentialsReady ? $modelClass::renderMessage(
                $templates['entrega_' . (int) $sale['dispositivos']] ?? '',
                $this->messageContext($cuenta, $password, (int) $sale['dispositivos'], (string) $sale['fecha_vencimiento'], (string) $sale['monto_bs'])
            ) : '';
        }
        $today = new DateTimeImmutable('today');
        $paidEnd = parse_ymd_date((string) ($cuenta['fecha_vencimiento'] ?? ''));
        $graceEnd = (string) ($cuenta['fin_gracia'] ?? '');
        $currentEnd = static::SERVICE === 'chatgpt' && $graceEnd !== '' && $paidEnd !== null && $today >= $paidEnd
            ? $graceEnd : (string) ($cuenta['fecha_vencimiento'] ?? '');
        $entregaTexts = [];
        foreach ($modelClass::TEMPLATE_KEYS as $templateKey) {
            if (!preg_match('/^entrega_(\d+)$/', $templateKey, $match)) {
                continue;
            }
            $devices = (int) $match[1];
            $entregaTexts[$devices] = $credentialsReady ? $modelClass::renderMessage(
                $templates['entrega_' . $devices] ?? '',
                $this->messageContext($cuenta, $password, $devices, $currentEnd, $modelClass::salePrice($devices))
            ) : '';
        }
        $lastSaleId = (int) ($_GET['venta'] ?? 0);
        if (!array_key_exists($lastSaleId, $saleTexts)) {
            $lastSaleId = null;
        }
        $this->render(static::SERVICE . '/show', [
            'pageTitle' => static::LABEL . ' · ' . $cuenta['etiqueta'], 'cuenta' => $cuenta,
            'password' => $password, 'pagos' => $this->cuentas->payments($id), 'ventas' => $sales,
            'templates' => $templates, 'saleTexts' => $saleTexts, 'entregaTexts' => $entregaTexts,
            'renewalText' => $modelClass::renderMessage($templates['renovacion'] ?? '', $this->messageContext($cuenta, $password, 0, $currentEnd, '')),
            'lastSaleId' => $lastSaleId, 'canAdmin' => $this->canAdmin(),
            'credentialsReady' => $credentialsReady,
            'saleToken' => $this->issueToken('vender', $id), 'paymentToken' => $this->issueToken('pagar', $id),
            'releaseToken' => $this->issueToken('liberar', $id), 'stateToken' => $this->issueToken('estado', $id),
            'periodToken' => static::SERVICE === 'chatgpt' ? $this->issueToken('vigencia', $id) : '',
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $token = $this->validateToken('crear', 0);
        if (isset($_SESSION[static::SERVICE . '_creadas'][$token])) {
            $this->redirect($this->path('/' . (int) $_SESSION[static::SERVICE . '_creadas'][$token]));
        }
        $data = $this->accountPayload();
        $data['fecha_pago'] = (string) ($_POST['fecha_pago'] ?? '');
        $data['monto_bs'] = (string) ($_POST['monto_bs'] ?? '');
        try {
            $this->validateDate($data['fecha_pago']);
            if (trim($data['password_cuenta']) === '') {
                throw new \InvalidArgumentException('Ingresa la contraseña de la cuenta.');
            }
            $id = $this->cuentas->create($data);
            $_SESSION[static::SERVICE . '_creadas'][$token] = $id;
            audit(static::SERVICE . '.crear', 'Cuenta ' . $id . ' con pago inicial');
            flash('success', 'Cuenta ' . static::LABEL . ' creada con ' . $this->capacity() . ' ' . static::UNIT . ' y su pago inicial.');
            $this->redirect($this->path('/' . $id));
        } catch (\Throwable $error) {
            set_old(array_diff_key($data, ['password_cuenta' => true]));
            $this->operationError($error);
            $this->redirect($this->path());
        }
    }

    public function edit(int $id): void
    {
        $this->requireAdmin();
        $this->render(static::SERVICE . '/edit', [
            'pageTitle' => 'Editar cuenta ' . static::LABEL, 'cuenta' => $this->account($id),
            'password' => '', 'canAdmin' => true,
        ]);
    }

    public function update(int $id): void
    {
        $this->requireAdmin();
        $this->account($id);
        try {
            $this->cuentas->update($id, $this->accountPayload());
            audit(static::SERVICE . '.editar', 'Cuenta ' . $id);
            flash('success', 'Datos de la cuenta actualizados.');
        } catch (\Throwable $error) {
            $this->operationError($error);
            $this->redirect($this->path('/' . $id) . '/editar');
        }
        $this->redirect($this->path('/' . $id));
    }

    public function pagar(int $id): void
    {
        $this->requireAdmin();
        $this->account($id);
        $token = $this->validateToken('pagar', $id);
        try {
            $date = (string) ($_POST['fecha_pago'] ?? '');
            $this->validateDate($date);
            $payment = $this->cuentas->registerPayment($id, $date, (string) ($_POST['monto_bs'] ?? ''), $token);
            audit(static::SERVICE . '.pagar', 'Cuenta ' . $id . ' / pago ' . $payment);
            flash('success', static::SERVICE === 'chatgpt'
                ? 'Pago registrado: un mes pagado y un mes de gracia desde la fecha del pago. Los cupos se conservan hasta liberarlos manualmente.'
                : 'Pago registrado: un mes de vigencia desde la fecha del pago. Los cupos se conservan hasta liberarlos manualmente.');
        } catch (\Throwable $error) {
            $this->operationError($error);
        }
        $this->redirect($this->path('/' . $id));
    }

    public function vender(int $id): void
    {
        $this->account($id);
        $token = $this->validateToken('vender', $id);
        try {
            $date = (string) ($_POST['fecha_venta'] ?? date('Y-m-d'));
            $this->validateDate($date);
            $sale = $this->cuentas->sell($id, (int) ($_POST['dispositivos'] ?? 0), $date, $token);
            audit(static::SERVICE . '.vender', 'Cuenta ' . $id . ' / venta ' . $sale);
            flash('success', 'Venta registrada. Puedes copiar la entrega sin volver a consumir cupos.');
            $this->redirect($this->path('/' . $id) . '?venta=' . $sale);
        } catch (\Throwable $error) {
            $this->operationError($error);
            $this->redirect($this->path('/' . $id));
        }
    }

    public function liberar(int $id): void
    {
        $this->requireAdmin();
        $this->account($id);
        $token = $this->validateToken('liberar', $id);
        try {
            $released = $this->cuentas->releaseSlots($id, trim((string) ($_POST['motivo'] ?? '')), (string) (auth_user()['username'] ?? ''));
            unset($_SESSION[static::SERVICE . '_operaciones'][$token]);
            audit(static::SERVICE . '.liberar', 'Cuenta ' . $id . ' / ' . $released . ' ' . static::UNIT);
            flash('success', 'Se liberaron ' . $released . ' cupos. El historial de ventas y pagos se conserva.');
        } catch (\Throwable $error) {
            $this->operationError($error);
        }
        $this->redirect($this->path('/' . $id));
    }

    public function toggleActive(int $id): void
    {
        $this->requireAdmin();
        $this->account($id);
        $token = $this->validateToken('estado', $id);
        try {
            $this->cuentas->toggleActive($id);
            unset($_SESSION[static::SERVICE . '_operaciones'][$token]);
            audit(static::SERVICE . '.estado', 'Cuenta ' . $id);
            flash('success', 'Estado de la cuenta actualizado.');
        } catch (\Throwable $error) {
            $this->operationError($error);
        }
        $this->redirect($this->path('/' . $id));
    }

    public function templates(): void
    {
        $this->requireAdmin();
        $this->render(static::SERVICE . '/templates', ['pageTitle' => 'Textos ' . static::LABEL, 'templates' => $this->cuentas->templates(), 'canAdmin' => true]);
    }

    public function saveTemplates(): void
    {
        $this->requireAdmin();
        try {
            $data = [];
            $modelClass = static::MODEL;
            foreach ($modelClass::TEMPLATE_KEYS as $key) {
                $data[$key] = trim((string) ($_POST[$key] ?? ''));
                if (mb_strlen($data[$key]) > 10000) {
                    throw new \InvalidArgumentException('Cada texto puede tener hasta 10.000 caracteres.');
                }
            }
            $this->cuentas->saveTemplates($data);
            audit(static::SERVICE . '.plantillas', 'Textos de entrega y renovación');
            flash('success', 'Textos guardados. Los campos vacíos quedan pendientes de configurar.');
        } catch (\Throwable $error) {
            $this->operationError($error);
        }
        $this->redirect($this->path('/plantillas'));
    }

    protected function account(int $id): array
    {
        $cuenta = $this->cuentas->find($id);
        if ($cuenta === null) {
            flash('danger', 'Cuenta ' . static::LABEL . ' no encontrada.');
            $this->redirect($this->path());
        }
        return $cuenta;
    }

    private function accountPayload(): array
    {
        return [
            'etiqueta' => trim((string) ($_POST['etiqueta'] ?? '')),
            'correo' => trim((string) ($_POST['correo'] ?? '')),
            'password_cuenta' => (string) ($_POST['password_cuenta'] ?? ''),
            'notas' => trim((string) ($_POST['notas'] ?? '')),
        ];
    }

    private function messageContext(array $cuenta, string $password, int $devices, string $due, string $price): array
    {
        return [
            'CUENTA' => (string) $cuenta['etiqueta'], 'CORREO' => (string) ($cuenta['correo'] ?? ''),
            'PASSWORD' => $password, 'DISPOSITIVOS' => $devices > 0 ? (string) $devices : '',
            'DISPOSITIVO_LABEL' => $devices > 0 ? ($devices === 1 ? 'dispositivo' : 'dispositivos') : '',
            'CUPOS' => $devices > 0 ? (string) $devices : '',
            'PRECIO' => $price !== '' ? money($price) : '', 'FECHA_VENCE' => $due,
        ];
    }

    protected function canAdmin(): bool
    {
        // Consultar el rol actual evita conservar permisos de una sesión antigua.
        $user = (new \App\Models\User())->findById((int) (auth_user()['id'] ?? 0));
        return $user !== null && (int) ($user['activo'] ?? 1) === 1 && ($user['rol'] ?? '') === 'admin';
    }

    protected function path(string $suffix = ''): string
    {
        return '/' . static::SERVICE . $suffix;
    }

    private function capacity(): int
    {
        $modelClass = static::MODEL;
        return $modelClass::CAPACIDAD;
    }

    protected function requireAdmin(): void
    {
        if (!$this->canAdmin()) {
            flash('danger', 'Esta acción está disponible para administradores.');
            $this->redirect($this->path());
        }
    }

    protected function issueToken(string $purpose, int $account): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[static::SERVICE . '_operaciones'][$token] = ['accion' => $purpose, 'cuenta' => $account];
        while (count($_SESSION[static::SERVICE . '_operaciones']) > 100) {
            $oldest = array_key_first($_SESSION[static::SERVICE . '_operaciones']);
            unset($_SESSION[static::SERVICE . '_operaciones'][$oldest], $_SESSION[static::SERVICE . '_creadas'][$oldest]);
        }
        return $token;
    }

    protected function validateToken(string $purpose, int $id): string
    {
        $token = (string) ($_POST['token_operacion'] ?? '');
        $stored = $_SESSION[static::SERVICE . '_operaciones'][$token] ?? null;
        if (!is_array($stored) || $stored['accion'] !== $purpose || $stored['cuenta'] !== $id) {
            flash('danger', 'El formulario expiró. Vuelve a abrir la cuenta e intenta otra vez.');
            $this->redirect($id > 0 ? $this->path('/' . $id) : $this->path());
        }
        return $token;
    }

    protected function validateDate(string $value): void
    {
        $date = parse_ymd_date($value);
        if ($date === null || $date > new DateTimeImmutable('today')) {
            throw new \InvalidArgumentException('Selecciona una fecha válida que no sea futura.');
        }
    }

    protected function operationError(\Throwable $error): void
    {
        if ($error instanceof \InvalidArgumentException || ($error instanceof \RuntimeException && !$error instanceof \PDOException)) {
            flash('danger', $error->getMessage());
        } else {
            app_log('error', static::LABEL . ': ' . $error->getMessage());
            flash('danger', 'No se pudo completar la operación. Intenta nuevamente.');
        }
    }
}

