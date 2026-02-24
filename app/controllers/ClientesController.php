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
        $rows = $this->clientes->all($search);
        $plataformas = $this->plataformas->all();
        $tiposSuscripcion = $this->modalidades->all();

        $this->render('clientes/index', [
            'pageTitle' => 'Clientes',
            'rows' => $rows,
            'search' => $search,
            'plataformas' => $plataformas,
            'tiposSuscripcion' => $tiposSuscripcion,
        ]);
    }

    public function store(): void
    {
        $payload = [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'telefono' => trim((string) ($_POST['telefono'] ?? '')),
            'notas' => trim((string) ($_POST['notas'] ?? '')),
            'plataforma_id' => (int) ($_POST['plataforma_id'] ?? 0),
            'modalidad_id' => (int) ($_POST['modalidad_id'] ?? 0),
            'usuario_proveedor' => trim((string) ($_POST['usuario_proveedor'] ?? '')),
        ];

        if (
            $payload['nombre'] === '' ||
            $payload['telefono'] === '' ||
            $payload['plataforma_id'] <= 0 ||
            $payload['modalidad_id'] <= 0
        ) {
            set_old($payload);
            flash('danger', 'Completa nombre, telefono, plataforma y plan de suscripcion.');
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
                flash('danger', 'Debes indicar el ' . $fieldLabel . ' de la cuenta para la suscripcion inicial.');
                $this->redirect('/clientes');
            }

            if ($datoRenovacion === 'CORREO' && filter_var($payload['usuario_proveedor'], FILTER_VALIDATE_EMAIL) === false) {
                set_old($payload);
                flash('danger', 'Debes ingresar un correo valido para la cuenta de la suscripcion inicial.');
                $this->redirect('/clientes');
            }
        } else {
            $payload['usuario_proveedor'] = '';
        }

        $today = new DateTimeImmutable('today');
        $duracion = max(1, (int) ($modalidad['duracion_meses'] ?? 1));
        $fechaInicio = $today->format('Y-m-d');
        $fechaVencimiento = $today->modify('+' . $duracion . ' months')->format('Y-m-d');

        $clientePayload = [
            'nombre' => $payload['nombre'],
            'telefono' => $payload['telefono'],
            'notas' => $payload['notas'],
        ];
        $suscripcionPayload = [
            'plataforma_id' => $payload['plataforma_id'],
            'modalidad_id' => $payload['modalidad_id'],
            'precio_venta' => (string) max(1, (int) round((float) ($modalidad['precio'] ?? 0))),
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
            flash('success', 'Cliente y suscripcion inicial creados correctamente.');
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_old($payload);
            flash('danger', 'No se pudo crear el cliente con su suscripcion inicial: ' . $exception->getMessage());
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
        $payload = [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'telefono' => trim((string) ($_POST['telefono'] ?? '')),
            'notas' => trim((string) ($_POST['notas'] ?? '')),
        ];

        if ($payload['nombre'] === '' || $payload['telefono'] === '') {
            flash('danger', 'Nombre y telefono son obligatorios.');
            $this->redirect('/clientes/editar/' . $id);
        }

        $this->clientes->update($id, $payload);
        flash('success', 'Cliente actualizado.');
        $this->redirect('/clientes');
    }

    public function destroy(int $id): void
    {
        try {
            $this->clientes->delete($id);
            flash('success', 'Cliente eliminado.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo eliminar el cliente: ' . $exception->getMessage());
        }

        $this->redirect('/clientes');
    }
}
