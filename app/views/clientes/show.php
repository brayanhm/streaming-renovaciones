<?php
declare(strict_types=1);

use App\Models\Modalidad;
use App\Models\Plataforma;

if (!function_exists('status_badge_show')) {
    function status_badge_show(string $status): string
    {
        return match ($status) {
            'CONTACTAR_2D' => 'text-bg-warning',
            'REENVIAR_1D' => 'text-bg-info',
            'ESPERA' => 'text-bg-primary',
            'ACTIVO' => 'text-bg-success',
            'VENCIDO' => 'text-bg-danger',
            'RECUP' => 'text-bg-dark',
            default => 'text-bg-secondary',
        };
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1"><?= e((string) ($item['nombre'] ?? '')) ?></h1>
        <p class="text-secondary mb-0"><?= e((string) ($item['telefono'] ?? '')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('/clientes/editar/' . (int) $item['id'])) ?>" class="btn btn-outline-primary">Editar cliente</a>
        <a href="<?= e(url('/clientes')) ?>" class="btn btn-outline-secondary">Volver a clientes</a>
    </div>
</div>

<?php if (!empty($item['notas'])): ?>
<div class="alert alert-light border mb-3">
    <strong>Notas:</strong> <?= e((string) $item['notas']) ?>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body p-0">
        <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Suscripciones</h2>
            <a href="<?= e(url('/suscripciones?cliente_id=' . (int) $item['id'] . '#nueva-vigencia')) ?>" class="btn btn-sm btn-outline-primary">+ Nueva vigencia</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Plataforma</th>
                        <th>Plan</th>
                        <th>Usuario</th>
                        <th>Notas</th>
                        <th>Inicio</th>
                        <th>Vencimiento</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suscripciones)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-secondary">Sin suscripciones.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($suscripciones as $sus): ?>
                        <?php
                        $dias = (int) ($sus['dias_para_vencer'] ?? 0);
                        $isNoRenew = (int) ($sus['flag_no_renovo'] ?? 0) === 1;
                        $statusLabel = $isNoRenew ? 'VENCIDO' : (string) ($sus['estado'] ?? '');
                        $tipoCuenta = (string) ($sus['tipo_cuenta'] ?? 'CUENTA_COMPLETA');
                        $dispositivos = isset($sus['dispositivos']) ? (int) $sus['dispositivos'] : null;
                        $duracion = max(1, (int) ($sus['duracion_meses'] ?? 1));
                        if ($dias < 0) {
                            $diasLabel = 'Vencido hace ' . abs($dias) . 'd';
                            $diasClass = 'text-danger';
                        } elseif ($dias === 0) {
                            $diasLabel = 'Hoy';
                            $diasClass = 'text-danger';
                        } else {
                            $diasLabel = 'En ' . $dias . 'd';
                            $diasClass = $dias <= 3 ? 'text-warning' : 'text-secondary';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($sus['plataforma_nombre'] ?? '')) ?></div>
                                <small class="text-secondary"><?= e((string) ($sus['plataforma_tipo_servicio'] ?? '')) ?></small>
                            </td>
                            <td>
                                <div><?= e((string) ($sus['nombre_modalidad'] ?? '')) ?></div>
                                <small class="text-secondary"><?= e(Modalidad::tipoCuentaLabel($tipoCuenta, $dispositivos)) ?> · <?= e((string) $duracion) ?> mes(es)</small>
                            </td>
                            <td>
                                <?php if (!empty($sus['usuario_proveedor'])): ?>
                                    <?php $renovLabel = Plataforma::datoRenovacionLabel((string) ($sus['plataforma_dato_renovacion'] ?? 'USUARIO')); ?>
                                    <small><?= e($renovLabel) ?>: <?= e((string) $sus['usuario_proveedor']) ?></small>
                                <?php else: ?>
                                    <small class="text-secondary">—</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($sus['notas'])): ?>
                                    <small><?= e(mb_strimwidth((string) $sus['notas'], 0, 50, '…')) ?></small>
                                <?php else: ?>
                                    <small class="text-secondary">—</small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= e((string) ($sus['fecha_inicio'] ?? '')) ?></small></td>
                            <td>
                                <div><?= e((string) ($sus['fecha_vencimiento'] ?? '')) ?></div>
                                <small class="<?= e($diasClass) ?>"><?= e($diasLabel) ?></small>
                            </td>
                            <td><?= e(money((float) ($sus['precio_final'] ?? $sus['modalidad_precio'] ?? 0))) ?></td>
                            <td>
                                <span class="badge <?= e(status_badge_show($statusLabel)) ?>"><?= e($statusLabel) ?></span>
                                <?php if ($isNoRenew): ?>
                                    <div><small class="text-danger">No renovado</small></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 justify-content-end">
                                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/suscripciones/historial/' . (int) $sus['id'])) ?>">Historial</a>
                                    <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/suscripciones/editar/' . (int) $sus['id'])) ?>">Editar</a>
                                </div>
                                <details class="mt-2">
                                    <summary class="small text-primary" role="button">Modificar finalizacion</summary>
                                    <form method="post" action="<?= e(url('/suscripciones/finalizacion/' . (int) $sus['id'])) ?>" class="mt-2">
                                        <?= csrf_field() ?>
                                        <label class="form-label form-label-sm mb-1" for="fecha_vencimiento_<?= (int) $sus['id'] ?>">Nueva fecha</label>
                                        <input
                                            type="date"
                                            class="form-control form-control-sm mb-2"
                                            id="fecha_vencimiento_<?= (int) $sus['id'] ?>"
                                            name="fecha_vencimiento"
                                            value="<?= e((string) ($sus['fecha_vencimiento'] ?? '')) ?>"
                                            min="<?= e((string) ($sus['fecha_inicio'] ?? '')) ?>"
                                            required
                                        >
                                        <button class="btn btn-success btn-sm w-100" type="submit">Guardar finalizacion</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
