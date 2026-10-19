<?php declare(strict_types=1); ?>
<div class="card border-primary mb-3 gs-account-summary"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="gs-selected-account">
        <h2 class="h6 text-secondary mb-2">Cuenta seleccionada para distribución</h2>
        <?php if (!empty($distribucion)): ?>
            <strong><?= e((string) $distribucion['etiqueta']) ?></strong>
            <p class="h6 mt-1 mb-1 gs-selected-availability <?= !empty($distribucion['ventas_habilitadas']) ? 'text-success' : 'text-danger' ?>"><?= !empty($distribucion['ventas_habilitadas']) ? 'Disponible para entregas' : 'Entregas bloqueadas' ?></p>
            <p class="small text-secondary mb-0"><?= (int) $distribucion['ocupados'] ?> / <?= (int) $distribucion['capacidad'] ?> <?= e($unit) ?> ocupados</p>
            <?php if (!empty($distribucion['llena'])): ?><p class="small text-secondary mb-0">Capacidad de referencia alcanzada. Continúa seleccionada hasta elegir otra cuenta.</p><?php endif; ?>
            <?php if (!empty($distribucion['motivo_bloqueo'])): ?><p class="small text-danger mb-0 mt-1"><?= e($distribucion['motivo_bloqueo']) ?> Revisa la cuenta o selecciona otra.</p><?php endif; ?>
        <?php else: ?>
            <p class="mb-0">Aún no se ha seleccionado una cuenta. El administrador debe abrir una cuenta preparada y seleccionarla para distribución.</p>
        <?php endif; ?>
    </div>
    <?php if (!empty($distribucion)): ?><a class="btn btn-primary" href="<?= e(url('/' . $service . '/distribucion')) ?>"><?= icon('arrow-right') ?><span class="button-label">Entregar accesos</span></a><?php endif; ?>
    <?php if (!empty($distribucion)): ?><details class="gs-selected-dates">
        <summary class="small text-secondary">Fechas de distribución</summary>
        <?php $fechasDistribucion = $distribucion; require __DIR__ . '/fechas_distribucion.php'; ?>
    </details><?php endif; ?>
</div></div>
