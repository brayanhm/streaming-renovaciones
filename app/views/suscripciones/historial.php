<?php
declare(strict_types=1);

use App\Models\Modalidad;

$clienteNombre = (string) ($suscripcion['cliente_nombre'] ?? '');
$plataformaNombre = (string) ($suscripcion['plataforma_nombre'] ?? '');
$modalidadNombre = (string) ($suscripcion['nombre_modalidad'] ?? '');
$tipoCuenta = (string) ($suscripcion['tipo_cuenta'] ?? 'CUENTA_COMPLETA');
$dispositivos = isset($suscripcion['dispositivos']) ? (int) $suscripcion['dispositivos'] : null;
$duracion = max(1, (int) ($suscripcion['duracion_meses'] ?? 1));
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Historial de renovaciones</h1>
        <p class="text-secondary mb-0">
            <?= e($clienteNombre) ?> · <?= e($plataformaNombre) ?> · <?= e($modalidadNombre) ?>
            (<?= e(Modalidad::tipoCuentaLabel($tipoCuenta, $dispositivos)) ?>, <?= e((string) $duracion) ?> mes(es))
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('/suscripciones/editar/' . (int) $suscripcion['id'])) ?>" class="btn btn-outline-primary">Editar suscripción</a>
        <a href="<?= e(url('/suscripciones')) ?>" class="btn btn-outline-secondary">Volver</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Vencimiento actual</h2>
                <div class="h5 mb-0"><?= e((string) ($suscripcion['fecha_vencimiento'] ?? '')) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Precio venta</h2>
                <div class="h5 mb-0"><?= e(money((float) ($suscripcion['precio_final'] ?? $suscripcion['modalidad_precio'] ?? 0))) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Renovaciones</h2>
                <div class="h5 mb-0"><?= e((string) count($movimientos)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Ganancia acumulada</h2>
                <?php $ganAcum = array_sum(array_column($movimientos, 'utilidad')); ?>
                <div class="h5 mb-0 <?= $ganAcum < 0 ? 'text-danger' : 'text-success' ?>"><?= e(money((float) $ganAcum)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th class="text-end">Meses</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Costo</th>
                        <th class="text-end">Ganancia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientos)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-secondary">Sin renovaciones registradas.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($movimientos as $i => $mov): ?>
                        <?php $gan = (float) ($mov['utilidad'] ?? 0); ?>
                        <tr>
                            <td class="text-secondary"><?= e((string) ($i + 1)) ?></td>
                            <td><?= e((string) ($mov['fecha'] ?? '')) ?></td>
                            <td><span class="badge text-bg-primary"><?= e((string) ($mov['tipo'] ?? '')) ?></span></td>
                            <td class="text-end"><?= e((string) (int) ($mov['meses'] ?? 0)) ?></td>
                            <td class="text-end"><?= e(money((float) ($mov['monto'] ?? 0))) ?></td>
                            <td class="text-end"><?= e(money((float) ($mov['costo'] ?? 0))) ?></td>
                            <td class="text-end fw-semibold <?= $gan < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= e(money($gan)) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
