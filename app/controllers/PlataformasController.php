<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Modalidad;
use App\Models\Plataforma;

class PlataformasController extends Controller
{
    private Plataforma $plataformas;
    private Modalidad $modalidades;

    public function __construct()
    {
        $this->plataformas = new Plataforma();
        $this->modalidades = new Modalidad();
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $rows = $this->plataformas->all($search);

        $this->render('plataformas/index', [
            'pageTitle' => 'Plataformas',
            'rows' => $rows,
            'search' => $search,
        ]);
    }

    public function store(): void
    {
        $payload = $this->collectPayload();
        if ($payload === null) {
            $this->redirect('/plataformas');
        }

        try {
            $platformId = $this->plataformas->create($payload);
            $generated = $this->syncAutomaticPlans($platformId, (string) ($payload['duraciones_disponibles'] ?? ''));
            clear_old();
            if ($generated > 0) {
                flash('success', 'Plataforma creada con ' . $generated . ' planes generados automaticamente.');
            } else {
                flash('success', 'Plataforma creada.');
            }
        } catch (\Throwable $exception) {
            set_old($payload);
            flash('danger', 'No se pudo crear la plataforma: ' . $exception->getMessage());
        }

        $this->redirect('/plataformas');
    }

    public function edit(int $id): void
    {
        $item = $this->plataformas->find($id);
        if ($item === null) {
            flash('danger', 'Plataforma no encontrada.');
            $this->redirect('/plataformas');
        }

        $this->render('plataformas/edit', [
            'pageTitle' => 'Editar plataforma',
            'item' => $item,
        ]);
    }

    public function update(int $id): void
    {
        $payload = $this->collectPayload();
        if ($payload === null) {
            $this->redirect('/plataformas/editar/' . $id);
        }

        try {
            $this->plataformas->update($id, $payload);
            $generated = $this->syncAutomaticPlans($id, (string) ($payload['duraciones_disponibles'] ?? ''));
            if ($generated > 0) {
                flash('success', 'Plataforma actualizada. Se generaron ' . $generated . ' planes faltantes.');
            } else {
                flash('success', 'Plataforma actualizada.');
            }
            clear_old();
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo actualizar la plataforma: ' . $exception->getMessage());
        }

        $this->redirect('/plataformas');
    }

    public function destroy(int $id): void
    {
        try {
            $this->plataformas->delete($id);
            flash('success', 'Plataforma eliminada.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo eliminar la plataforma: ' . $exception->getMessage());
        }

        $this->redirect('/plataformas');
    }

    private function collectPayload(): ?array
    {
        $payload = [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'tipo_servicio' => strtoupper(trim((string) ($_POST['tipo_servicio'] ?? ''))),
            'duraciones_disponibles' => trim((string) ($_POST['duraciones_disponibles'] ?? '')),
            'dato_renovacion' => strtoupper(trim((string) ($_POST['dato_renovacion'] ?? 'NO_APLICA'))),
            'mensaje_menos_2' => trim((string) ($_POST['mensaje_menos_2'] ?? '')),
            'mensaje_menos_1' => trim((string) ($_POST['mensaje_menos_1'] ?? '')),
            'mensaje_rec_7' => trim((string) ($_POST['mensaje_rec_7'] ?? '')),
            'mensaje_rec_15' => trim((string) ($_POST['mensaje_rec_15'] ?? '')),
        ];

        if ($payload['nombre'] === '' || !in_array($payload['tipo_servicio'], ['RENOVABLE', 'DESECHABLE'], true)) {
            set_old($payload);
            flash('danger', 'Completa el nombre y el tipo de servicio de la plataforma.');

            return null;
        }

        $normalizedDurations = Plataforma::normalizeDuracionesDisponibles($payload['duraciones_disponibles']);
        if ($payload['duraciones_disponibles'] !== '' && $normalizedDurations === null) {
            set_old($payload);
            flash('danger', 'Duraciones no validas. Usa meses positivos separados por comas, por ejemplo: 1,3,7.');

            return null;
        }
        $payload['duraciones_disponibles'] = $normalizedDurations;

        if (
            $payload['tipo_servicio'] === 'RENOVABLE' &&
            !in_array($payload['dato_renovacion'], ['USUARIO', 'CORREO'], true)
        ) {
            set_old($payload);
            flash('danger', 'Para plataformas renovables debes definir si la renovacion se realiza por usuario o por correo.');

            return null;
        }

        $payload['dato_renovacion'] = Plataforma::normalizeDatoRenovacion(
            $payload['dato_renovacion'],
            $payload['tipo_servicio']
        );

        return $payload;
    }

    private function syncAutomaticPlans(int $platformId, string $duracionesCsv): int
    {
        $duraciones = Plataforma::parseDuracionesDisponibles($duracionesCsv);
        if ($duraciones === []) {
            return 0;
        }

        $existing = $this->modalidades->allByPlataforma($platformId);
        if ($existing === []) {
            $template = [
                'plataforma_id' => $platformId,
                'nombre_modalidad' => 'Cuenta completa',
                'tipo_cuenta' => 'CUENTA_COMPLETA',
                'duracion_meses' => (int) $duraciones[0],
                'dispositivos' => null,
                'precio' => '1',
                'costo' => '1',
            ];

            return $this->modalidades->ensureTemplateDurations($template, $duraciones);
        }

        return $this->modalidades->ensurePlatformDurations($platformId, $duraciones);
    }
}
