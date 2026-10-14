<?php declare(strict_types=1); ?>
<div class="card border-primary mb-4"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h2 class="h6 text-secondary mb-2">Cuenta seleccionada para distribución</h2>
        <?php if (!empty($distribucion)): ?>
            <strong><?= e((string) $distribucion['etiqueta']) ?></strong>
            <span class="badge text-bg-<?= $distribucion['llena'] ? 'warning' : 'primary' ?> ms-1"><?= e($distribucion['estado_distribucion']) ?></span>
            <p class="small text-secondary mb-0 mt-1"><?= (int) $distribucion['ocupados'] ?> / <?= (int) $distribucion['capacidad'] ?> <?= e($unit) ?> ocupados · <?= (int) $distribucion['libres'] ?> libres</p>
            <?php if (!in_array($distribucion['estado_periodo'], ['PAGADO', 'GRACIA'], true) || (int) $distribucion['activo'] !== 1): ?><p class="small text-danger mb-0 mt-1">Ventas bloqueadas. Revisa el plan y la vigencia o selecciona otra cuenta.</p><?php endif; ?>
        <?php else: ?>
            <p class="mb-0">Aún no se ha seleccionado una cuenta. El administrador debe abrir una cuenta preparada y seleccionarla para distribución.</p>
        <?php endif; ?>
    </div>
    <?php if (!empty($distribucion)): ?><a class="btn btn-primary" href="<?= e(url('/' . $service . '/' . (int) $distribucion['id'])) ?>"><?= icon('arrow-right') ?><span class="button-label">Abrir cuenta seleccionada</span></a><?php endif; ?>
</div></div>
