<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Modalidad;
use App\Models\Plataforma;

class TiposSuscripcionController extends Controller
{
    private Modalidad $tiposSuscripcion;
    private Plataforma $plataformas;

    public function __construct()
    {
        $this->tiposSuscripcion = new Modalidad();
        $this->plataformas = new Plataforma();
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $rows = $this->tiposSuscripcion->all($search);
        $platforms = $this->plataformas->all();

        $this->render('modalidades/index', [
            'pageTitle' => 'Tipos de suscripcion',
            'rows' => $rows,
            'platforms' => $platforms,
            'search' => $search,
            'tiposCuenta' => Modalidad::TIPOS_CUENTA,
        ]);
    }

    public function store(): void
    {
        $payload = $this->collectPayload();
        if ($payload === null) {
            $this->redirect('/tipos-suscripcion');
        }

        try {
            $this->tiposSuscripcion->create($payload);
            clear_old();
            flash('success', 'Tipo de suscripcion creado.');
        } catch (\Throwable $exception) {
            set_old($payload);
            flash('danger', 'No se pudo crear el tipo de suscripcion: ' . $exception->getMessage());
        }

        $this->redirect('/tipos-suscripcion');
    }

    public function edit(int $id): void
    {
        $item = $this->tiposSuscripcion->find($id);
        if ($item === null) {
            flash('danger', 'Tipo de suscripcion no encontrado.');
            $this->redirect('/tipos-suscripcion');
        }

        $platforms = $this->plataformas->all();

        $this->render('modalidades/edit', [
            'pageTitle' => 'Editar tipo de suscripcion',
            'item' => $item,
            'platforms' => $platforms,
            'tiposCuenta' => Modalidad::TIPOS_CUENTA,
        ]);
    }

    public function update(int $id): void
    {
        $payload = $this->collectPayload();
        if ($payload === null) {
            $this->redirect('/tipos-suscripcion/editar/' . $id);
        }

        try {
            $this->tiposSuscripcion->update($id, $payload);
            clear_old();
            flash('success', 'Tipo de suscripcion actualizado.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo actualizar el tipo de suscripcion: ' . $exception->getMessage());
        }

        $this->redirect('/tipos-suscripcion');
    }

    public function destroy(int $id): void
    {
        try {
            $this->tiposSuscripcion->delete($id);
            flash('success', 'Tipo de suscripcion eliminado.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo eliminar el tipo de suscripcion: ' . $exception->getMessage());
        }

        $this->redirect('/tipos-suscripcion');
    }

    private function collectPayload(): ?array
    {
        $payload = [
            'plataforma_id' => (int) ($_POST['plataforma_id'] ?? 0),
            'nombre_modalidad' => trim((string) ($_POST['nombre_modalidad'] ?? '')),
            'tipo_cuenta' => strtoupper(trim((string) ($_POST['tipo_cuenta'] ?? 'CUENTA_COMPLETA'))),
            'duracion_meses' => (int) ($_POST['duracion_meses'] ?? 1),
            'dispositivos' => trim((string) ($_POST['dispositivos'] ?? '')),
            'precio' => trim((string) ($_POST['precio'] ?? '')),
        ];

        $precio = (float) $payload['precio'];
        if (
            $payload['plataforma_id'] <= 0 ||
            $payload['nombre_modalidad'] === '' ||
            !in_array($payload['tipo_cuenta'], Modalidad::TIPOS_CUENTA, true) ||
            $payload['duracion_meses'] <= 0 ||
            $precio <= 0
        ) {
            set_old($payload);
            flash('danger', 'Completa todos los campos obligatorios. Precio y duracion deben ser mayores a 0.');

            return null;
        }

        $dispositivos = $payload['dispositivos'] === '' ? null : (int) $payload['dispositivos'];
        if ($payload['tipo_cuenta'] === 'POR_DISPOSITIVOS' && ($dispositivos === null || $dispositivos <= 0)) {
            set_old($payload);
            flash('danger', 'Para "POR_DISPOSITIVOS" debes indicar la cantidad de dispositivos.');

            return null;
        }

        if ($dispositivos !== null && $dispositivos <= 0) {
            set_old($payload);
            flash('danger', 'La cantidad de dispositivos debe ser mayor a 0.');

            return null;
        }

        $payload['dispositivos'] = $dispositivos;
        $payload['precio'] = number_format($precio, 2, '.', '');

        return $payload;
    }
}
