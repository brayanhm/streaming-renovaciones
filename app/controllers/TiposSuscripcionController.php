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
            $plataforma = $this->plataformas->find((int) $payload['plataforma_id']);
            $duraciones = Plataforma::parseDuracionesDisponibles((string) ($plataforma['duraciones_disponibles'] ?? ''));
            if ($duraciones === []) {
                $duraciones = [(int) $payload['duracion_meses']];
            }
            $created = $this->tiposSuscripcion->ensureTemplateDurations($payload, $duraciones);
            clear_old();
            if ($created > 1) {
                flash('success', 'Tipo de suscripcion creado con ' . $created . ' duraciones automaticamente.');
            } else {
                flash('success', 'Tipo de suscripcion creado.');
            }
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

    public function precios(): void
    {
        $platforms = $this->plataformas->all();
        $selectedPlatformId = $this->resolvePlatformFilterId();

        if ($selectedPlatformId <= 0 && !empty($platforms)) {
            $selectedPlatformId = (int) ($platforms[0]['id'] ?? 0);
        }

        $selectedPlatform = null;
        foreach ($platforms as $platform) {
            if ((int) ($platform['id'] ?? 0) === $selectedPlatformId) {
                $selectedPlatform = $platform;
                break;
            }
        }

        if ($selectedPlatformId > 0 && $selectedPlatform !== null) {
            $duraciones = Plataforma::parseDuracionesDisponibles((string) ($selectedPlatform['duraciones_disponibles'] ?? ''));
            if ($duraciones !== []) {
                $generated = $this->tiposSuscripcion->ensurePlatformDurations($selectedPlatformId, $duraciones);
                if ($generated > 0) {
                    flash('info', 'Se generaron ' . $generated . ' duraciones faltantes automaticamente.');
                }
            }
        }

        $rows = $selectedPlatformId > 0
            ? $this->tiposSuscripcion->allByPlataforma($selectedPlatformId)
            : [];

        $this->render('modalidades/precios', [
            'pageTitle' => 'Precios por plataforma',
            'platforms' => $platforms,
            'selectedPlatformId' => $selectedPlatformId,
            'selectedPlatform' => $selectedPlatform,
            'rows' => $rows,
        ]);
    }

    public function updatePrecios(): void
    {
        $platformId = (int) ($_POST['plataforma_id'] ?? 0);
        if ($platformId <= 0 || $this->plataformas->find($platformId) === null) {
            flash('danger', 'Selecciona una plataforma valida.');
            $this->redirect('/tipos-suscripcion/precios');
        }

        $rows = $this->tiposSuscripcion->allByPlataforma($platformId);
        if ($rows === []) {
            flash('warning', 'La plataforma no tiene tipos de suscripcion para actualizar.');
            $this->redirect('/tipos-suscripcion/precios?' . http_build_query(['plataforma_id' => $platformId]));
        }

        $inputCostos = $_POST['costo'] ?? [];
        $inputPrecios = $_POST['precio'] ?? [];
        if (!is_array($inputCostos) || !is_array($inputPrecios)) {
            flash('danger', 'Datos invalidos para actualizar precios.');
            $this->redirect('/tipos-suscripcion/precios?' . http_build_query(['plataforma_id' => $platformId]));
        }

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $costo = trim((string) ($inputCostos[$id] ?? ''));
            $precio = trim((string) ($inputPrecios[$id] ?? ''));
            if (!preg_match('/^\d+$/', $costo) || (int) $costo <= 0) {
                flash('danger', 'Costo invalido en el plan ID ' . $id . '. Usa enteros mayores a 0.');
                $this->redirect('/tipos-suscripcion/precios?' . http_build_query(['plataforma_id' => $platformId]));
            }
            if (!preg_match('/^\d+$/', $precio) || (int) $precio <= 0) {
                flash('danger', 'Precio invalido en el plan ID ' . $id . '. Usa enteros mayores a 0.');
                $this->redirect('/tipos-suscripcion/precios?' . http_build_query(['plataforma_id' => $platformId]));
            }
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'UPDATE modalidades
                 SET costo = :costo, precio = :precio
                 WHERE id = :id AND plataforma_id = :plataforma_id'
            );

            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                $stmt->execute([
                    'id' => $id,
                    'plataforma_id' => $platformId,
                    'costo' => (int) ($inputCostos[$id] ?? 0),
                    'precio' => (int) ($inputPrecios[$id] ?? 0),
                ]);
            }
            $pdo->commit();
            flash('success', 'Costos y precios actualizados para la plataforma seleccionada.');
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'No se pudieron actualizar los precios: ' . $exception->getMessage());
        }

        $this->redirect('/tipos-suscripcion/precios?' . http_build_query(['plataforma_id' => $platformId]));
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
            'costo' => trim((string) ($_POST['costo'] ?? '')),
            'precio' => trim((string) ($_POST['precio'] ?? '')),
        ];

        if (!preg_match('/^\d+$/', $payload['costo'])) {
            set_old($payload);
            flash('danger', 'El costo debe ser un numero entero mayor a 0.');

            return null;
        }

        if (!preg_match('/^\d+$/', $payload['precio'])) {
            set_old($payload);
            flash('danger', 'El precio de venta debe ser un numero entero mayor a 0.');

            return null;
        }
        $costo = (int) $payload['costo'];
        $precio = (int) $payload['precio'];
        if (
            $payload['plataforma_id'] <= 0 ||
            $payload['nombre_modalidad'] === '' ||
            !in_array($payload['tipo_cuenta'], Modalidad::TIPOS_CUENTA, true) ||
            $payload['duracion_meses'] <= 0 ||
            $costo <= 0 ||
            $precio <= 0
        ) {
            set_old($payload);
            flash(
                'danger',
                'Completa todos los campos obligatorios. Costo, precio de venta y duracion deben ser enteros mayores a 0.'
            );

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
        $payload['costo'] = (string) $costo;
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
