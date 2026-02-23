<?php
declare(strict_types=1);

use App\Models\Modalidad;

if (!function_exists('tipo_suscripcion_label')) {
    function tipo_suscripcion_label(array $item): string
    {
        $tipoCuenta = (string) ($item['tipo_cuenta'] ?? 'CUENTA_COMPLETA');
        $dispositivos = isset($item['dispositivos']) ? (int) $item['dispositivos'] : null;
        $duracion = max(1, (int) ($item['duracion_meses'] ?? 1));

        return Modalidad::tipoCuentaLabel($tipoCuenta, $dispositivos) . ' - ' . $duracion . ' mes(es)';
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Suscripciones</h1>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al dashboard</a>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" action="<?= e(url('/suscripciones')) ?>" class="row g-2 mb-3">
                    <div class="col-12 col-md-7">
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            name="q"
                            placeholder="Buscar por cliente, telefono, plataforma o tipo"
                            value="<?= e($search ?? '') ?>"
                        >
                    </div>
                    <div class="col-12 col-md-3">
                        <select class="form-select form-select-lg" name="estado">
                            <option value="">Todos</option>
                            <?php foreach ($estados as $status): ?>
                                <option value="<?= e($status) ?>" <?= ($estado ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button class="btn btn-primary btn-lg" type="submit">Buscar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>Tipo suscripcion</th>
                                <th>Precio venta</th>
                                <th>Inicio</th>
                                <th>Vence</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="8" class="text-center text-secondary py-4">Sin registros.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e((string) $item['cliente_nombre']) ?></div>
                                        <small class="text-secondary"><?= e((string) $item['cliente_telefono']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= e((string) $item['plataforma_nombre']) ?></div>
                                        <small class="text-secondary"><?= e((string) $item['nombre_modalidad']) ?></small>
                                    </td>
                                    <td><?= e(tipo_suscripcion_label($item)) ?></td>
                                    <td>$<?= e(number_format((float) ($item['precio_final'] ?? $item['modalidad_precio'] ?? 0), 2, '.', ',')) ?></td>
                                    <td><?= e((string) $item['fecha_inicio']) ?></td>
                                    <td><?= e((string) $item['fecha_vencimiento']) ?></td>
                                    <td>
                                        <span class="badge text-bg-secondary"><?= e((string) $item['estado']) ?></span>
                                        <?php if ((int) $item['flag_no_renovo'] === 1): ?>
                                            <div><small class="text-danger">No renovo</small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-end">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/suscripciones/editar/' . (int) $item['id'])) ?>">Editar</a>
                                            <form method="post" action="<?= e(url('/suscripciones/eliminar/' . (int) $item['id'])) ?>" onsubmit="return confirm('Eliminar suscripcion?')">
                                                <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
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

    <div class="col-xl-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Nueva suscripcion</h2>
                <form method="post" action="<?= e(url('/suscripciones')) ?>" id="create-subscription-form">
                    <div class="mb-3">
                        <label class="form-label" for="cliente_id">Cliente</label>
                        <select class="form-select" id="cliente_id" name="cliente_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldCliente = old('cliente_id'); ?>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= e((string) $cliente['id']) ?>" <?= $oldCliente === (string) $cliente['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $cliente['nombre']) ?> - <?= e((string) $cliente['telefono']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="plataforma_id">Plataforma</label>
                        <select class="form-select js-plataforma" id="plataforma_id" name="plataforma_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldPlat = old('plataforma_id'); ?>
                            <?php foreach ($plataformas as $plataforma): ?>
                                <option
                                    value="<?= e((string) $plataforma['id']) ?>"
                                    data-tipo="<?= e((string) $plataforma['tipo_servicio']) ?>"
                                    <?= $oldPlat === (string) $plataforma['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $plataforma['nombre']) ?> (<?= e((string) $plataforma['tipo_servicio']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="modalidad_id">Tipo de suscripcion</label>
                        <select class="form-select js-modalidad" id="modalidad_id" name="modalidad_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldMod = old('modalidad_id'); ?>
                            <?php foreach ($tiposSuscripcion as $modalidad): ?>
                                <option
                                    value="<?= e((string) $modalidad['id']) ?>"
                                    data-plataforma-id="<?= e((string) $modalidad['plataforma_id']) ?>"
                                    data-precio="<?= e((string) $modalidad['precio']) ?>"
                                    <?= $oldMod === (string) $modalidad['id'] ? 'selected' : '' ?>
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

                    <div class="mb-3">
                        <label class="form-label" for="precio_venta">Precio de venta (editable)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="form-control js-precio-venta"
                            id="precio_venta"
                            name="precio_venta"
                            value="<?= e(old('precio_venta')) ?>"
                            required
                        >
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label" for="fecha_inicio">Fecha inicio</label>
                            <input
                                type="date"
                                class="form-control"
                                id="fecha_inicio"
                                name="fecha_inicio"
                                value="<?= e(old('fecha_inicio', date('Y-m-d'))) ?>"
                                required
                            >
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="fecha_vencimiento">Fecha vencimiento</label>
                            <input
                                type="date"
                                class="form-control"
                                id="fecha_vencimiento"
                                name="fecha_vencimiento"
                                value="<?= e(old('fecha_vencimiento', date('Y-m-d', strtotime('+1 month')))) ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="mt-3 mb-3">
                        <label class="form-label" for="estado">Estado inicial</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <?php $oldEstado = old('estado', 'ACTIVO'); ?>
                            <?php foreach ($estados as $status): ?>
                                <option value="<?= e($status) ?>" <?= $oldEstado === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 js-usuario-wrap">
                        <label class="form-label" for="usuario_proveedor">Usuario proveedor (solo renovable)</label>
                        <input type="text" class="form-control" id="usuario_proveedor" name="usuario_proveedor" value="<?= e(old('usuario_proveedor')) ?>">
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="flag_no_renovo" name="flag_no_renovo" <?= old('flag_no_renovo') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="flag_no_renovo">
                            Marcar no renovo
                        </label>
                    </div>

                    <button type="submit" class="btn btn-success w-100 btn-lg">Guardar suscripcion</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('create-subscription-form');
    if (!form) return;

    const plataformaSelect = form.querySelector('.js-plataforma');
    const modalidadSelect = form.querySelector('.js-modalidad');
    const usuarioWrap = form.querySelector('.js-usuario-wrap');
    const precioVentaInput = form.querySelector('.js-precio-venta');

    const applyFilters = () => {
        const plataformaId = plataformaSelect.value;
        const selectedPlatformOption = plataformaSelect.options[plataformaSelect.selectedIndex];
        const tipoServicio = selectedPlatformOption ? selectedPlatformOption.dataset.tipo : '';
        let hasVisible = false;

        for (const option of modalidadSelect.options) {
            if (!option.value) {
                option.hidden = false;
                continue;
            }
            const belongs = option.dataset.plataformaId === plataformaId;
            option.hidden = !belongs;
            if (belongs) hasVisible = true;
        }

        if (modalidadSelect.selectedOptions.length > 0) {
            const selected = modalidadSelect.selectedOptions[0];
            if (selected && selected.value && selected.hidden) {
                modalidadSelect.value = '';
            }
        }

        if (!hasVisible && plataformaId !== '') {
            modalidadSelect.value = '';
        }

        if (tipoServicio === 'DESECHABLE') {
            usuarioWrap.classList.add('d-none');
        } else {
            usuarioWrap.classList.remove('d-none');
        }

        const selectedModalidad = modalidadSelect.options[modalidadSelect.selectedIndex];
        if (selectedModalidad && selectedModalidad.dataset.precio) {
            precioVentaInput.value = selectedModalidad.dataset.precio;
        }
    };

    plataformaSelect.addEventListener('change', applyFilters);
    modalidadSelect.addEventListener('change', applyFilters);
    applyFilters();
})();
</script>
