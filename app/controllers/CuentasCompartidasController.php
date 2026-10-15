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
            'search' => $search, 'canAdmin' => $this->canAdmin(), 'distribucion' => $this->cuentas->currentDistribution(),
            'creationToken' => $this->issueToken('crear', 0),
            'importToken' => static::SERVICE === 'chatgpt' ? $this->issueToken('importar', 0) : '',
            'avisosChatgpt' => static::SERVICE === 'chatgpt' ? (new \App\Models\ChatgptAvisos())->resumen() : null,
        ]);
    }

    public function show(int $id): void
    {
        $modelClass = static::MODEL;
        $cuenta = $this->account($id);
        $templates = $this->cuentas->deliveryTemplates();
        $password = decrypt_secret((string) ($cuenta['password_cuenta'] ?? ''));
        $credentialsReady = filter_var((string) ($cuenta['correo'] ?? ''), FILTER_VALIDATE_EMAIL) !== false && trim($password) !== '';
        $sales = $this->cuentas->sales($id);
        $saleTexts = [];
        $saleCopyReasons = [];
        foreach ($sales as $sale) {
            $reason = $modelClass::deliveryUnavailableReason($cuenta, $sale);
            $saleCopyReasons[(int) $sale['id']] = $reason ?? (!$credentialsReady ? 'Credenciales pendientes de completar.' : 'Mensaje pendiente de configurar.');
            $saleTexts[(int) $sale['id']] = $credentialsReady && $reason === null ? $modelClass::renderMessage(
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
        if (empty($saleTexts[$lastSaleId])) {
            $lastSaleId = null;
        }
        $payments = $this->cuentas->payments($id);
        $canReviewPeriod = static::SERVICE === 'chatgpt' && (int) ($cuenta['revision_vigencia_pendiente'] ?? 0) === 1 && $payments === [];
        $this->render(static::SERVICE . '/show', [
            'pageTitle' => static::LABEL . ' · ' . $cuenta['etiqueta'], 'cuenta' => $cuenta,
            'password' => $password, 'pagos' => $payments, 'ventas' => $sales,
            'saleCopyReasons' => $saleCopyReasons, 'canReviewPeriod' => $canReviewPeriod,
            'templates' => $templates, 'saleTexts' => $saleTexts, 'entregaTexts' => $entregaTexts,
            'renewalText' => $modelClass::renderMessage($templates['renovacion'] ?? '', $this->messageContext($cuenta, $password, 0, $currentEnd, '')),
            'lastSaleId' => $lastSaleId, 'canAdmin' => $this->canAdmin(),
            'credentialsReady' => $credentialsReady,
            'saleToken' => $this->issueToken('vender', $id), 'paymentToken' => $this->issueToken('pagar', $id),
            'selectionToken' => $this->issueToken('distribuir', $id), 'cycleToken' => $this->issueToken('ciclo', $id, (int) $cuenta['ciclo_actual']),
            'planToken' => $this->issueToken('plan', $id), 'ciclos' => $this->cuentas->cycles($id),
            'stateToken' => $this->issueToken('estado', $id),
            'periodToken' => $canReviewPeriod ? $this->issueToken('vigencia', $id) : '',
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
                throw new \App\Core\ValidationException(['password_cuenta' => 'Ingresa la contraseña de la cuenta.']);
            }
            $id = $this->cuentas->create($data);
            $_SESSION[static::SERVICE . '_creadas'][$token] = $id;
            audit(static::SERVICE . '.crear', 'Cuenta ' . $id . ' con pago inicial');
            flash('success', 'Cuenta ' . static::LABEL . ' creada con ' . (int) $data['capacidad'] . ' ' . static::UNIT . ' y su pago inicial. Selecciónala para distribución cuando esté preparada.');
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
            'occupationToken' => $this->issueToken('ocupacion', $id),
            'occupationAdjustments' => $this->cuentas->occupationAdjustments($id),
        ]);
    }

    public function update(int $id): void
    {
        $this->requireAdmin();
        $account = $this->account($id);
        $data = $this->accountPayload((int) $account['capacidad']);
        try {
            $this->cuentas->update($id, $data);
            audit(static::SERVICE . '.editar', 'Cuenta ' . $id);
            flash('success', 'Datos de la cuenta actualizados.');
        } catch (\Throwable $error) {
            set_old($data);
            $this->operationError($error);
            $this->redirect($this->path('/' . $id) . '/editar');
        }
        $this->redirect($this->path('/' . $id));
    }

    public function adjustOccupation(int $id): void
    {
        $this->requireAdmin();
        $this->account($id);
        $token = $this->validateToken('ocupacion', $id);
        try {
            $result = $this->cuentas->adjustOccupation($id, $_POST['ocupados'] ?? null, $_POST['ocupados_antes'] ?? null,
                is_string($_POST['motivo'] ?? '') ? ($_POST['motivo'] ?? '') : '',
                (string) (auth_user()['username'] ?? ''), $token);
            audit(static::SERVICE . '.ocupacion', 'Cuenta ' . $id . ' / ' . $result['ocupados_antes'] . ' → ' . $result['ocupados_despues']);
            flash('success', 'Ocupación actualizada de ' . $result['ocupados_antes'] . ' a ' . $result['ocupados_despues'] . '. Las ventas y los pagos se conservan.');
        } catch (\Throwable $error) {
            $this->operationError($error);
            $this->redirect($this->path('/' . $id . '/editar') . '#ocupacion');
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
                ? 'Pago registrado: un mes pagado y un mes de gracia. Los cupos y la selección se conservan; inicia un nuevo ciclo cuando hayas cerrado las sesiones.'
                : 'Pago registrado: un mes de vigencia. Los cupos y la selección se conservan; inicia un nuevo ciclo cuando hayas cerrado las sesiones.');
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
            $this->validateDate($date, 'fecha_venta');
            $sale = $this->cuentas->sell($id, (int) ($_POST['dispositivos'] ?? 0), $date, $token, $this->canAdmin());
            audit(static::SERVICE . '.vender', 'Cuenta ' . $id . ' / venta ' . $sale);
            flash('success', 'Venta registrada. Puedes copiar la entrega sin volver a consumir cupos.');
            $this->redirect($this->path('/' . $id) . '?venta=' . $sale);
        } catch (\Throwable $error) {
            $this->operationError($error);
            $this->redirect($this->path('/' . $id));
        }
    }

    public function startCycle(int $id): void
    {
        $this->requireAdmin();
        $this->account($id);
        $token = $this->validateToken('ciclo', $id);
        try {
            $expectedCycle = $_SESSION[static::SERVICE . '_operaciones'][$token]['ciclo'] ?? null;
            if (!is_int($expectedCycle)) throw new \InvalidArgumentException('El formulario de ciclo expiró. Recarga la cuenta.');
            $released = $this->cuentas->startCycle($id, ($_POST['sesiones_cerradas'] ?? '') === '1', (string) (auth_user()['username'] ?? ''), $token, $expectedCycle);
            audit(static::SERVICE . '.ciclo', 'Cuenta ' . $id . ' / ' . $released . ' cupos liberados / cierre de sesiones confirmado');
            flash('success', 'Nuevo ciclo iniciado y cuenta seleccionada para distribución. Se reiniciaron los cupos; el historial de ventas y pagos se conserva.');
        } catch (\Throwable $error) {
            $this->operationError($error);
        }
        $this->redirect($this->path('/' . $id));
    }

    public function selectDistribution(int $id): void
    {
        $this->requireAdmin();
        $token = $this->validateToken('distribuir', $id);
        try {
            $this->cuentas->selectDistribution($id);
            unset($_SESSION[static::SERVICE . '_operaciones'][$token]);
            audit(static::SERVICE . '.distribuir', 'Cuenta ' . $id);
            flash('success', 'Cuenta seleccionada para distribución. Los cupos y accesos anteriores se conservan.');
        } catch (\Throwable $error) {
            $this->operationError($error);
        }
        $this->redirect($this->path('/' . $id));
    }

    public function setPlan(int $id): void
    {
        $this->requireAdmin();
        $token = $this->validateToken('plan', $id);
        try {
            $plan = (string) ($_POST['plan'] ?? '');
            if (!in_array($plan, ['GRATIS', 'RESTAURAR'], true)) {
                throw new \InvalidArgumentException('Selecciona un estado de plan válido.');
            }
            $this->cuentas->setFreePlan($id, $plan === 'GRATIS');
            unset($_SESSION[static::SERVICE . '_operaciones'][$token]);
            audit(static::SERVICE . '.plan', 'Cuenta ' . $id . ' / ' . $plan);
            flash('success', $plan === 'GRATIS' ? 'Cuenta marcada Gratis. Las ventas quedan bloqueadas.' : 'Plan restaurado según las fechas de vigencia registradas.');
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
            $active = $_POST['activo'] ?? null;
            if (!is_string($active) || !in_array($active, ['0', '1'], true)) throw new \InvalidArgumentException('Selecciona activar o desactivar la cuenta. Recarga el formulario.');
            $this->cuentas->setActive($id, $active === '1');
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
        $this->redirect($this->path('/distribucion'));
    }

    public function distribution(): void
    {
        $modelClass = static::MODEL;
        $account = $this->cuentas->currentDistribution();
        $canAdmin = $this->canAdmin();
        $credentialsReady = $account !== null
            && filter_var((string) ($account['correo'] ?? ''), FILTER_VALIDATE_EMAIL) !== false
            && trim(decrypt_secret((string) ($account['password_cuenta'] ?? ''))) !== '';
        $lastDelivery = null;
        if ($account !== null && $credentialsReady) {
            $last = $_SESSION[static::SERVICE . '_ultima_entrega'] ?? [];
            if ((int) ($last['cuenta'] ?? 0) === (int) $account['id']) {
                $sale = $this->cuentas->sale((int) $account['id'], (int) ($last['venta'] ?? 0));
                if ($sale !== null && $modelClass::deliveryUnavailableReason($account, $sale) === null) {
                    $lastDelivery = $this->deliveryData($account, $sale, $canAdmin);
                }
            }
        }
        $prices = [1 => $modelClass::salePrice(1)];
        if (static::SERVICE === 'chatgpt') {
            $prices[2] = $modelClass::salePrice(2);
        }
        $this->render('cuentas_compartidas/entrega', [
            'pageTitle' => 'Distribución de ' . static::LABEL, 'service' => static::SERVICE, 'serviceLabel' => static::LABEL,
            'unit' => static::UNIT, 'cuenta' => $account, 'canAdmin' => $canAdmin, 'prices' => $prices,
            'credentialsReady' => $credentialsReady, 'lastDelivery' => $lastDelivery,
            'deliveryToken' => $account !== null ? $this->issueToken('entregar', (int) $account['id']) : '',
        ]);
    }

    public function deliver(int $id): void
    {
        $status = 200;
        $saleId = null;
        try {
            $token = $this->operationToken('entregar', $id);
            $rawDevices = $_POST['dispositivos'] ?? '';
            if (!is_string($rawDevices) || !in_array($rawDevices, static::SERVICE === 'chatgpt' ? ['1', '2'] : ['1'], true)) {
                throw new \InvalidArgumentException('Selecciona una cantidad de dispositivos válida para este servicio.');
            }
            $devices = (int) $rawDevices;
            $canAdmin = $this->canAdmin();
            $date = $_SESSION[static::SERVICE . '_operaciones'][$token]['fecha_entrega'] ?? date('Y-m-d');
            $_SESSION[static::SERVICE . '_operaciones'][$token]['fecha_entrega'] = $date;
            $saleId = $this->cuentas->sell($id, $devices, $date, $token, $canAdmin);
            $account = $this->cuentas->find($id);
            $sale = $this->cuentas->sale($id, $saleId);
            if ($account === null || $sale === null || $sale['liberada_at'] !== null) {
                throw new \RuntimeException('La entrega pertenece a un ciclo anterior. Recarga la pantalla de distribución.');
            }
            $payload = $this->deliveryData($account, $sale, $canAdmin);
            $payload['nextToken'] = $this->issueToken('entregar', $id);
            $_SESSION[static::SERVICE . '_ultima_entrega'] = ['cuenta' => $id, 'venta' => $saleId];
            audit(static::SERVICE . '.entregar', 'Cuenta ' . $id . ' / venta ' . $saleId . ' / ' . $devices . ' ' . static::UNIT);
        } catch (\Throwable $error) {
            $status = 422;
            if ($error instanceof \InvalidArgumentException || ($error instanceof \RuntimeException && !$error instanceof \PDOException)) {
                $message = $error->getMessage();
            } else {
                $status = 500;
                app_log('error', static::LABEL . ' entrega: ' . $error->getMessage());
                $message = 'No se pudo confirmar la entrega. Reintenta con la misma cantidad para recuperar el resultado sin duplicar cupos.';
            }
            $payload = ['ok' => false, 'message' => $message, 'retrySameQuantity' => $saleId !== null || $status === 500];
        }
        $this->jsonResponse($payload, $status);
    }

    private function deliveryData(array $account, array $sale, bool $canAdmin): array
    {
        $modelClass = static::MODEL;
        $reason = $modelClass::deliveryUnavailableReason($account, $sale);
        if ($reason !== null) throw new \RuntimeException($reason . ' Recarga la pantalla de distribución.');
        $devices = (int) $sale['dispositivos'];
        $templates = $this->cuentas->deliveryTemplates();
        $password = decrypt_secret((string) ($account['password_cuenta'] ?? ''));
        if (trim($password) === '') {
            throw new \RuntimeException('La contraseña está pendiente de completar. El administrador debe revisar la cuenta.');
        }
        $text = $modelClass::renderMessage($templates['entrega_' . $devices],
            $this->messageContext($account, $password, $devices, (string) $sale['fecha_vencimiento'], (string) $sale['monto_bs']));
        return ['ok' => true, 'saleId' => (int) $sale['id'], 'devices' => $devices, 'text' => $text,
            'occupied' => (int) $account['ocupados'], 'capacity' => (int) $account['capacidad'],
            'remaining' => (int) $account['libres'], 'state' => $account['estado_distribucion'], 'canExceed' => $canAdmin,
            'eligible' => (int) $account['activo'] === 1 && $account['distribucion_actual']
                && in_array($account['estado_periodo'], ['PAGADO', 'GRACIA'], true)];
    }

    protected function jsonResponse(array $data, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    protected function operationToken(string $purpose, int $id): string
    {
        $token = $_POST['token_operacion'] ?? '';
        if (!is_string($token)) {
            throw new \InvalidArgumentException('El formulario no es válido. Recarga la pantalla de distribución.');
        }
        $stored = $_SESSION[static::SERVICE . '_operaciones'][$token] ?? null;
        if (!is_array($stored) || $stored['accion'] !== $purpose || $stored['cuenta'] !== $id) {
            throw new \InvalidArgumentException('El formulario expiró. Recarga la pantalla de distribución e intenta otra vez.');
        }
        return $token;
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

    private function accountPayload(?int $currentCapacity = null): array
    {
        return [
            'etiqueta' => trim((string) ($_POST['etiqueta'] ?? '')),
            'correo' => trim((string) ($_POST['correo'] ?? '')),
            'password_cuenta' => (string) ($_POST['password_cuenta'] ?? ''),
            'notas' => trim((string) ($_POST['notas'] ?? '')),
            'capacidad' => (string) ($_POST['capacidad'] ?? $currentCapacity ?? $this->capacity()),
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

    protected function issueToken(string $purpose, int $account, ?int $cycle = null): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[static::SERVICE . '_operaciones'][$token] = ['accion' => $purpose, 'cuenta' => $account];
        if ($purpose === 'ciclo') $_SESSION[static::SERVICE . '_operaciones'][$token]['ciclo'] = $cycle ?? $this->cuentas->currentCycleId($account);
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

    protected function validateDate(string $value, string $field = 'fecha_pago'): void
    {
        $date = parse_ymd_date($value);
        if ($date === null || $date > new DateTimeImmutable('today')) {
            throw new \App\Core\ValidationException([$field => 'Selecciona una fecha válida que no sea futura.']);
        }
    }

    protected function operationError(\Throwable $error): void
    {
        if ($error instanceof \App\Core\ValidationException) set_field_errors($error->fields);
        if ($error instanceof \InvalidArgumentException || ($error instanceof \RuntimeException && !$error instanceof \PDOException)) {
            flash('danger', $error->getMessage());
        } else {
            app_log('error', static::LABEL . ': ' . $error->getMessage());
            flash('danger', 'No se pudo completar la operación. Intenta nuevamente.');
        }
    }
}

