<?php
declare(strict_types=1);
$canAdmin = $canAdmin ?? false;
$accountId = (int) $cuenta['id'];
$service = 'claude';
$occupationExpanded = true;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Editar cuenta Claude</h1>
    <a href="<?= e(url('/claude/' . $accountId)) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver a la cuenta</span></a>
</div>
<div class="card shadow-sm"><div class="card-body">
    <?php if ($canAdmin): ?>
    <form method="post" action="<?= e(url('/claude/' . $accountId . '/actualizar')) ?>" autocomplete="off" data-chatgpt-submit>
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="etiqueta">Nombre interno</label>
                <input type="text" class="form-control" id="etiqueta" name="etiqueta" value="<?= e(form_value('/claude/' . $accountId . '/actualizar', 'etiqueta', (string) ($cuenta['etiqueta'] ?? ''))) ?>" maxlength="100" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="correo">Correo de la cuenta</label>
                <input type="email" class="form-control" id="correo" name="correo" value="<?= e(form_value('/claude/' . $accountId . '/actualizar', 'correo', (string) ($cuenta['correo'] ?? ''))) ?>" maxlength="150" autocomplete="off" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password_cuenta">Nueva contraseña</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password_cuenta" name="password_cuenta" autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary" data-password-target="password_cuenta" aria-pressed="false"><?= icon('eye') ?><span class="button-label">Mostrar</span></button>
                </div>
                <small class="text-secondary">Déjala vacía para conservar la contraseña actual.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="capacidad">Capacidad en cupos</label>
                <input type="number" class="form-control" id="capacidad" name="capacidad" value="<?= e(form_value('/claude/' . $accountId . '/actualizar', 'capacidad', (string) ($cuenta['capacidad'] ?? 5))) ?>" min="1" max="99999" step="1" required>
                <small class="text-secondary">Solo el administrador puede vender por encima de esta capacidad.</small>
            </div>
            <div class="col-12">
                <label class="form-label" for="notas">Notas</label>
                <textarea class="form-control" id="notas" name="notas" rows="3"><?= e(form_value('/claude/' . $accountId . '/actualizar', 'notas', (string) ($cuenta['notas'] ?? ''))) ?></textarea>
            </div>
            <div class="col-12"><button type="submit" class="btn btn-primary"><?= icon('save') ?><span class="button-label">Guardar cambios</span></button></div>
        </div>
    </form>
    <?php else: ?>
        <p class="text-secondary mb-0">Solo el administrador puede editar esta cuenta.</p>
    <?php endif; ?>
</div></div>
<?php if ($canAdmin): ?>
<div class="card shadow-sm mt-3"><div class="card-body">
    <h2 class="h5">Ocupación de la cuenta</h2>
    <?php require __DIR__ . '/../cuentas_compartidas/ocupacion.php'; ?>
</div></div>
<?php endif; ?>
<?php require __DIR__ . '/scripts.php'; ?>
