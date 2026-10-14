<?php
declare(strict_types=1);
?>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <img src="<?= e(url('/img/logo.png')) ?>" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
            <h1><?= e(APP_NAME) ?></h1>
            <span class="app-subtitle">Sistema de gestión de membresías</span>
        </div>

        <?php if (($hasUsers ?? false) === true): ?>
            <form method="post" action="<?= e(url('/login')) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        value="<?= e(old('username')) ?>"
                        placeholder="tu_usuario"
                        autocomplete="username"
                        required
                    >
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="pw-wrapper">
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="pw-toggle" onclick="togglePw('password', this)" aria-controls="password" aria-label="Mostrar contraseña" aria-pressed="false">
                            <?= icon('eye') ?>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100"><?= icon('log-in') ?><span class="button-label">Iniciar sesión</span></button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning mb-4">
                Aún no hay usuarios registrados. Crea el administrador inicial para empezar.
            </div>
            <form method="post" action="<?= e(url('/login')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="setup_admin">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario admin</label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        value="<?= e(old('username')) ?>"
                        placeholder="nombre_admin"
                        required
                    >
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="pw-wrapper">
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="pw-toggle" onclick="togglePw('password', this)" aria-controls="password" aria-label="Mostrar contraseña" aria-pressed="false">
                            <?= icon('eye') ?>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Confirmar contraseña</label>
                    <div class="pw-wrapper">
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="••••••••" required>
                        <button type="button" class="pw-toggle" onclick="togglePw('password_confirm', this)" aria-controls="password_confirm" aria-label="Mostrar confirmación de contraseña" aria-pressed="false">
                            <?= icon('eye') ?>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100"><?= icon('plus') ?><span class="button-label">Crear admin inicial</span></button>
            </form>
        <?php endif; ?>

        <div class="login-footer">
            <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?> &copy; <?= date('Y') ?>
        </div>
    </div>
</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    GhostIcons.set(btn, isHidden ? 'eye-off' : 'eye');
    const label = id === 'password_confirm' ? 'confirmación de contraseña' : 'contraseña';
    btn.setAttribute('aria-label', (isHidden ? 'Ocultar ' : 'Mostrar ') + label);
    btn.setAttribute('aria-pressed', String(isHidden));
}
</script>
