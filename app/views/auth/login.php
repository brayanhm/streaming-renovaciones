<?php
declare(strict_types=1);
?>
<style>
    body.app-body {
        background:
            radial-gradient(ellipse at 15% 0%,   rgba(0,212,255,.08) 0%, transparent 50%),
            radial-gradient(ellipse at 85% 100%, rgba(224,64,251,.08) 0%, transparent 50%),
            linear-gradient(160deg, #040a12 0%, #060c16 50%, #040a12 100%) !important;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    body.app-body::before {
        content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
        background:repeating-linear-gradient(0deg,transparent,transparent 3px,rgba(0,0,0,.05) 3px,rgba(0,0,0,.05) 4px);
    }

    .login-orb {
        position: fixed;
        border-radius: 50%;
        filter: blur(90px);
        pointer-events: none;
        z-index: 0;
    }
    .orb-cyan    { width:520px;height:520px;background:rgba(0,212,255,.12);  top:-160px;left:-160px; }
    .orb-magenta { width:420px;height:420px;background:rgba(224,64,251,.12); bottom:-120px;right:-120px; }
    .orb-mid     { width:260px;height:260px;background:rgba(77,159,255,.08); top:45%;left:55%; }

    .login-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
        position: relative;
        z-index: 1;
    }

    .login-card {
        width: 100%;
        max-width: 430px;
        background: rgba(12, 26, 46, 0.85);
        border: 1px solid rgba(0,212,255,.2);
        border-radius: 16px;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow:
            0 0 0 1px rgba(0,212,255,.08) inset,
            0 30px 60px rgba(0,0,0,.7),
            0 0 40px rgba(0,212,255,.06);
        padding: 2.5rem;
        position: relative;
        overflow: hidden;
    }

    /* top neon line */
    .login-card::before {
        content: '';
        position: absolute;
        top: 0; left: 10%; right: 10%;
        height: 2px;
        background: linear-gradient(90deg, transparent, #00d4ff, #e040fb, #00d4ff, transparent);
        border-radius: 0 0 4px 4px;
    }

    /* corner accents */
    .login-card::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0;
        width: 60px; height: 60px;
        border-left: 2px solid rgba(0,212,255,.3);
        border-bottom: 2px solid rgba(0,212,255,.3);
        border-radius: 0 0 0 14px;
        pointer-events: none;
    }

    .login-logo {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 2rem;
    }

    .login-logo img {
        height: 72px;
        max-width: 200px;
        object-fit: contain;
        filter: drop-shadow(0 0 18px rgba(0,212,255,.55)) drop-shadow(0 0 6px rgba(224,64,251,.35));
    }

    .login-logo .app-subtitle {
        font-family: 'Rajdhani','Exo 2',system-ui,sans-serif;
        font-size: 0.72rem;
        color: #3a6080;
        text-align: center;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .login-card .form-label {
        font-family: 'Rajdhani','Exo 2',system-ui,sans-serif;
        color: #507090;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: .07em;
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }

    .login-card .form-control {
        background: rgba(4,12,24,.8) !important;
        border: 1px solid rgba(0,212,255,.2) !important;
        color: #b8d8f0 !important;
        border-radius: 8px;
        padding: 0.7rem 1rem;
        font-family: 'Rajdhani','Exo 2',system-ui,sans-serif;
        font-size: 1rem;
        transition: border-color .2s, box-shadow .2s;
    }
    .login-card .form-control:focus {
        background: rgba(6,16,30,.9) !important;
        border-color: #00d4ff !important;
        box-shadow: 0 0 0 3px rgba(0,212,255,.18), 0 0 16px rgba(0,212,255,.12) !important;
        color: #d8f0ff !important;
        outline: none;
    }
    .login-card .form-control::placeholder { color: #1e3a54 !important; }
    .login-card .form-control:-webkit-autofill,
    .login-card .form-control:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0 100px #040c18 inset !important;
        -webkit-text-fill-color: #b8d8f0 !important;
    }

    .btn-login {
        font-family: 'Rajdhani','Exo 2',system-ui,sans-serif;
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        background: linear-gradient(135deg, #00d4ff 0%, #0088cc 100%);
        border: 1px solid #00d4ff;
        border-radius: 8px;
        color: #000;
        padding: 0.75rem;
        width: 100%;
        transition: box-shadow .2s, transform .15s;
        box-shadow: 0 0 20px rgba(0,212,255,.3);
        cursor: pointer;
    }
    .btn-login:hover {
        box-shadow: 0 0 35px rgba(0,212,255,.55), 0 0 60px rgba(0,212,255,.2);
        transform: translateY(-1px);
        color: #000;
    }
    .btn-login:active { transform: translateY(0); }

    .btn-login-success {
        background: linear-gradient(135deg, #00ff88 0%, #00aa55 100%);
        border-color: #00ff88;
        box-shadow: 0 0 20px rgba(0,255,136,.28);
    }
    .btn-login-success:hover {
        box-shadow: 0 0 35px rgba(0,255,136,.5), 0 0 60px rgba(0,255,136,.18);
    }

    .login-card .alert {
        background: rgba(255,193,7,.08);
        border: 1px solid rgba(255,193,7,.35);
        color: #ffd060;
        border-radius: 8px;
        font-size: .85rem;
        font-family: 'Rajdhani','Exo 2',system-ui,sans-serif;
    }

    .login-footer {
        text-align: center;
        margin-top: 1.75rem;
        color: #1e3a54;
        font-size: 0.72rem;
        letter-spacing: .06em;
        font-family: 'Rajdhani','Exo 2',system-ui,sans-serif;
        text-transform: uppercase;
    }

    .login-sep {
        border: none;
        border-top: 1px solid rgba(0,212,255,.1);
        margin: 1.5rem 0;
    }

    .pw-wrapper {
        position: relative;
    }
    .pw-wrapper .form-control {
        padding-right: 2.8rem;
    }
    .pw-toggle {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        color: #3a6080;
        line-height: 1;
        transition: color .2s;
    }
    .pw-toggle:hover { color: #00d4ff; }
    .pw-toggle svg { display: block; }
</style>

<div class="login-orb orb-cyan"></div>
<div class="login-orb orb-magenta"></div>
<div class="login-orb orb-mid"></div>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <img src="<?= e(url('/img/logo.png')) ?>" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
            <span class="app-subtitle">Sistema de gestión de membresías</span>
        </div>

        <?php if (($hasUsers ?? false) === true): ?>
            <form method="post" action="<?= e(url('/login')) ?>">
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
                        <button type="button" class="pw-toggle" onclick="togglePw('password', this)" tabindex="-1">
                            <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login">Iniciar sesión</button>
            </form>
        <?php else: ?>
            <div class="alert mb-4">
                Aún no hay usuarios registrados. Crea el administrador inicial para empezar.
            </div>
            <form method="post" action="<?= e(url('/login')) ?>">
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
                        <button type="button" class="pw-toggle" onclick="togglePw('password', this)" tabindex="-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Confirmar contraseña</label>
                    <div class="pw-wrapper">
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="••••••••" required>
                        <button type="button" class="pw-toggle" onclick="togglePw('password_confirm', this)" tabindex="-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login btn-login-success">Crear admin inicial</button>
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
    btn.innerHTML = isHidden
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709z"/><path fill-rule="evenodd" d="M13.646 14.354l-12-12 .708-.708 12 12-.708.708z"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>';
}
</script>
