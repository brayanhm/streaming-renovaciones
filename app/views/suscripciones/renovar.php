<?php declare(strict_types=1); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Renovar suscripción</h1>
    <a class="btn btn-outline-secondary" href="<?= e(url('/dashboard')) ?>">Volver al panel</a>
</div>
<div class="gs-operation-context mb-3">
    <strong><?= e($item['cliente_nombre']) ?></strong>
    <span><?= e($item['plataforma_nombre']) ?> · <?= e($item['nombre_modalidad']) ?></span>
    <span>Usuario de la cuenta: <?= e($item['usuario_proveedor'] ?: 'Sin registrar') ?></span>
    <span>Vencimiento actual: <strong><?= e(display_date($item['fecha_vencimiento'])) ?></strong></span>
</div>
<?php if ($options === []): ?>
    <div class="alert alert-warning">No hay períodos disponibles para este plan. Revisa los planes de la plataforma antes de renovar.</div>
<?php else: ?>
<form method="post" action="<?= e(url('/suscripciones/renovar/' . (int) $item['id'])) ?>" data-form-behavior="renewal">
    <?= csrf_field() ?>
    <input type="hidden" name="token_renovacion" value="<?= e($renewalToken) ?>">
    <div class="mb-3">
        <label class="form-label" for="renewal-months">Período de renovación</label>
        <select class="form-select" id="renewal-months" name="meses" required>
            <?php foreach ($options as $option): ?>
            <option value="<?= (int) $option['meses'] ?>" data-price="<?= e(display_money($option['precio'])) ?>" data-due="<?= e(display_date($option['nuevo_vencimiento'])) ?>">
                <?= e(duration_label((int) $option['meses'])) ?> · <?= e(display_money($option['precio'])) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <dl class="row mb-3 gs-operation-preview" aria-live="polite">
        <dt class="col-sm-6">Importe a registrar</dt><dd class="col-sm-6" data-renewal-price></dd>
        <dt class="col-sm-6">Nuevo vencimiento</dt><dd class="col-sm-6" data-renewal-due></dd>
    </dl>
    <p class="small text-secondary">Al confirmar se registra el ingreso de la renovación. Si sigue vigente, el período se suma al vencimiento actual; si ya venció, se cuenta desde hoy.</p>
    <button type="submit" class="btn btn-primary"><?= icon('refresh-cw') ?><span class="button-label">Confirmar renovación</span></button>
</form>
<?php endif; ?>
