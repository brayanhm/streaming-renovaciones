<?php
declare(strict_types=1);

use App\Models\Modalidad;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Editar tipo de suscripcion</h1>
    <a href="<?= e(url('/tipos-suscripcion')) ?>" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(url('/tipos-suscripcion/actualizar/' . (int) $item['id'])) ?>" id="edit-plan-form">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="plataforma_id">Plataforma</label>
                    <select class="form-select" id="plataforma_id" name="plataforma_id" required>
                        <?php foreach ($platforms as $platform): ?>
                            <option value="<?= e((string) $platform['id']) ?>" <?= (int) $item['plataforma_id'] === (int) $platform['id'] ? 'selected' : '' ?>>
                                <?= e((string) $platform['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="nombre_modalidad">Nombre tipo suscripcion</label>
                    <input
                        type="text"
                        class="form-control"
                        id="nombre_modalidad"
                        name="nombre_modalidad"
                        value="<?= e((string) $item['nombre_modalidad']) ?>"
                        required
                    >
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tipo_cuenta">Tipo de cuenta</label>
                    <select class="form-select js-tipo-cuenta" id="tipo_cuenta" name="tipo_cuenta" required>
                        <?php foreach ($tiposCuenta as $tipoCuenta): ?>
                            <option value="<?= e($tipoCuenta) ?>" <?= (string) ($item['tipo_cuenta'] ?? 'CUENTA_COMPLETA') === $tipoCuenta ? 'selected' : '' ?>>
                                <?= e($tipoCuenta) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="duracion_meses">Duracion (meses)</label>
                    <input
                        type="number"
                        min="1"
                        class="form-control"
                        id="duracion_meses"
                        name="duracion_meses"
                        value="<?= e((string) max(1, (int) ($item['duracion_meses'] ?? 1))) ?>"
                        required
                    >
                </div>
                <div class="col-md-4 js-dispositivos-wrap">
                    <label class="form-label" for="dispositivos">Dispositivos (si aplica)</label>
                    <input
                        type="number"
                        min="1"
                        class="form-control"
                        id="dispositivos"
                        name="dispositivos"
                        value="<?= e((string) ($item['dispositivos'] ?? '')) ?>"
                    >
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="precio">Precio</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control"
                        id="precio"
                        name="precio"
                        value="<?= e((string) $item['precio']) ?>"
                        required
                    >
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary btn-lg" type="submit">Guardar cambios</button>
                    <a href="<?= e(url('/tipos-suscripcion')) ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('edit-plan-form');
    if (!form) return;

    const tipoCuenta = form.querySelector('.js-tipo-cuenta');
    const dispositivosWrap = form.querySelector('.js-dispositivos-wrap');
    const dispositivos = form.querySelector('#dispositivos');

    const apply = () => {
        const value = tipoCuenta.value;
        const needs = value === 'POR_DISPOSITIVOS' || value === 'AMBOS';
        dispositivosWrap.classList.toggle('d-none', !needs);
        dispositivos.required = value === 'POR_DISPOSITIVOS';
        if (!needs) {
            dispositivos.value = '';
        }
    };

    tipoCuenta.addEventListener('change', apply);
    apply();
})();
</script>
