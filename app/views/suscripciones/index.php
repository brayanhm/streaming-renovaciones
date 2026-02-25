<?php
declare(strict_types=1);

use App\Models\Modalidad;
use App\Models\Plataforma;

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
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<?php if ((int) ($selectedClientId ?? 0) > 0): ?>
    <div class="alert alert-info">
        Cliente preseleccionado. Puedes cargar su vigencia de cuenta indicando fecha de inicio y fecha de vencimiento.
    </div>
<?php endif; ?>

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
                            placeholder="Busca por cliente, telefono, plataforma o plan"
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
                                <th>Plan</th>
                                <th>Costo (Bs)</th>
                                <th>Precio venta (Bs)</th>
                                <th>Ganancia (Bs)</th>
                                <th>Fecha inicio</th>
                                <th>Fecha vencimiento</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="10" class="text-center text-secondary py-4">No hay suscripciones registradas.</td></tr>
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
                                        <?php if (!empty($item['usuario_proveedor'])): ?>
                                            <?php $renovacionLabel = Plataforma::datoRenovacionLabel((string) ($item['plataforma_dato_renovacion'] ?? 'USUARIO')); ?>
                                            <div><small class="text-secondary"><?= e($renovacionLabel) ?>: <?= e((string) $item['usuario_proveedor']) ?></small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e(tipo_suscripcion_label($item)) ?></td>
                                    <td><?= e(money((float) ($item['costo_final'] ?? $item['modalidad_costo'] ?? 0))) ?></td>
                                    <td><?= e(money((float) ($item['precio_final'] ?? $item['modalidad_precio'] ?? 0))) ?></td>
                                    <td class="<?= (float) ($item['ganancia_final'] ?? 0) < 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' ?>">
                                        <?= e(money((float) ($item['ganancia_final'] ?? 0))) ?>
                                    </td>
                                    <td><?= e((string) $item['fecha_inicio']) ?></td>
                                    <td><?= e((string) $item['fecha_vencimiento']) ?></td>
                                    <td>
                                        <span class="badge text-bg-secondary"><?= e((string) $item['estado']) ?></span>
                                        <?php if ((int) $item['flag_no_renovo'] === 1): ?>
                                            <div><small class="text-danger">No renovado</small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/suscripciones/editar/' . (int) $item['id'])) ?>">Editar</a>
                                            <form method="post" action="<?= e(url('/suscripciones/eliminar/' . (int) $item['id'])) ?>" onsubmit="return confirm('Eliminar esta suscripcion?')">
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
        <div class="card shadow-sm" id="nueva-vigencia">
            <div class="card-body">
                <h2 class="h5 mb-3">Nueva vigencia de cuenta</h2>
                <form method="post" action="<?= e(url('/suscripciones')) ?>" id="create-subscription-form">
                    <div class="mb-3">
                        <label class="form-label" for="cliente_id">Cliente</label>
                        <select class="form-select" id="cliente_id" name="cliente_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldCliente = old('cliente_id', (int) ($selectedClientId ?? 0) > 0 ? (string) (int) $selectedClientId : ''); ?>
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
                                    data-dato-renovacion="<?= e((string) Plataforma::normalizeDatoRenovacion((string) ($plataforma['dato_renovacion'] ?? ''), (string) ($plataforma['tipo_servicio'] ?? ''))) ?>"
                                    <?= $oldPlat === (string) $plataforma['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $plataforma['nombre']) ?> (<?= e((string) $plataforma['tipo_servicio']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="modalidad_id">Plan de suscripcion</label>
                        <select class="form-select js-modalidad" id="modalidad_id" name="modalidad_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldMod = old('modalidad_id'); ?>
                            <?php foreach ($tiposSuscripcion as $modalidad): ?>
                                <option
                                    value="<?= e((string) $modalidad['id']) ?>"
                                    data-plataforma-id="<?= e((string) $modalidad['plataforma_id']) ?>"
                                    data-costo="<?= e((string) ((int) round((float) ($modalidad['costo'] ?? 0)))) ?>"
                                    data-precio="<?= e((string) ((int) round((float) $modalidad['precio']))) ?>"
                                    <?= $oldMod === (string) $modalidad['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $modalidad['plataforma_nombre']) ?>
                                    -
                                    <?= e((string) $modalidad['nombre_modalidad']) ?>
                                    (<?= e(Modalidad::tipoCuentaLabel((string) ($modalidad['tipo_cuenta'] ?? 'CUENTA_COMPLETA'), isset($modalidad['dispositivos']) ? (int) $modalidad['dispositivos'] : null)) ?>,
                                    <?= e((string) max(1, (int) ($modalidad['duracion_meses'] ?? 1))) ?> mes(es),
                                    Costo: <?= e(money((float) ($modalidad['costo'] ?? 0))) ?>,
                                    Venta: <?= e(money((float) $modalidad['precio'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="costo_base">Costo de la cuenta (Bs)</label>
                        <input
                            type="number"
                            step="1"
                            min="1"
                            class="form-control js-costo-base"
                            id="costo_base"
                            name="costo_base"
                            value="<?= e(old('costo_base')) ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="precio_venta">Precio final de venta (Bs)</label>
                        <input
                            type="number"
                            step="1"
                            min="1"
                            class="form-control js-precio-venta"
                            id="precio_venta"
                            name="precio_venta"
                            value="<?= e(old('precio_venta')) ?>"
                            required
                        >
                    </div>
                    <div class="alert alert-light border py-2 mb-3">
                        Ganancia estimada:
                        <strong class="js-ganancia"><?= e(money((float) 0)) ?></strong>
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
                    <small class="text-secondary d-block mt-1">Define manualmente la vigencia de la cuenta para este cliente.</small>

                    <div class="mt-3 mb-3">
                        <label class="form-label" for="estado">Estado inicial de la suscripcion</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <?php $oldEstado = old('estado', 'ACTIVO'); ?>
                            <?php foreach ($estados as $status): ?>
                                <option value="<?= e($status) ?>" <?= $oldEstado === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 js-usuario-wrap">
                        <label class="form-label js-usuario-label" for="usuario_proveedor">Dato de la cuenta para renovar</label>
                        <input
                            type="text"
                            class="form-control js-usuario-input"
                            id="usuario_proveedor"
                            name="usuario_proveedor"
                            value="<?= e(old('usuario_proveedor')) ?>"
                            placeholder="Ej: usuario123"
                        >
                        <small class="text-secondary js-usuario-help">Se pedira segun la configuracion de la plataforma.</small>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="flag_no_renovo" name="flag_no_renovo" <?= old('flag_no_renovo') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="flag_no_renovo">
                            Marcar como no renovado
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
    const usuarioLabel = form.querySelector('.js-usuario-label');
    const usuarioInput = form.querySelector('.js-usuario-input');
    const usuarioHelp = form.querySelector('.js-usuario-help');
    const costoBaseInput = form.querySelector('.js-costo-base');
    const precioVentaInput = form.querySelector('.js-precio-venta');
    const gananciaEl = form.querySelector('.js-ganancia');

    const applyFilters = () => {
        const plataformaId = plataformaSelect.value;
        const selectedPlatformOption = plataformaSelect.options[plataformaSelect.selectedIndex];
        const tipoServicio = selectedPlatformOption ? selectedPlatformOption.dataset.tipo : '';
        const datoRenovacion = selectedPlatformOption ? selectedPlatformOption.dataset.datoRenovacion : 'NO_APLICA';
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
        if (selectedModalidad && selectedModalidad.dataset.costo) {
            costoBaseInput.value = selectedModalidad.dataset.costo;
        }
        if (selectedModalidad && selectedModalidad.dataset.precio) {
            precioVentaInput.value = selectedModalidad.dataset.precio;
        }
        applyGanancia();
    };

    const applyGanancia = () => {
        const costo = Number.parseInt(costoBaseInput.value || '0', 10);
        const precio = Number.parseInt(precioVentaInput.value || '0', 10);
        const ganancia = (Number.isNaN(precio) ? 0 : precio) - (Number.isNaN(costo) ? 0 : costo);
        const sign = ganancia < 0 ? '-' : '';
        const abs = Math.abs(ganancia).toLocaleString('es-BO');
        gananciaEl.textContent = 'Bs ' + sign + abs;
        gananciaEl.classList.toggle('text-danger', ganancia < 0);
        gananciaEl.classList.toggle('text-success', ganancia >= 0);
    };

    plataformaSelect.addEventListener('change', applyFilters);
    modalidadSelect.addEventListener('change', applyFilters);
    costoBaseInput.addEventListener('input', applyGanancia);
    precioVentaInput.addEventListener('input', applyGanancia);
    applyFilters();
    applyGanancia();
})();
</script>
