<?php
declare(strict_types=1);

$create = $plan['create'] ?? [];
$skipExisting = $plan['skipExisting'] ?? [];
$dupFile = (int) ($plan['dupFile'] ?? 0);
$skipped = $skipped ?? [];
$nada = count($create) === 0;

if (!function_exists('modalidad_label_disp')) {
    function modalidad_label_disp(array $mod): string
    {
        $disp = (int) ($mod['dispositivos'] ?? 1);
        $dur = (int) ($mod['duracion_meses'] ?? 1);
        return $disp . ' disp · ' . $dur . ' mes' . ($dur === 1 ? '' : 'es');
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Vista previa de importación</h1>
        <small class="text-secondary">
            Destino: <strong><?= e((string) ($target['plataformaNombre'] ?? 'FlujoTV Por dispositivos')) ?></strong>.
            Nada se ha creado todavía.
        </small>
    </div>
    <a href="<?= e(url('/importar/flujotv-dispositivos')) ?>" class="btn btn-outline-secondary">Cancelar</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="h6 text-secondary mb-1">A crear</div>
            <div class="h3 mb-0 text-success"><?= e((string) count($create)) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="h6 text-secondary mb-1">Duplicados omitidos</div>
            <div class="h3 mb-0 text-secondary"><?= e((string) (count($skipExisting) + $dupFile)) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="h6 text-secondary mb-1">Filas sin fecha</div>
            <div class="h3 mb-0 text-secondary"><?= e((string) count($skipped)) ?></div>
        </div></div>
    </div>
</div>

<?php if ($nada): ?>
    <div class="alert alert-info">No hay cuentas nuevas para crear: todas las filas del CSV ya existen o no tienen fecha válida.</div>
<?php endif; ?>

<?php if (!empty($create)): ?>
<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Cuentas a crear (<?= e((string) count($create)) ?>)</h2>
        <div class="table-responsive" style="max-height:460px;overflow-y:auto;">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr><th>Usuario</th><th>Plan</th><th>Inicio</th><th>Vence</th><th>Crédito</th><th class="text-end">Precio (Bs)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($create as $c): ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) $c['user']) ?></td>
                            <td><span class="badge text-bg-secondary"><?= e(modalidad_label_disp($c['mod'])) ?></span></td>
                            <td><?= e((string) $c['inicio']) ?></td>
                            <td><?= e((string) $c['fin']) ?></td>
                            <td><?= e((string) $c['credito']) ?></td>
                            <td class="text-end"><?= e(money((float) ($c['mod']['precio'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <small class="text-secondary d-block mt-2">Todas de 1 dispositivo, con precio/costo del plan y teléfono vacío para completar luego.</small>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($skipExisting) || $dupFile > 0 || !empty($skipped)): ?>
    <div class="alert alert-warning mt-3">
        <?php if (!empty($skipExisting)): ?>
            <div><?= e((string) count($skipExisting)) ?> cuenta(s) ya existen con el mismo usuario y fechas: se omiten.</div>
        <?php endif; ?>
        <?php if ($dupFile > 0): ?>
            <div><?= e((string) $dupFile) ?> fila(s) repetida(s) dentro del archivo: se omiten.</div>
        <?php endif; ?>
        <?php if (!empty($skipped)): ?>
            <div><?= e((string) count($skipped)) ?> fila(s) sin fecha de finalización válida: se omiten
                (<?= e(implode(', ', array_slice(array_column($skipped, 'user'), 0, 12))) ?><?= count($skipped) > 12 ? '…' : '' ?>).
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mt-4">
    <?php if (!$nada): ?>
        <form method="post" action="<?= e(url('/importar/flujotv-dispositivos/aplicar')) ?>"
              onsubmit="return confirm('¿Crear <?= (int) count($create) ?> cuenta(s) por dispositivos?')">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e((string) $token) ?>">
            <button type="submit" class="btn btn-success btn-lg">Confirmar y crear</button>
        </form>
    <?php endif; ?>
    <a href="<?= e(url('/importar/flujotv-dispositivos')) ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
</div>
