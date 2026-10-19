<?php declare(strict_types=1); ?>
<div class="gs-row-actions">
    <?php if (!empty($row['distribucion_actual'])): ?><a class="btn btn-primary btn-sm" href="<?= e(url('/' . $service . '/distribucion')) ?>">Entregar</a><?php endif; ?>
    <?php if ($canAdmin && !empty($row['puede_pagar_distribucion'])): ?>
        <?php $paymentAction = '/' . $service . '/' . (int) $row['id'] . '/distribuir'; ?>
        <details class="text-start" id="pago-distribucion-<?= e($service) ?>-<?= (int) $row['id'] ?>" data-form-panel <?= ($_SESSION['_old_action'] ?? '') === $paymentAction ? 'open' : '' ?>>
            <summary class="btn btn-outline-primary btn-sm text-nowrap">Pagar y distribuir</summary>
            <form method="post" action="<?= e(url($paymentAction)) ?>" class="mt-2" data-chatgpt-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="token_operacion" value="<?= e((string) ($indexSelectionToken ?? '')) ?>">
                <input type="hidden" name="origen" value="listado">
                <input type="hidden" name="q" value="<?= e((string) $q) ?>">
                <input type="hidden" name="filtro" value="<?= e((string) ($filter ?? '')) ?>">
                <input type="hidden" name="page" value="<?= (int) ($page ?? 1) ?>">
                <input type="hidden" name="por_pagina" value="<?= (int) ($perPage ?? 25) ?>">
                <input type="hidden" name="registrar_pago" value="1">
                <p class="small text-secondary mb-2">Pago de hoy: <strong><?= e(date('d/m/Y')) ?></strong>.<br><?= $service === 'chatgpt' ? 'Un mes pagado y un mes de gracia.' : 'Un mes pagado.' ?><br>Conserva los cupos ocupados.</p>
                <label class="form-label small" for="costo-distribucion-<?= (int) $row['id'] ?>">Costo real en Bs</label>
                <input class="form-control form-control-sm mb-2" type="number" id="costo-distribucion-<?= (int) $row['id'] ?>" name="monto_bs" value="<?= e(form_value($paymentAction, 'monto_bs')) ?>" min="0.01" max="99999999.99" step="0.01" required>
                <button class="btn btn-primary btn-sm" type="submit"><?= icon('circle-check') ?><span class="button-label">Registrar pago y distribuir</span></button>
            </form>
        </details>
    <?php elseif ($canAdmin && empty($row['distribucion_actual'])): ?>
        <form method="post" action="<?= e(url('/' . $service . '/' . (int) $row['id'] . '/distribuir')) ?>" data-chatgpt-submit>
            <?= csrf_field() ?>
            <input type="hidden" name="token_operacion" value="<?= e((string) ($indexSelectionToken ?? '')) ?>">
            <input type="hidden" name="origen" value="listado">
            <input type="hidden" name="q" value="<?= e((string) $q) ?>">
                <input type="hidden" name="filtro" value="<?= e((string) ($filter ?? '')) ?>">
                <input type="hidden" name="page" value="<?= (int) ($page ?? 1) ?>">
                <input type="hidden" name="por_pagina" value="<?= (int) ($perPage ?? 25) ?>">
            <button class="btn btn-primary btn-sm text-nowrap" type="submit" <?= !empty($row['motivo_bloqueo']) ? 'disabled' : '' ?> title="<?= e((string) ($row['motivo_bloqueo'] ?: 'Seleccionar esta cuenta para distribución')) ?>"><?= icon('circle-check') ?><span class="button-label">Distribuir</span></button>
        </form>
    <?php endif; ?>

    <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/' . $service . '/' . (int) $row['id'])) ?>">Abrir</a>
    <details class="gs-action-menu">
        <summary class="btn btn-outline-secondary btn-sm" aria-label="Más acciones para <?= e((string) $row['etiqueta']) ?>">Más</summary>
        <div class="gs-action-popover">
            <?php if ($canAdmin): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/' . $service . '/' . (int) $row['id'] . '/editar')) ?>"><?= icon('square-pen') ?><span class="button-label">Editar</span></a>
                <a class="btn btn-sm btn-outline-primary" data-modal-link href="<?= e(url('/' . $service . '/' . (int) $row['id'] . '/ocupacion')) ?>"><?= icon('users') ?><span class="button-label">Ajustar cupos</span></a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-secondary" data-modal-link href="<?= e(url('/' . $service . '/' . (int) $row['id'] . '/historial-distribucion')) ?>"><?= icon('scroll-text') ?><span class="button-label">Historial de distribución</span></a>
        </div>
    </details>
</div>
