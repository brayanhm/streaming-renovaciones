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
        $selectedPlatformId = $this->resolvePlatformFilterId();
        $rows = $this->tiposSuscripcion->all($search, $selectedPlatformId);
        $platforms = $this->plataformas->all();
        $selectedPlatform = null;
        if ($selectedPlatformId > 0) {
            foreach ($platforms as $platform) {
                if ((int) ($platform['id'] ?? 0) === $selectedPlatformId) {
                    $selectedPlatform = $platform;
                    break;
                }
            }
        }

        $this->render('modalidades/index', [
            'pageTitle' => 'Tipos de suscripcion',
            'rows' => $rows,
            'platforms' => $platforms,
            'search' => $search,
            'selectedPlatformId' => $selectedPlatformId,
            'selectedPlatform' => $selectedPlatform,
            'tiposCuenta' => Modalidad::TIPOS_CUENTA,
        ]);
    }

    public function store(): void
    {
        $returnPlatformId = $this->resolveReturnPlatformId();
        $payload = $this->collectPayload();
        if ($payload === null) {
            $this->redirect($this->buildIndexPath($returnPlatformId));
        }

        try {
            $this->tiposSuscripcion->create($payload);
            clear_old();
            flash('success', 'Tipo de suscripcion creado.');
        } catch (\Throwable $exception) {
            set_old($payload);
            flash('danger', 'No se pudo crear el tipo de suscripcion: ' . $exception->getMessage());
        }

        $this->redirect($this->buildIndexPath($returnPlatformId));
    }

    public function edit(int $id): void
    {
        $item = $this->tiposSuscripcion->find($id);
        if ($item === null) {
            flash('danger', 'Tipo de suscripcion no encontrado.');
            $this->redirect('/tipos-suscripcion');
        }

        $platforms = $this->plataformas->all();
        $returnPlatformId = $this->resolvePlatformFilterId();

        $this->render('modalidades/edit', [
            'pageTitle' => 'Editar tipo de suscripcion',
            'item' => $item,
            'platforms' => $platforms,
            'returnPlatformId' => $returnPlatformId,
            'tiposCuenta' => Modalidad::TIPOS_CUENTA,
        ]);
    }

    public function update(int $id): void
    {
        $returnPlatformId = $this->resolveReturnPlatformId();
        $payload = $this->collectPayload();
        if ($payload === null) {
            $this->redirect($this->buildEditPath($id, $returnPlatformId));
        }

        try {
            $this->tiposSuscripcion->update($id, $payload);
            clear_old();
            flash('success', 'Tipo de suscripcion actualizado.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo actualizar el tipo de suscripcion: ' . $exception->getMessage());
        }

        $this->redirect($this->buildIndexPath($returnPlatformId));
    }

    public function destroy(int $id): void
    {
        $returnPlatformId = $this->resolveReturnPlatformId();

        try {
            $this->tiposSuscripcion->delete($id);
            flash('success', 'Tipo de suscripcion eliminado.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo eliminar el tipo de suscripcion: ' . $exception->getMessage());
        }

        $this->redirect($this->buildIndexPath($returnPlatformId));
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

        if (!preg_match('/^\d+$/', $payload['precio'])) {
            set_old($payload);
            flash('danger', 'El precio debe ser un numero entero mayor a 0.');

            return null;
        }
        $precio = (int) $payload['precio'];
        if (
            $payload['plataforma_id'] <= 0 ||
            $payload['nombre_modalidad'] === '' ||
            !in_array($payload['tipo_cuenta'], Modalidad::TIPOS_CUENTA, true) ||
            $payload['duracion_meses'] <= 0 ||
            $precio <= 0
        ) {
            set_old($payload);
            flash('danger', 'Completa todos los campos obligatorios. Precio y duracion deben ser valores enteros mayores a 0.');

            return null;
        }

        $plataforma = $this->plataformas->find($payload['plataforma_id']);
        if ($plataforma === null) {
            set_old($payload);
            flash('danger', 'La plataforma seleccionada no existe.');

            return null;
        }

        $dispositivos = $payload['dispositivos'] === '' ? null : (int) $payload['dispositivos'];
        if ($payload['tipo_cuenta'] === 'POR_DISPOSITIVOS' && ($dispositivos === null || $dispositivos <= 0)) {
            set_old($payload);
            flash('danger', 'Si eliges "POR_DISPOSITIVOS", debes indicar la cantidad de dispositivos.');

            return null;
        }

        if ($dispositivos !== null && $dispositivos <= 0) {
            set_old($payload);
            flash('danger', 'La cantidad de dispositivos debe ser mayor a 0.');

            return null;
        }

        $allowedDurations = Plataforma::parseDuracionesDisponibles((string) ($plataforma['duraciones_disponibles'] ?? ''));
        if ($allowedDurations !== [] && !in_array($payload['duracion_meses'], $allowedDurations, true)) {
            set_old($payload);
            flash(
                'danger',
                'La duracion no esta permitida para esta plataforma. Valores permitidos: ' . implode(', ', $allowedDurations) . '.'
            );

            return null;
        }

        $payload['dispositivos'] = $dispositivos;
        $payload['precio'] = (string) $precio;

        return $payload;
    }

    private function resolvePlatformFilterId(): int
    {
        $platformId = (int) ($_GET['plataforma_id'] ?? 0);
        if ($platformId <= 0) {
            return 0;
        }

        return $this->plataformas->find($platformId) !== null ? $platformId : 0;
    }

    private function resolveReturnPlatformId(): int
    {
        $platformId = (int) ($_POST['return_plataforma_id'] ?? 0);
        if ($platformId <= 0) {
            return 0;
        }

        return $this->plataformas->find($platformId) !== null ? $platformId : 0;
    }

    private function buildIndexPath(int $platformId): string
    {
        if ($platformId <= 0) {
            return '/tipos-suscripcion';
        }

        return '/tipos-suscripcion?' . http_build_query(['plataforma_id' => $platformId]);
    }

    private function buildEditPath(int $id, int $platformId): string
    {
        if ($platformId <= 0) {
            return '/tipos-suscripcion/editar/' . $id;
        }

        return '/tipos-suscripcion/editar/' . $id . '?' . http_build_query(['plataforma_id' => $platformId]);
    }
}
