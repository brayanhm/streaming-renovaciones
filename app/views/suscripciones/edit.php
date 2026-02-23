<?php
declare(strict_types=1);

use App\Models\Modalidad;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Editar suscripcion</h1>
    <a href="<?= e(url('/suscripciones')) ?>" class="btn btn-outline-secondary">Volver</a>
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
                                <?= (int) $item['plataforma_id'] === (int) $plataforma['id'] ? 'selected' : '' ?>
                            >
                                <?= e((string) $plataforma['nombre']) ?> (<?= e((string) $plataforma['tipo_servicio']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="modalidad_id">Tipo de suscripcion</label>
                    <select class="form-select js-modalidad" id="modalidad_id" name="modalidad_id" required>
                        <?php foreach ($tiposSuscripcion as $modalidad): ?>
                            <option
                                value="<?= e((string) $modalidad['id']) ?>"
                                data-plataforma-id="<?= e((string) $modalidad['plataforma_id']) ?>"
                                data-precio="<?= e((string) $modalidad['precio']) ?>"
                                <?= (int) $item['modalidad_id'] === (int) $modalidad['id'] ? 'selected' : '' ?>
                            >
                                <?= e((string) $modalidad['plataforma_nombre']) ?>
                                -
                                <?= e((string) $modalidad['nombre_modalidad']) ?>
                                (<?= e(Modalidad::tipoCuentaLabel((string) ($modalidad['tipo_cuenta'] ?? 'CUENTA_COMPLETA'), isset($modalidad['dispositivos']) ? (int) $modalidad['dispositivos'] : null)) ?>,
                                <?= e((string) max(1, (int) ($modalidad['duracion_meses'] ?? 1))) ?> mes(es),
                                $<?= e(number_format((float) $modalidad['precio'], 2, '.', ',')) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="precio_venta">Precio de venta (editable)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control js-precio-venta"
                        id="precio_venta"
                        name="precio_venta"
                        value="<?= e((string) ($item['precio_venta'] ?? '')) ?>"
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
                    <label class="form-label" for="usuario_proveedor">Usuario proveedor</label>
                    <input type="text" class="form-control" id="usuario_proveedor" name="usuario_proveedor" value="<?= e((string) ($item['usuario_proveedor'] ?? '')) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="flag_no_renovo" name="flag_no_renovo" <?= (int) $item['flag_no_renovo'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="flag_no_renovo">Marcar no renovo</label>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary btn-lg" type="submit">Guardar cambios</button>
                    <a href="<?= e(url('/suscripciones')) ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
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
    const precioVentaInput = form.querySelector('.js-precio-venta');

    const applyFilters = () => {
        const plataformaId = plataformaSelect.value;
        const selectedPlatformOption = plataformaSelect.options[plataformaSelect.selectedIndex];
        const tipoServicio = selectedPlatformOption ? selectedPlatformOption.dataset.tipo : '';

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
        } else {
            usuarioWrap.classList.remove('d-none');
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
