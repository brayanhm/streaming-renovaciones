<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;
use App\Models\Modalidad;
use App\Models\Plataforma;
use App\Models\Suscripcion;
use DateTimeImmutable;

class ClientesController extends Controller
{
    private Cliente $clientes;
    private Plataforma $plataformas;
    private Modalidad $modalidades;
    private Suscripcion $suscripciones;

    public function __construct()
    {
        $this->clientes = new Cliente();
        $this->plataformas = new Plataforma();
        $this->modalidades = new Modalidad();
        $this->suscripciones = new Suscripcion();
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $perPage = PER_PAGE;
        $total = $this->clientes->count($search);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min((int) ($_GET['page'] ?? 1), $totalPages));
        $rows = $this->clientes->all($search, $perPage, ($page - 1) * $perPage);
        $plataformas = $this->plataformas->all();
        $tiposSuscripcion = $this->modalidades->all();
        $missingContactCount = $this->clientes->countMissingContactData();

        $this->render('clientes/index', [
            'pageTitle' => 'Clientes',
            'rows' => $rows,
            'search' => $search,
            'plataformas' => $plataformas,
            'tiposSuscripcion' => $tiposSuscripcion,
            'missingContactCount' => $missingContactCount,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $total,
            'perPage' => $perPage,
        ]);
    }

    public function show(int $id): void
    {
        $item = $this->clientes->find($id);
        if ($item === null) {
            flash('danger', 'Cliente no encontrado.');
            $this->redirect('/clientes');
        }

        $suscripciones = $this->suscripciones->allByCliente($id);

        $this->render('clientes/show', [
            'pageTitle' => 'Cliente: ' . ($item['nombre'] ?? ''),
            'item' => $item,
            'suscripciones' => $suscripciones,
        ]);
    }

    public function completar(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $rows = $this->clientes->missingContactData($search);

        $this->render('clientes/completar', [
            'pageTitle' => 'Completar contactos',
            'rows' => $rows,
            'search' => $search,
        ]);
    }

    public function store(): void
    {
        $payload = [
            'nombre' => trim((string) ($_POST['contacto'] ?? $_POST['nombre'] ?? '')),
            'telefono' => trim((string) ($_POST['numero'] ?? $_POST['telefono'] ?? '')),
            'notas' => trim((string) ($_POST['notas'] ?? '')),
            'plataforma_id' => (int) ($_POST['plataforma_id'] ?? 0),
            'modalidad_id' => (int) ($_POST['modalidad_id'] ?? 0),
            'usuario_proveedor' => trim((string) ($_POST['usuario_proveedor'] ?? '')),
        ];

        if (
            $payload['telefono'] === '' ||
            $payload['plataforma_id'] <= 0 ||
            $payload['modalidad_id'] <= 0
        ) {
            set_old($payload);
            flash('danger', 'Completa el número, la plataforma y el plan de suscripción.');
            $this->redirect('/clientes');
        }

        $payload['telefono'] = normalize_whatsapp_phone_bolivia($payload['telefono']);
        if ($payload['telefono'] === '' || !is_valid_whatsapp_phone_bolivia($payload['telefono'])) {
            set_old($payload);
            flash('danger', 'Número inválido. Para Bolivia usa celular de 8 dígitos (inicia con 6 o 7), con o sin +591.');
            $this->redirect('/clientes');
        }

        $modalidad = $this->modalidades->find($payload['modalidad_id']);
        if ($modalidad === null || (int) $modalidad['plataforma_id'] !== $payload['plataforma_id']) {
            set_old($payload);
            flash('danger', 'El plan seleccionado no corresponde a la plataforma elegida.');
            $this->redirect('/clientes');
        }

        $plataforma = $this->plataformas->find($payload['plataforma_id']);
        if ($plataforma === null) {
            set_old($payload);
            flash('danger', 'La plataforma seleccionada no existe.');
            $this->redirect('/clientes');
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
                flash('danger', 'Debes indicar el ' . $fieldLabel . ' de la cuenta para la suscripción inicial.');
                $this->redirect('/clientes');
            }

            if ($datoRenovacion === 'CORREO' && filter_var($payload['usuario_proveedor'], FILTER_VALIDATE_EMAIL) === false) {
                set_old($payload);
                flash('danger', 'Debes ingresar un correo válido para la cuenta de la suscripción inicial.');
                $this->redirect('/clientes');
            }
        } else {
            $payload['usuario_proveedor'] = '';
        }

        $today = new DateTimeImmutable('today');
        $duracion = max(1, (int) ($modalidad['duracion_meses'] ?? 1));
        $fechaInicio = $today->format('Y-m-d');
        $fechaVencimiento = shift_months_clamped($today, $duracion)->format('Y-m-d');

        $clientePayload = [
            'nombre' => $payload['nombre'],
            'telefono' => $payload['telefono'],
            'notas' => $payload['notas'],
        ];
        $suscripcionPayload = [
            'plataforma_id' => $payload['plataforma_id'],
            'modalidad_id' => $payload['modalidad_id'],
            'costo_base' => normalize_decimal_amount((string) ($modalidad['costo'] ?? '0')) ?? '0.00',
            'precio_venta' => normalize_decimal_amount((string) ($modalidad['precio'] ?? '0')) ?? '0.00',
            'fecha_inicio' => $fechaInicio,
            'fecha_vencimiento' => $fechaVencimiento,
            'estado' => 'ACTIVO',
            'usuario_proveedor' => $payload['usuario_proveedor'],
            'flag_no_renovo' => 0,
        ];

        $pdo = db();
        try {
            $pdo->beginTransaction();
            $clienteId = $this->clientes->create($clientePayload);
            $suscripcionPayload['cliente_id'] = $clienteId;
            $this->suscripciones->create($suscripcionPayload);
            $pdo->commit();
            clear_old();
            flash('success', 'Cliente y suscripción inicial creados correctamente.');
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_old($payload);
            flash('danger', 'No se pudo crear el cliente con su suscripción inicial: ' . $exception->getMessage());
        }

        $this->redirect('/clientes');
    }

    public function storeAntiguo(): void
    {
        $payload = [
            'contacto_antiguo' => trim((string) ($_POST['contacto_antiguo'] ?? $_POST['nombre_antiguo'] ?? '')),
            'numero_antiguo' => trim((string) ($_POST['numero_antiguo'] ?? $_POST['telefono_antiguo'] ?? '')),
            'notas_antiguo' => trim((string) ($_POST['notas_antiguo'] ?? '')),
            'plataforma_id_antiguo' => (int) ($_POST['plataforma_id_antiguo'] ?? 0),
            'fecha_finalizacion' => trim((string) ($_POST['fecha_finalizacion'] ?? '')),
            'usuario_proveedor_antiguo' => trim((string) ($_POST['usuario_proveedor_antiguo'] ?? '')),
        ];

        if (
            $payload['numero_antiguo'] === '' ||
            $payload['plataforma_id_antiguo'] <= 0 ||
            $payload['fecha_finalizacion'] === ''
        ) {
            set_old($payload);
            flash('danger', 'Completa el número, la plataforma y la fecha de finalización.');
            $this->redirect('/clientes');
        }

        $payload['numero_antiguo'] = normalize_whatsapp_phone_bolivia($payload['numero_antiguo']);
        if ($payload['numero_antiguo'] === '' || !is_valid_whatsapp_phone_bolivia($payload['numero_antiguo'])) {
            set_old($payload);
            flash('danger', 'Numero invalido. Para Bolivia usa celular de 8 digitos (inicia con 6 o 7), con o sin +591.');
            $this->redirect('/clientes');
        }

        $fechaVencimiento = parse_ymd_date($payload['fecha_finalizacion']);
        if ($fechaVencimiento === null) {
            set_old($payload);
            flash('danger', 'La fecha de finalizacion no es valida.');
            $this->redirect('/clientes');
        }

        $plataforma = $this->plataformas->find($payload['plataforma_id_antiguo']);
        if ($plataforma === null) {
            set_old($payload);
            flash('danger', 'La plataforma seleccionada no existe.');
            $this->redirect('/clientes');
        }

        $tipoServicio = strtoupper((string) ($plataforma['tipo_servicio'] ?? ''));
        $datoRenovacion = Plataforma::normalizeDatoRenovacion(
            isset($plataforma['dato_renovacion']) ? (string) $plataforma['dato_renovacion'] : null,
            $tipoServicio
        );

        if ($tipoServicio === 'RENOVABLE') {
            if ($payload['usuario_proveedor_antiguo'] === '') {
                set_old($payload);
                $fieldLabel = $datoRenovacion === 'CORREO' ? 'correo' : 'usuario';
                flash('danger', 'Debes indicar el ' . $fieldLabel . ' de la cuenta para este cliente antiguo.');
                $this->redirect('/clientes');
            }

            if (
                $datoRenovacion === 'CORREO' &&
                filter_var($payload['usuario_proveedor_antiguo'], FILTER_VALIDATE_EMAIL) === false
            ) {
                set_old($payload);
                flash('danger', 'Debes ingresar un correo valido para la cuenta del cliente antiguo.');
                $this->redirect('/clientes');
            }
        } else {
            $payload['usuario_proveedor_antiguo'] = '';
        }

        $modalidad = $this->modalidades->firstByPlataforma($payload['plataforma_id_antiguo']);
        if ($modalidad === null) {
            set_old($payload);
            flash('danger', 'La plataforma no tiene planes para crear la suscripcion.');
            $this->redirect('/clientes');
        }

        $duracion = max(1, (int) ($modalidad['duracion_meses'] ?? 1));
        $fechaInicio = shift_months_clamped($fechaVencimiento, -$duracion)->format('Y-m-d');
        $fechaVencimientoStr = $fechaVencimiento->format('Y-m-d');
        $today = new DateTimeImmutable('today');
        $daysToDue = (int) $today->diff($fechaVencimiento)->format('%r%a');
        $isExpired = $daysToDue < 0;
        $estado = 'ACTIVO';
        if ($isExpired) {
            $estado = abs($daysToDue) >= RECUP_DAYS ? 'RECUP' : 'VENCIDO';
        }

        $clientePayload = [
            'nombre' => $payload['contacto_antiguo'],
            'telefono' => $payload['numero_antiguo'],
            'notas' => $payload['notas_antiguo'],
        ];
        $suscripcionPayload = [
            'plataforma_id' => $payload['plataforma_id_antiguo'],
            'modalidad_id' => (int) $modalidad['id'],
            'costo_base' => normalize_decimal_amount((string) ($modalidad['costo'] ?? '0')) ?? '0.00',
            'precio_venta' => normalize_decimal_amount((string) ($modalidad['precio'] ?? '0')) ?? '0.00',
            'fecha_inicio' => $fechaInicio,
            'fecha_vencimiento' => $fechaVencimientoStr,
            'estado' => $estado,
            'usuario_proveedor' => $payload['usuario_proveedor_antiguo'],
            'flag_no_renovo' => 0,
        ];

        $pdo = db();
        try {
            $pdo->beginTransaction();
            $clienteId = $this->clientes->create($clientePayload);
            $suscripcionPayload['cliente_id'] = $clienteId;
            $this->suscripciones->create($suscripcionPayload);
            $pdo->commit();
            clear_old();
            flash('success', 'Cliente antiguo creado con fecha de finalizacion ' . $fechaVencimientoStr . '.');
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_old($payload);
            flash('danger', 'No se pudo crear el cliente antiguo: ' . $exception->getMessage());
        }

        $this->redirect('/clientes');
    }

    public function edit(int $id): void
    {
        $item = $this->clientes->find($id);
        if ($item === null) {
            flash('danger', 'Cliente no encontrado.');
            $this->redirect('/clientes');
        }

        $this->render('clientes/edit', [
            'pageTitle' => 'Editar cliente',
            'item' => $item,
        ]);
    }

    public function update(int $id): void
    {
        if ($this->clientes->find($id) === null) {
            flash('danger', 'Cliente no encontrado.');
            $this->redirect('/clientes');
        }

        $payload = [
            'nombre' => trim((string) ($_POST['contacto'] ?? $_POST['nombre'] ?? '')),
            'telefono' => trim((string) ($_POST['numero'] ?? $_POST['telefono'] ?? '')),
            'notas' => trim((string) ($_POST['notas'] ?? '')),
        ];

        if ($payload['telefono'] === '') {
            flash('danger', 'El número es obligatorio.');
            $this->redirect('/clientes/editar/' . $id);
        }

        $payload['telefono'] = normalize_whatsapp_phone_bolivia($payload['telefono']);
        if ($payload['telefono'] === '' || !is_valid_whatsapp_phone_bolivia($payload['telefono'])) {
            flash('danger', 'Número inválido. Para Bolivia usa celular de 8 dígitos (inicia con 6 o 7), con o sin +591.');
            $this->redirect('/clientes/editar/' . $id);
        }

        $this->clientes->update($id, $payload);
        flash('success', 'Cliente actualizado.');
        $this->redirect('/clientes');
    }

    public function updateMissingContact(int $id): void
    {
        $item = $this->clientes->find($id);
        if ($item === null) {
            flash('danger', 'Cliente no encontrado.');
            $this->redirect('/clientes/completar');
        }

        $contacto = trim((string) ($_POST['contacto'] ?? ''));
        $numero = normalize_whatsapp_phone_bolivia(trim((string) ($_POST['numero'] ?? '')));

        if ($numero === '' || !is_valid_whatsapp_phone_bolivia($numero)) {
            flash('danger', 'Ingresa un número celular válido de Bolivia (8 dígitos, inicia con 6 o 7).');
            $this->redirect('/clientes/completar');
        }

        $ok = $this->clientes->update($id, [
            'nombre' => $contacto,
            'telefono' => $numero,
            'notas' => (string) ($item['notas'] ?? ''),
        ]);

        if (!$ok) {
            flash('danger', 'No se pudo actualizar el registro.');
            $this->redirect('/clientes/completar');
        }

        flash('success', 'Contacto y número actualizados.');
        $this->redirect('/clientes/completar');
    }

    public function destroy(int $id): void
    {
        if ($this->clientes->find($id) === null) {
            flash('danger', 'Cliente no encontrado.');
            $this->redirect('/clientes');
        }

        try {
            $this->clientes->delete($id);
            flash('success', 'Cliente eliminado.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo eliminar el cliente: ' . $exception->getMessage());
        }

        $this->redirect('/clientes');
    }
}

