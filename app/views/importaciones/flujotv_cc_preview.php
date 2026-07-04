<?php
declare(strict_types=1);

$update = $plan['update'] ?? [];
$create = $plan['create'] ?? [];
$ignore = $plan['ignore'] ?? [];
$notInCsv = $plan['notInCsv'] ?? [];
$skipped = $skipped ?? [];
$nada = count($update) === 0 && count($create) === 0;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Vista previa de importación</h1>
        <small class="text-secondary">
            Destino: <strong><?= e((string) ($target['plataformaNombre'] ?? 'FlujoTV Cuenta Completa')) ?></strong>.
            Nada se ha escrito todavía.
        </small>
    </div>
    <a href="<?= e(url('/importar/flujotv')) ?>" class="btn btn-outline-secondary">Cancelar</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="h6 text-secondary mb-1">A actualizar</div>
            <div class="h3 mb-0 text-warning"><?= e((string) count($update)) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="h6 text-secondary mb-1">A crear</div>
            <div class="h3 mb-0 text-success"><?= e((string) count($create)) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="h6 text-secondary mb-1">Sin cambio</div>
            <div class="h3 mb-0"><?= e((string) count($ignore)) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="h6 text-secondary mb-1">En sistema, no en CSV</div>
            <div class="h3 mb-0 text-secondary"><?= e((string) count($notInCsv)) ?></div>
        </div></div>
    </div>
</div>

<?php if (!empty($skipped) || (int) ($duplicates ?? 0) > 0): ?>
    <div class="alert alert-warning">
        <?php if ((int) ($duplicates ?? 0) > 0): ?>
            <div><?= e((string) $duplicates) ?> fila(s) duplicada(s) en el CSV: se conserva la fecha más lejana.</div>
        <?php endif; ?>
        <?php if (!empty($skipped)): ?>
            <div><?= e((string) count($skipped)) ?> fila(s) omitida(s) por no tener fecha válida:
                <?= e(implode(', ', array_slice(array_column($skipped, 'user'), 0, 15))) ?><?= count($skipped) > 15 ? '…' : '' ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($nada): ?>
    <div class="alert alert-info">No hay cambios para aplicar: todas las cuentas del CSV ya están al día.</div>
<?php endif; ?>

<div class="row g-3">
    <?php if (!empty($update)): ?>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Actualizar vencimiento (<?= e((string) count($update)) ?>)</h2>
                <div class="table-responsive" style="max-height:360px;overflow-y:auto;">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-dark"><tr><th>Usuario</th><th>Actual</th><th>Nuevo</th><th class="text-end">Δ días</th></tr></thead>
                        <tbody>
                            <?php foreach ($update as $u): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $u['user']) ?></td>
                                    <td><?= e((string) $u['old']) ?></td>
                                    <td><?= e((string) $u['fin']) ?></td>
                                    <td class="text-end <?= (int) $u['delta'] < 0 ? 'text-danger fw-semibold' : 'text-success' ?>">
                                        <?= ((int) $u['delta'] > 0 ? '+' : '') . e((string) $u['delta']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php $retro = array_filter($update, static fn ($u) => (int) $u['delta'] < 0); ?>
                <?php if (!empty($retro)): ?>
                    <small class="text-danger d-block mt-2">
                        <?= e((string) count($retro)) ?> cuenta(s) retroceden su vencimiento (el CSV tiene una fecha anterior a la del sistema): se aplica la del CSV.
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($create)): ?>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Crear nuevas (<?= e((string) count($create)) ?>)</h2>
                <div class="table-responsive" style="max-height:360px;overflow-y:auto;">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-dark"><tr><th>Usuario</th><th>Vencimiento</th></tr></thead>
                        <tbody>
                            <?php foreach ($create as $c): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $c['user']) ?></td>
                                    <td><?= e((string) $c['fin']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-secondary d-block mt-2">Se crean con plan de 1 mes (precio del plan), inicio hoy y teléfono vacío.</small>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($notInCsv)): ?>
    <details class="mt-3">
        <summary class="text-secondary" role="button"><?= e((string) count($notInCsv)) ?> cuenta(s) en el sistema que no aparecen en el CSV (no se modifican)</summary>
        <div class="small text-secondary mt-2">
            <?= e(implode(', ', array_slice(array_map(static fn ($r) => (string) $r['usuario'], $notInCsv), 0, 60))) ?><?= count($notInCsv) > 60 ? '…' : '' ?>
        </div>
    </details>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mt-4">
    <?php if (!$nada): ?>
        <form method="post" action="<?= e(url('/importar/flujotv/aplicar')) ?>"
              onsubmit="return confirm('¿Aplicar <?= (int) count($update) ?> actualización(es) y <?= (int) count($create) ?> creación(es)?')">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e((string) $token) ?>">
            <button type="submit" class="btn btn-success btn-lg">Confirmar y aplicar</button>
        </form>
    <?php endif; ?>
    <a href="<?= e(url('/importar/flujotv')) ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
</div>
