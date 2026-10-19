<?php declare(strict_types=1); ?>
<div class="gs-occupancy">
    <strong class="<?= $exceeded > 0 ? 'text-danger' : ($remaining === 0 ? 'text-warning' : '') ?>"><?= $occupied ?> / <?= $recommended ?></strong>
    <small class="text-secondary"> ocupados</small>
    <div class="progress gs-occupancy-bar" role="progressbar" aria-label="Ocupación respecto a la referencia" aria-valuenow="<?= min($occupied, $recommended) ?>" aria-valuemin="0" aria-valuemax="<?= $recommended ?>" aria-valuetext="<?= $occupied ?> ocupados; referencia <?= $recommended ?>; <?= $exceeded ?> adicionales">
        <div class="progress-bar <?= $exceeded > 0 ? 'bg-danger' : ($remaining === 0 ? 'bg-warning' : '') ?>" style="width:<?= min(100, (int) round(100 * $occupied / max(1, $recommended))) ?>%"></div>
    </div>
    <small class="text-secondary"><?php if ($exceeded > 0): ?>+<?= $exceeded ?> sobre la referencia<?php elseif ($remaining === 0): ?>Referencia alcanzada<?php else: ?><?= $remaining ?> hasta la referencia<?php endif; ?></small>
</div>
<?php $fechasDistribucion = $row; $compactDistributionDates = true; require __DIR__ . '/fechas_distribucion.php'; unset($compactDistributionDates); ?>
