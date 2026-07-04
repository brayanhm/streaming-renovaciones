<?php
declare(strict_types=1);

$uid = (int) ($user['id'] ?? 0);
$isSelf = $uid === (int) ($currentUserId ?? 0);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Editar usuario</h1>
        <small class="text-secondary">
            <?= e((string) ($user['username'] ?? '')) ?>
            <?php if ($isSelf): ?><span class="badge text-bg-primary ms-1">Tú</span><?php endif; ?>
        </small>
    </div>
    <a href="<?= e(url('/usuarios')) ?>" class="btn btn-outline-secondary">Volver a usuarios</a>
</div>

<?php if (!empty($isLastActiveAdmin)): ?>
    <div class="alert alert-info">
        Este es el <strong>último administrador activo</strong>. No podrás quitarle el rol admin, desactivarlo ni eliminarlo.
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-4">Datos del usuario</h2>
                <form method="post" action="<?= e(url('/usuarios/actualizar/' . $uid)) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="username">Usuario</label>
                        <input type="text" class="form-control form-control-lg" id="username" name="username"
                               value="<?= e((string) ($user['username'] ?? '')) ?>" minlength="3" required>
                        <div class="form-text">Mínimo 3 caracteres.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="rol">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <?php $currentRol = (string) ($user['rol'] ?? 'operador'); ?>
                            <?php foreach (($roles ?? []) as $r): ?>
                                <option value="<?= e($r) ?>" <?= $currentRol === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-4">Restablecer contraseña</h2>
                <p class="text-secondary small">Como administrador fijas una nueva contraseña directamente, sin necesitar la anterior.</p>
                <form method="post" action="<?= e(url('/usuarios/password/' . $uid)) ?>" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="new_password">Nueva contraseña</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required autocomplete="new-password">
                        <div class="form-text">Mínimo 6 caracteres.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="confirm_password">Confirmar contraseña</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary">Restablecer contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>
