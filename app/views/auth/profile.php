<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Mi perfil</h1>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver al panel</span></a>
</div>

<nav class="nav nav-pills gap-2 mb-3" aria-label="Opciones de perfil">
    <a class="nav-link <?= $profileSection === 'usuario' ? 'active' : '' ?>" data-modal-link href="<?= e(url('/perfil?seccion=usuario')) ?>" <?= $profileSection === 'usuario' ? 'aria-current="page"' : '' ?>>Cambiar usuario</a>
    <a class="nav-link <?= $profileSection === 'password' ? 'active' : '' ?>" data-modal-link href="<?= e(url('/perfil?seccion=password')) ?>" <?= $profileSection === 'password' ? 'aria-current="page"' : '' ?>>Cambiar contraseña</a>
</nav>
<div class="row g-4">
    <?php if ($profileSection === 'usuario'): ?>
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-4">Cambiar nombre de usuario</h2>
                <form method="post" action="<?= e(url('/perfil')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="change_username">
                    <div class="mb-3">
                        <label class="form-label" for="username">Nuevo usuario</label>
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            id="username"
                            name="username"
                            value="<?= e((string) ($user['username'] ?? '')) ?>"
                            minlength="3"
                            required
                        >
                        <div class="form-text">Mínimo 3 caracteres.</div>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= icon('save') ?><span class="button-label">Guardar usuario</span></button>
                </form>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-4">Cambiar contraseña</h2>
                <form method="post" action="<?= e(url('/perfil')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label" for="current_password">Contraseña actual</label>
                        <input
                            type="password"
                            class="form-control"
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_password">Nueva contraseña</label>
                        <input
                            type="password"
                            class="form-control"
                            id="new_password"
                            name="new_password"
                            minlength="6"
                            autocomplete="new-password"
                            required
                        >
                        <div class="form-text">Mínimo 6 caracteres.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="confirm_password">Confirmar nueva contraseña</label>
                        <input
                            type="password"
                            class="form-control"
                            id="confirm_password"
                            name="confirm_password"
                            minlength="6"
                            autocomplete="new-password"
                            required
                        >
                    </div>
                    <button type="submit" class="btn btn-primary"><?= icon('key-round') ?><span class="button-label">Actualizar contraseña</span></button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
