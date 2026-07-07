<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Editar cuenta principal</h1>
    <a href="<?= e(url('/cuentas-principales/' . (int) $cuenta['id'])) ?>" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(url('/cuentas-principales/actualizar/' . (int) $cuenta['id'])) ?>" autocomplete="off">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="etiqueta">Etiqueta / nombre interno</label>
                    <input type="text" class="form-control" id="etiqueta" name="etiqueta" value="<?= e((string) ($cuenta['etiqueta'] ?? '')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Plataforma</label>
                    <input type="text" class="form-control bg-light" value="<?= e((string) ($cuenta['plataforma_nombre'] ?? '')) ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="correo">Correo de la cuenta</label>
                    <input type="text" class="form-control" id="correo" name="correo" value="<?= e((string) ($cuenta['correo'] ?? '')) ?>" autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_cuenta">Contraseña</label>
                    <input type="text" class="form-control" id="password_cuenta" name="password_cuenta" value="<?= e((string) ($password ?? '')) ?>" autocomplete="new-password">
                    <small class="text-secondary">Se guarda cifrada. Déjala vacía para no cambiarla.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="capacidad">Cantidad de usuarios sugeridos</label>
                    <input type="number" class="form-control" id="capacidad" name="capacidad" min="1" value="<?= e((string) ($cuenta['capacidad'] ?? 1)) ?>" required>
                    <small class="text-secondary">Actualmente asignados: <?= e((string) (int) ($cuenta['ocupados'] ?? 0)) ?>.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="fecha_inicio">Activación de la cuenta</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= e((string) ($cuenta['fecha_inicio'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="fecha_vencimiento">Vence (pago)</label>
                    <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" value="<?= e((string) ($cuenta['fecha_vencimiento'] ?? '')) ?>">
                    <small class="text-secondary">Vacío = 1 mes desde la activación.</small>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" <?= (int) ($cuenta['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">Cuenta activa</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notas">Notas</label>
                    <textarea class="form-control" id="notas" name="notas" rows="2"><?= e((string) ($cuenta['notas'] ?? '')) ?></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg">Guardar cambios</button>
                </div>
            </div>
        </form>

        <hr>
        <form method="post" action="<?= e(url('/cuentas-principales/eliminar/' . (int) $cuenta['id'])) ?>" onsubmit="return confirm('¿Eliminar esta cuenta principal?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger">Eliminar cuenta principal</button>
            <small class="text-secondary ms-2">Solo si no tiene usuarios asignados.</small>
        </form>
    </div>
</div>
