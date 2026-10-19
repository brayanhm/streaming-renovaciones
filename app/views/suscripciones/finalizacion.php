<?php declare(strict_types=1); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><?= $reactivate ? 'Reactivar suscripción' : 'Editar vencimiento' ?></h1>
    <a class="btn btn-outline-secondary" href="<?= e(url('/clientes/' . (int) $item['cliente_id'])) ?>">Volver al cliente</a>
</div>
<div class="gs-operation-context mb-3">
    <strong><?= e($item['cliente_nombre']) ?></strong>
    <span><?= e($item['plataforma_nombre']) ?> · <?= e($item['nombre_modalidad']) ?></span>
    <span>Vencimiento actual: <strong><?= e(display_date($item['fecha_vencimiento'])) ?></strong></span>
</div>
<div class="alert alert-info">Este cambio no registra un ingreso. Para registrar el cobro de un período, utiliza «Renovar».</div>
<form method="post" action="<?= e(url('/suscripciones/finalizacion/' . (int) $item['id'])) ?>" data-form-behavior="due-date">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="new-due-date">Nueva fecha de vencimiento</label>
        <input class="form-control" id="new-due-date" name="fecha_vencimiento" type="date" value="<?= e($suggestedDue) ?>" min="<?= e($item['fecha_inicio']) ?>" required>
    </div>
    <p class="small text-secondary" role="status" aria-live="polite" data-due-date-status></p>
    <button type="submit" class="btn btn-primary"><?= icon('save') ?><span class="button-label">Guardar vencimiento</span></button>
</form>
