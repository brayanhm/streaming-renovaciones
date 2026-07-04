<?php
declare(strict_types=1);

$rolBadge = static function (string $rol): string {
    return $rol === 'admin' ? 'text-bg-warning' : 'text-bg-secondary';
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Usuarios del sistema</h1>
        <small class="text-secondary">Gestiona quién puede acceder al panel y sus contraseñas.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('/usuarios/auditoria')) ?>" class="btn btn-outline-secondary">Auditoría</a>
        <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al panel</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Creado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-4">No hay usuarios registrados.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <?php
                                $uid = (int) $item['id'];
                                $isSelf = $uid === (int) ($currentUserId ?? 0);
                                $isActive = (int) ($item['activo'] ?? 1) === 1;
                                $isAdmin = (string) ($item['rol'] ?? '') === 'admin';
                                $isLastAdmin = $isAdmin && $isActive && (int) ($activeAdmins ?? 0) <= 1;
                                ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <?= e((string) $item['username']) ?>
                                        <?php if ($isSelf): ?><span class="badge text-bg-primary ms-1">Tú</span><?php endif; ?>
                                    </td>
                                    <td><span class="badge <?= e($rolBadge((string) $item['rol'])) ?>"><?= e((string) $item['rol']) ?></span></td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge text-bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-secondary"><?= e((string) ($item['created_at'] ?? '')) ?></small></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/usuarios/editar/' . $uid)) ?>">Editar</a>

                                            <?php if ($isActive): ?>
                                                <form method="post" action="<?= e(url('/usuarios/estado/' . $uid)) ?>"
                                                      onsubmit="return confirm('¿Desactivar el acceso de este usuario?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="activo" value="0">
                                                    <button class="btn btn-outline-warning btn-sm" type="submit"
                                                        <?= $isSelf || $isLastAdmin ? 'disabled title="No disponible para tu cuenta o el último admin"' : '' ?>>
                                                        Desactivar
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" action="<?= e(url('/usuarios/estado/' . $uid)) ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="activo" value="1">
                                                    <button class="btn btn-outline-success btn-sm" type="submit">Activar</button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="post" action="<?= e(url('/usuarios/eliminar/' . $uid)) ?>"
                                                  onsubmit="return confirm('¿Eliminar definitivamente a este usuario?')">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-outline-danger btn-sm" type="submit"
                                                    <?= $isSelf || $isLastAdmin ? 'disabled title="No disponible para tu cuenta o el último admin"' : '' ?>>
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Nuevo usuario</h2>
                <form method="post" action="<?= e(url('/usuarios')) ?>" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="username">Usuario</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= e(old('username')) ?>" minlength="3" required autocomplete="off">
                        <div class="form-text">Mínimo 3 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="rol">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <?php $oldRol = old('rol', 'operador'); ?>
                            <?php foreach (($roles ?? []) as $r): ?>
                                <option value="<?= e($r) ?>" <?= $oldRol === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Solo <strong>admin</strong> puede gestionar usuarios.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required autocomplete="new-password">
                        <div class="form-text">Mínimo 6 caracteres.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password_confirm">Confirmar contraseña</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="6" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg">Crear usuario</button>
                </form>
            </div>
        </div>
    </div>
</div>
