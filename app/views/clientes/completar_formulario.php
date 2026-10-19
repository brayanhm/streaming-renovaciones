<?php declare(strict_types=1); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Completar contacto</h1>
    <a class="btn btn-outline-secondary" href="<?= e(url('/clientes/completar')) ?>">Volver a pendientes</a>
</div>
<div class="gs-operation-context mb-3">
    <strong>Cliente #<?= (int) $item['id'] ?></strong>
    <?php foreach (array_slice($subscriptions, 0, 3) as $subscription): ?>
    <span><?= e($subscription['plataforma_nombre']) ?> · <?= e($subscription['usuario_proveedor'] ?: 'Usuario sin registrar') ?></span>
    <?php endforeach; ?>
    <?php if ($subscriptions === []): ?><span>Sin suscripciones asociadas.</span><?php endif; ?>
</div>
<form method="post" action="<?= e(url('/clientes/completar/actualizar/' . (int) $item['id'])) ?>" data-contact-form>
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="missing-contact-name">Contacto</label>
        <input class="form-control" id="missing-contact-name" name="contacto" value="<?= e($item['nombre']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label" for="missing-contact-phone">Número de celular</label>
        <input class="form-control" id="missing-contact-phone" name="numero" value="<?= e(local_whatsapp_phone_bolivia($item['telefono'])) ?>" inputmode="numeric" maxlength="8" pattern="[67][0-9]{7}" required aria-describedby="missing-phone-help">
        <p class="small text-secondary mt-2" id="missing-phone-help">8 dígitos que comiencen con 6 o 7. El sistema agrega +591.</p>
    </div>
    <button type="submit" name="continuar" value="0" class="btn btn-outline-primary"><?= icon('save') ?><span class="button-label">Guardar</span></button>
    <button type="submit" name="continuar" value="1" class="btn btn-primary"><?= icon('arrow-right') ?><span class="button-label">Guardar y siguiente</span></button>
</form>
