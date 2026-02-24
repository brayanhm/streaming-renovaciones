<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;
use App\Models\Modalidad;
use App\Models\Plataforma;
use App\Models\Suscripcion;

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
        $search = trim((string) ($_GET['q'] ?? ''));
        $estado = strtoupper(trim((string) ($_GET['estado'] ?? '')));
        if (!in_array($estado, Suscripcion::ESTADOS, true)) {
            $estado = '';
        }

        $rows = $this->suscripciones->all($search, $estado);
        $clientes = $this->clientes->all();
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
            'precio_venta' => trim((string) ($_POST['precio_venta'] ?? '')),
            'fecha_inicio' => trim((string) ($_POST['fecha_inicio'] ?? '')),
            'fecha_vencimiento' => trim((string) ($_POST['fecha_vencimiento'] ?? '')),
            'estado' => strtoupper(trim((string) ($_POST['estado'] ?? 'ACTIVO'))),
            'usuario_proveedor' => trim((string) ($_POST['usuario_proveedor'] ?? '')),
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

        if ($payload['precio_venta'] !== '' && !preg_match('/^\d+$/', $payload['precio_venta'])) {
            set_old($payload);
            flash('danger', 'El precio de venta debe ser un numero entero mayor a 0.');

            return null;
        }

        $priceValue = $payload['precio_venta'] === ''
            ? (int) round((float) $modalidad['precio'])
            : (int) $payload['precio_venta'];
        if ($priceValue <= 0) {
            set_old($payload);
            flash('danger', 'El precio de venta debe ser mayor a 0.');

            return null;
        }

        $payload['precio_venta'] = (string) $priceValue;

        return $payload;
    }
}
