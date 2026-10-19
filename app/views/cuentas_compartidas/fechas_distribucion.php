<?php declare(strict_types=1); ?>
<div class="small text-secondary mt-2">
    <div><?= !empty($compactDistributionDates) ? 'Inicio:' : 'Inicio de distribución:' ?> <span data-distribution-started><?= e(display_datetime($fechasDistribucion['inicio_distribucion'] ?? null)) ?></span></div>
    <div><?= !empty($compactDistributionDates) ? 'Llenado:' : 'Primer llenado:' ?> <span data-distribution-completed><?= e(display_datetime($fechasDistribucion['cupos_completados_at'] ?? null)) ?></span></div>
</div>
