<?php
declare(strict_types=1);

use App\Models\Modalidad;
use App\Models\Plataforma;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Editar suscripcion</h1>
    <a href="<?= e(url('/suscripciones')) ?>" class="btn btn-outline-secondary">Volver a suscripciones</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(url('/suscripciones/actualizar/' . (int) $item['id'])) ?>" id="edit-subscription-form">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="cliente_id">Cliente</label>
                    <select class="form-select" id="cliente_id" name="cliente_id" required>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= e((string) $cliente['id']) ?>" <?= (int) $item['cliente_id'] === (int) $cliente['id'] ? 'selected' : '' ?>>
                                <?= e((string) $cliente['nombre']) ?> - <?= e((string) $cliente['telefono']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="plataforma_id">Plataforma</label>
                    <select class="form-select js-plataforma" id="plataforma_id" name="plataforma_id" required>
                        <?php foreach ($plataformas as $plataforma): ?>
                            <option
                                value="<?= e((string) $plataforma['id']) ?>"
                                data-tipo="<?= e((string) $plataforma['tipo_servicio']) ?>"
                                data-dato-renovacion="<?= e((string) Plataforma::normalizeDatoRenovacion((string) ($plataforma['dato_renovacion'] ?? ''), (string) ($plataforma['tipo_servicio'] ?? ''))) ?>"
                                <?= (int) $item['plataforma_id'] === (int) $plataforma['id'] ? 'selected' : '' ?>
                            >
                                <?= e((string) $plataforma['nombre']) ?> (<?= e((string) $plataforma['tipo_servicio']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="modalidad_id">Plan de suscripcion</label>
                    <select class="form-select js-modalidad" id="modalidad_id" name="modalidad_id" required>
                        <?php foreach ($tiposSuscripcion as $modalidad): ?>
                            <option
                                value="<?= e((string) $modalidad['id']) ?>"
                                data-plataforma-id="<?= e((string) $modalidad['plataforma_id']) ?>"
                                data-precio="<?= e((string) ((int) round((float) $modalidad['precio']))) ?>"
                                <?= (int) $item['modalidad_id'] === (int) $modalidad['id'] ? 'selected' : '' ?>
                            >
                                <?= e((string) $modalidad['plataforma_nombre']) ?>
                                -
                                <?= e((string) $modalidad['nombre_modalidad']) ?>
                                (<?= e(Modalidad::tipoCuentaLabel((string) ($modalidad['tipo_cuenta'] ?? 'CUENTA_COMPLETA'), isset($modalidad['dispositivos']) ? (int) $modalidad['dispositivos'] : null)) ?>,
                                <?= e((string) max(1, (int) ($modalidad['duracion_meses'] ?? 1))) ?> mes(es),
                                <?= e(money((float) $modalidad['precio'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="precio_venta">Precio final de venta (Bs)</label>
                    <input
                        type="number"
                        step="1"
                        min="1"
                        class="form-control js-precio-venta"
                        id="precio_venta"
                        name="precio_venta"
                        value="<?= e((string) ((int) round((float) ($item['precio_venta'] ?? 0)))) ?>"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="fecha_inicio">Fecha inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= e((string) $item['fecha_inicio']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="fecha_vencimiento">Fecha vencimiento</label>
                    <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" value="<?= e((string) $item['fecha_vencimiento']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="estado">Estado</label>
                    <select class="form-select" id="estado" name="estado" required>
                        <?php foreach ($estados as $status): ?>
                            <option value="<?= e($status) ?>" <?= (string) $item['estado'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 js-usuario-wrap">
                    <label class="form-label js-usuario-label" for="usuario_proveedor">Dato de la cuenta para renovar</label>
                    <input
                        type="text"
                        class="form-control js-usuario-input"
                        id="usuario_proveedor"
                        name="usuario_proveedor"
                        value="<?= e((string) ($item['usuario_proveedor'] ?? '')) ?>"
                    >
                    <small class="text-secondary js-usuario-help">Se pedira segun la configuracion de la plataforma.</small>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="flag_no_renovo" name="flag_no_renovo" <?= (int) $item['flag_no_renovo'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="flag_no_renovo">Marcar como no renovado</label>
                    </div>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-lg w-100 w-sm-auto" type="submit">Guardar cambios</button>
                    <a href="<?= e(url('/suscripciones')) ?>" class="btn btn-outline-secondary btn-lg w-100 w-sm-auto">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('edit-subscription-form');
    if (!form) return;

    const plataformaSelect = form.querySelector('.js-plataforma');
    const modalidadSelect = form.querySelector('.js-modalidad');
    const usuarioWrap = form.querySelector('.js-usuario-wrap');
    const usuarioLabel = form.querySelector('.js-usuario-label');
    const usuarioInput = form.querySelector('.js-usuario-input');
    const usuarioHelp = form.querySelector('.js-usuario-help');
    const precioVentaInput = form.querySelector('.js-precio-venta');

    const applyFilters = () => {
        const plataformaId = plataformaSelect.value;
        const selectedPlatformOption = plataformaSelect.options[plataformaSelect.selectedIndex];
        const tipoServicio = selectedPlatformOption ? selectedPlatformOption.dataset.tipo : '';
        const datoRenovacion = selectedPlatformOption ? selectedPlatformOption.dataset.datoRenovacion : 'NO_APLICA';

        for (const option of modalidadSelect.options) {
            if (!option.value) {
                option.hidden = false;
                continue;
            }
            option.hidden = option.dataset.plataformaId !== plataformaId;
        }

        if (modalidadSelect.selectedOptions.length > 0) {
            const selected = modalidadSelect.selectedOptions[0];
            if (selected && selected.hidden) {
                modalidadSelect.value = '';
            }
        }

        if (tipoServicio === 'DESECHABLE') {
            usuarioWrap.classList.add('d-none');
            usuarioInput.required = false;
            usuarioInput.type = 'text';
            usuarioInput.value = '';
        } else {
            usuarioWrap.classList.remove('d-none');
            usuarioInput.required = true;
            if (datoRenovacion === 'CORREO') {
                usuarioLabel.textContent = 'Correo de la cuenta para renovar';
                usuarioInput.type = 'email';
                usuarioInput.placeholder = 'correo@dominio.com';
                usuarioHelp.textContent = 'Ingresa el correo exacto de la cuenta que se usara para renovar.';
            } else {
                usuarioLabel.textContent = 'Usuario de la cuenta para renovar';
                usuarioInput.type = 'text';
                usuarioInput.placeholder = 'Ej: usuario123';
                usuarioHelp.textContent = 'Ingresa el usuario exacto de la cuenta que se usara para renovar.';
            }
        }

        const selectedModalidad = modalidadSelect.options[modalidadSelect.selectedIndex];
        if (selectedModalidad && selectedModalidad.dataset.precio && (!precioVentaInput.value || precioVentaInput.value === '0')) {
            precioVentaInput.value = selectedModalidad.dataset.precio;
        }
    };

    plataformaSelect.addEventListener('change', applyFilters);
    modalidadSelect.addEventListener('change', applyFilters);
    applyFilters();
})();
</script>
