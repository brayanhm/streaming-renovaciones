<?php declare(strict_types=1); ?>
<div class="card border-primary mb-4 gs-account-summary"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h2 class="h6 text-secondary mb-2">Cuenta seleccionada para distribución</h2>
        <?php if (!empty($distribucion)): ?>
            <strong><?= e((string) $distribucion['etiqueta']) ?></strong>
            <span class="badge text-bg-<?= $distribucion['llena'] ? 'success' : 'primary' ?> ms-1"><?= e($distribucion['estado_distribucion']) ?></span>
            <p class="h5 mt-3 mb-1"><?= (int) ($distribucion['cupos_vendibles'] ?? 0) ?> cupos para vender</p>
            <p class="small text-secondary mb-0"><?= (int) $distribucion['ocupados'] ?> / <?= (int) $distribucion['capacidad'] ?> <?= e($unit) ?> ocupados</p>
            <?php if (!empty($distribucion['motivo_bloqueo'])): ?><p class="small text-danger mb-0 mt-1"><?= e($distribucion['motivo_bloqueo']) ?> Revisa la cuenta o selecciona otra.</p><?php endif; ?>
        <?php else: ?>
            <p class="mb-0">Aún no se ha seleccionado una cuenta. El administrador debe abrir una cuenta preparada y seleccionarla para distribución.</p>
        <?php endif; ?>
    </div>
    <?php if (!empty($distribucion)): ?><a class="btn btn-primary" href="<?= e(url('/' . $service . '/distribucion')) ?>"><?= icon('arrow-right') ?><span class="button-label">Entregar accesos</span></a><?php endif; ?>
</div></div>
