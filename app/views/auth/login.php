<?php
declare(strict_types=1);
?>
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="card border-0 shadow">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 fw-bold mb-2">Acceso al sistema</h1>
                <p class="text-secondary mb-4">Gestion de clientes, vencimientos y renovaciones.</p>

                <?php if (($hasUsers ?? false) === true): ?>
                    <form method="post" action="<?= e(url('/login')) ?>" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario</label>
                            <input
                                type="text"
                                class="form-control form-control-lg"
                                id="username"
                                name="username"
                                value="<?= e(old('username')) ?>"
                                required
                            >
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Contrasena</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Iniciar sesion</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Aun no hay usuarios registrados. Crea el administrador inicial para empezar.
                    </div>
                    <form method="post" action="<?= e(url('/login')) ?>">
                        <input type="hidden" name="_action" value="setup_admin">
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario admin</label>
                            <input
                                type="text"
                                class="form-control form-control-lg"
                                id="username"
                                name="username"
                                value="<?= e(old('username')) ?>"
                                required
                            >
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contrasena</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                        </div>
                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">Confirmar contrasena</label>
                            <input type="password" class="form-control form-control-lg" id="password_confirm" name="password_confirm" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">Crear admin inicial</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
