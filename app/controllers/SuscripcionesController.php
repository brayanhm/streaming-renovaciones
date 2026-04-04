<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;
use App\Models\Modalidad;
use App\Models\Movimiento;
use App\Models\Plataforma;
use App\Models\Suscripcion;
use DateTimeImmutable;

class SuscripcionesController extends Controller
{
    private Suscripcion $suscripciones;
    private Cliente $clientes;
    private Plataforma $plataformas;
    private Modalidad $modalidades;

    public function __construct()
    {
        $this->suscripciones = new Suscripcion();
        $this->clientes = new Cliente();
        $this->plataformas = new Plataforma();
        $this->modalidades = new Modalidad();
    }

    public function index(): void
    {
        $this->suscripciones->recalculateStates(RECUP_DAYS);

        $search = trim((string) ($_GET['q'] ?? ''));
        $estado = strtoupper(trim((string) ($_GET['estado'] ?? '')));
        $selectedClientId = (int) ($_GET['cliente_id'] ?? 0);
        if (!in_array($estado, Suscripcion::ESTADOS, true)) {
            $estado = '';
        }

        $perPage = PER_PAGE;
        $total = $this->suscripciones->count($search, $estado);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min((int) ($_GET['page'] ?? 1), $totalPages));
        $rows = $this->suscripciones->all($search, $estado, 'TODOS', '', '', '', $perPage, ($page - 1) * $perPage);

        $clientes = $this->clientes->all();
        if ($selectedClientId > 0 && $this->clientes->find($selectedClientId) === null) {
            $selectedClientId = 0;
        }
        $plataformas = $this->plataformas->all();
        $tiposSuscripcion = $this->modalidades->all();

        $this->render('suscripciones/index', [
            'pageTitle' => 'Suscripciones',
            'rows' => $rows,
            'clientes' => $clientes,
            'plataformas' => $plataformas,
            'tiposSuscripcion' => $tiposSuscripcion,
            'search' => $search,
            'estado' => $estado,
            'estados' => Suscripcion::ESTADOS,
            'selectedClientId' => $selectedClientId,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $total,
            'perPage' => $perPage,
        ]);
    }

    public function historial(int $id): void
    {
        $suscripcion = $this->suscripciones->findWithRelations($id);
        if ($suscripcion === null) {
            flash('danger', 'Suscripcion no encontrada.');
            $this->redirect('/suscripciones');
        }

        $movimientos = (new Movimiento())->allBySuscripcion($id);

        $this->render('suscripciones/historial', [
            'pageTitle' => 'Historial de renovaciones',
            'suscripcion' => $suscripcion,
            'movimientos' => $movimientos,
        ]);
    }

    public function store(): void
    {
        $payload = $this->collectPayload();
        if ($payload === null) {
            $this->redirect('/suscripciones');
        }

        try {
            $this->suscripciones->create($payload);
            clear_old();
            flash('success', 'Suscripcion creada.');
        } catch (\Throwable $exception) {
            set_old($payload);
            flash('danger', 'No se pudo crear la suscripcion: ' . $exception->getMessage());
        }

        $this->redirect('/suscripciones');
    }

    public function edit(int $id): void
    {
        $item = $this->suscripciones->find($id);
        if ($item === null) {
            flash('danger', 'Suscripcion no encontrada.');
            $this->redirect('/suscripciones');
        }

        $clientes = $this->clientes->all();
        $plataformas = $this->plataformas->all();
        $tiposSuscripcion = $this->modalidades->all();

        $this->render('suscripciones/edit', [
            'pageTitle' => 'Editar suscripcion',
            'item' => $item,
            'clientes' => $clientes,
            'plataformas' => $plataformas,
            'tiposSuscripcion' => $tiposSuscripcion,
            'estados' => Suscripcion::ESTADOS,
        ]);
    }

    public function update(int $id): void
    {
        $payload = $this->collectPayload();
        if ($payload === null) {
            $this->redirect('/suscripciones/editar/' . $id);
        }

        try {
            $this->suscripciones->update($id, $payload);
            clear_old();
            flash('success', 'Suscripcion actualizada.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo actualizar la suscripcion: ' . $exception->getMessage());
        }

        $this->redirect('/suscripciones');
    }

    public function updateDueDate(int $id): void
    {
        $subscription = $this->suscripciones->find($id);
        if ($subscription === null) {
            flash('danger', 'Suscripcion no encontrada.');
            $this->goBack('/clientes');
        }

        $clienteId = (int) ($subscription['cliente_id'] ?? 0);
        $fallbackPath = $clienteId > 0 ? '/clientes/' . $clienteId : '/clientes';
        $rawDueDate = trim((string) ($_POST['fecha_vencimiento'] ?? ''));
        if ($rawDueDate === '') {
            flash('danger', 'Debes indicar la nueva fecha de finalizacion.');
            $this->goBack($fallbackPath);
        }

        try {
            $dueDate = new DateTimeImmutable($rawDueDate);
        } catch (\Throwable) {
            flash('danger', 'La fecha de finalizacion no es valida.');
            $this->goBack($fallbackPath);
        }

        $startDateRaw = (string) ($subscription['fecha_inicio'] ?? '');
        if ($startDateRaw !== '' && $dueDate->format('Y-m-d') < $startDateRaw) {
            flash('danger', 'La fecha de finalizacion no puede ser anterior a la fecha de inicio.');
            $this->goBack($fallbackPath);
        }

        try {
            $ok = $this->suscripciones->updateDueDate($id, $dueDate->format('Y-m-d'));
            if (!$ok) {
                flash('danger', 'No se pudo actualizar la fecha de finalizacion.');
                $this->goBack($fallbackPath);
            }
            flash('success', 'Fecha de finalizacion actualizada a ' . $dueDate->format('Y-m-d') . '.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo actualizar la finalizacion: ' . $exception->getMessage());
        }

        $this->goBack($fallbackPath);
    }

    public function destroy(int $id): void
    {
        try {
            $this->suscripciones->delete($id);
            flash('success', 'Suscripcion eliminada.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo eliminar la suscripcion: ' . $exception->getMessage());
        }

        $this->redirect('/suscripciones');
    }

    private function collectPayload(): ?array
    {
        $payload = [
            'cliente_id' => (int) ($_POST['cliente_id'] ?? 0),
            'plataforma_id' => (int) ($_POST['plataforma_id'] ?? 0),
            'modalidad_id' => (int) ($_POST['modalidad_id'] ?? 0),
            'costo_base' => trim((string) ($_POST['costo_base'] ?? '')),
            'precio_venta' => trim((string) ($_POST['precio_venta'] ?? '')),
            'fecha_inicio' => trim((string) ($_POST['fecha_inicio'] ?? '')),
            'fecha_vencimiento' => trim((string) ($_POST['fecha_vencimiento'] ?? '')),
            'estado' => strtoupper(trim((string) ($_POST['estado'] ?? 'ACTIVO'))),
            'usuario_proveedor' => trim((string) ($_POST['usuario_proveedor'] ?? '')),
            'notas' => trim((string) ($_POST['notas'] ?? '')),
            'flag_no_renovo' => isset($_POST['flag_no_renovo']) ? 1 : 0,
        ];

        if (
            $payload['cliente_id'] <= 0 ||
            $payload['plataforma_id'] <= 0 ||
            $payload['modalidad_id'] <= 0 ||
            $payload['fecha_inicio'] === '' ||
            $payload['fecha_vencimiento'] === '' ||
            !in_array($payload['estado'], Suscripcion::ESTADOS, true)
        ) {
            set_old($payload);
            flash('danger', 'Completa todos los campos obligatorios de la suscripcion.');

            return null;
        }

        if ($payload['fecha_inicio'] > $payload['fecha_vencimiento']) {
            set_old($payload);
            flash('danger', 'La fecha de inicio no puede ser posterior a la fecha de vencimiento.');

            return null;
        }

        $modalidad = $this->modalidades->find($payload['modalidad_id']);
        if ($modalidad === null || (int) $modalidad['plataforma_id'] !== $payload['plataforma_id']) {
            set_old($payload);
            flash('danger', 'El plan seleccionado no corresponde a la plataforma elegida.');

            return null;
        }

        $plataforma = $this->plataformas->find($payload['plataforma_id']);
        if ($plataforma === null) {
            set_old($payload);
            flash('danger', 'La plataforma seleccionada no existe.');

            return null;
        }

        $tipoServicio = strtoupper((string) ($plataforma['tipo_servicio'] ?? ''));
        $datoRenovacion = Plataforma::normalizeDatoRenovacion(
            isset($plataforma['dato_renovacion']) ? (string) $plataforma['dato_renovacion'] : null,
            $tipoServicio
        );

        if ($tipoServicio === 'RENOVABLE') {
            if ($payload['usuario_proveedor'] === '') {
                set_old($payload);
                $fieldLabel = $datoRenovacion === 'CORREO' ? 'correo' : 'usuario';
                flash('danger', 'Debes indicar el ' . $fieldLabel . ' de la cuenta para renovar esta plataforma.');

                return null;
            }

            if ($datoRenovacion === 'CORREO' && filter_var($payload['usuario_proveedor'], FILTER_VALIDATE_EMAIL) === false) {
                set_old($payload);
                flash('danger', 'Debes ingresar un correo valido para la cuenta de renovacion.');

                return null;
            }
        } else {
            $payload['usuario_proveedor'] = '';
        }

        $normalizedPrice = $payload['precio_venta'] === ''
            ? null
            : normalize_decimal_amount($payload['precio_venta']);
        if ($payload['precio_venta'] !== '' && $normalizedPrice === null) {
            set_old($payload);
            flash('danger', 'El precio de venta debe ser un numero valido mayor a 0.');

            return null;
        }

        $normalizedCost = $payload['costo_base'] === ''
            ? null
            : normalize_decimal_amount($payload['costo_base']);
        if ($payload['costo_base'] !== '' && $normalizedCost === null) {
            set_old($payload);
            flash('danger', 'El costo debe ser un numero valido mayor a 0.');

            return null;
        }

        $costValue = $normalizedCost === null
            ? (float) (normalize_decimal_amount((string) ($modalidad['costo'] ?? '0')) ?? '0')
            : (float) $normalizedCost;
        $priceValue = $normalizedPrice === null
            ? (float) (normalize_decimal_amount((string) ($modalidad['precio'] ?? '0')) ?? '0')
            : (float) $normalizedPrice;
        if ($costValue <= 0) {
            set_old($payload);
            flash('danger', 'El costo debe ser mayor a 0.');

            return null;
        }
        if ($priceValue <= 0) {
            set_old($payload);
            flash('danger', 'El precio de venta debe ser mayor a 0.');

            return null;
        }

        $payload['costo_base'] = number_format($costValue, 2, '.', '');
        $payload['precio_venta'] = number_format($priceValue, 2, '.', '');

        return $payload;
    }
}
