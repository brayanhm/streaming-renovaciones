<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Mi perfil</h1>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<div class="row g-4">
    <div class="col-lg-6">
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
                    <button type="submit" class="btn btn-primary">Guardar usuario</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
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
                    <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>
