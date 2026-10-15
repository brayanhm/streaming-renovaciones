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

        return Modalidad::tipoCuentaLabel($tipoCuenta, $dispositivos) . ' - ' . duration_label($duracion);
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Suscripciones</h1>
    <a href="<?= e(url('/suscripciones?nuevo=1')) ?>#nueva-vigencia" class="btn btn-primary" data-open-panel="nueva-vigencia"><?= icon('plus') ?><span class="button-label">Nueva suscripción</span></a>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver al panel</span></a>
</div>

<?php if ((int) ($selectedClientId ?? 0) > 0): ?>
    <div class="alert alert-info">
        Cliente preseleccionado. Puedes cargar su vigencia de cuenta indicando fecha de inicio y fecha de vencimiento.
    </div>
<?php endif; ?>

<div class="row g-3 gs-page-layout">
    <div class="col-xl-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" action="<?= e(url('/suscripciones')) ?>" class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-7">
                        <label class="form-label" for="buscar-suscripciones">Buscar suscripciones</label>
                        <input id="buscar-suscripciones"
                            type="text"
                            class="form-control form-control-lg"
                            name="q"
                            placeholder="Busca por contacto, usuario, teléfono, plataforma o plan"
                            value="<?= e($search ?? '') ?>"
                        >
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="filtro-estado">Estado</label>
                        <select class="form-select form-select-lg" id="filtro-estado" name="estado">
                            <option value="">Todos</option>
                            <?php foreach ($estados as $status): ?>
                                <option value="<?= e(status_label((string) $status)) ?>" <?= ($estado ?? '') === $status ? 'selected' : '' ?>><?= e(status_label((string) $status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button class="btn btn-primary btn-lg" type="submit"><?= icon('search') ?><span class="button-label">Buscar</span></button>
                    </div>
                </form>
                <?php if (($search ?? '') !== '' || ($estado ?? '') !== ''): ?><p class="small text-secondary">Filtros activos. <a href="<?= e(url('/suscripciones')) ?>">Limpiar filtros</a></p><?php endif; ?>

                <div class="table-responsive">
                    <table data-responsive-cards data-mobile-primary="0,1,5,8,9,10" class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>Plan</th>
                                <th>Notas</th>
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
                                <tr><td colspan="11" class="text-center text-secondary py-4"><?= ($search ?? '') !== '' || ($estado ?? '') !== '' ? 'No encontramos suscripciones con estos filtros.' : 'Todavía no hay suscripciones. Usa «Nueva suscripción» para comenzar.' ?></td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <?php
                                $isNoRenew = (int) ($item['flag_no_renovo'] ?? 0) === 1;
                                $statusLabel = $isNoRenew ? 'VENCIDO' : (string) ($item['estado'] ?? '');
                                ?>
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
                                    <td>
                                        <?php if (!empty($item['notas'])): ?>
                                            <small><?= e(mb_strimwidth((string) $item['notas'], 0, 50, '…')) ?></small>
                                        <?php else: ?>
                                            <small class="text-secondary">—</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e(money((float) ($item['costo_final'] ?? $item['modalidad_costo'] ?? 0))) ?></td>
                                    <td><?= e(money((float) ($item['precio_final'] ?? $item['modalidad_precio'] ?? 0))) ?></td>
                                    <td class="<?= (float) ($item['ganancia_final'] ?? 0) < 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' ?>">
                                        <?= e(money((float) ($item['ganancia_final'] ?? 0))) ?>
                                    </td>
                                    <td><?= e(display_date($item['fecha_inicio'])) ?></td>
                                    <td><?= e(display_date($item['fecha_vencimiento'])) ?></td>
                                    <td>
                                        <span class="badge text-bg-secondary"><?= e(status_label((string) $statusLabel)) ?></span>
                                        <?php if ($isNoRenew): ?>
                                            <div><small class="text-danger">No renovado</small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/suscripciones/historial/' . (int) $item['id'])) ?>"><?= icon('scroll-text') ?><span class="button-label">Historial</span></a>
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/suscripciones/editar/' . (int) $item['id'])) ?>"><?= icon('square-pen') ?><span class="button-label">Editar</span></a>
                                            <form method="post" action="<?= e(url('/suscripciones/eliminar/' . (int) $item['id'])) ?>" class="gs-mobile-detail-action" data-confirm="<?= e('Eliminar la suscripción de «' . $item['cliente_nombre'] . '» en ' . $item['plataforma_nombre'] . '. La suscripción dejará de aparecer en los listados; sus movimientos históricos se conservan.') ?>" data-confirm-title="Confirmar eliminación" data-confirm-button="Eliminar suscripción" data-confirm-danger="1">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-outline-danger btn-sm" type="submit"><?= icon('trash') ?><span class="button-label">Eliminar</span></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (($totalPages ?? 1) > 1): ?>
                <?php $pqs = http_build_query(array_filter(['q' => $search ?? '', 'estado' => $estado ?? ''])); ?>
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                    <small class="text-secondary"><?= e((string) ($totalRows ?? 0)) ?> suscripciones en total</small>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(url('/suscripciones?' . $pqs . '&page=' . (($page ?? 1) - 1))) ?>" aria-label="Página anterior">‹</a>
                        </li>
                        <li class="page-item disabled"><span class="page-link"><?= e((string) ($page ?? 1)) ?> / <?= e((string) ($totalPages ?? 1)) ?></span></li>
                        <li class="page-item <?= ($page ?? 1) >= ($totalPages ?? 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(url('/suscripciones?' . $pqs . '&page=' . (($page ?? 1) + 1))) ?>" aria-label="Página siguiente">›</a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4 gs-create-column">
        <details class="card gs-form-panel shadow-sm" id="nueva-vigencia" data-form-panel <?= ($_GET['nuevo'] ?? '') === '1' || ($_SESSION['_old_action'] ?? '') === '/suscripciones' || (int) ($selectedClientId ?? 0) > 0 ? 'open' : '' ?>>
            <summary>Nueva suscripción</summary>
            <div class="card-body">
                <form method="post" action="<?= e(url('/suscripciones')) ?>" id="create-subscription-form">
                    <?= csrf_field() ?>
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
                                    data-tipo="<?= e(service_type_label((string) $plataforma['tipo_servicio'])) ?>"
                                    data-dato-renovacion="<?= e((string) Plataforma::normalizeDatoRenovacion((string) ($plataforma['dato_renovacion'] ?? ''), (string) ($plataforma['tipo_servicio'] ?? ''))) ?>"
                                    <?= $oldPlat === (string) $plataforma['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $plataforma['nombre']) ?> (<?= e(service_type_label((string) $plataforma['tipo_servicio'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="modalidad_id">Plan de suscripción</label>
                        <select class="form-select js-modalidad" id="modalidad_id" name="modalidad_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldMod = old('modalidad_id'); ?>
                            <?php foreach ($tiposSuscripcion as $modalidad): ?>
                                <option
                                    value="<?= e((string) $modalidad['id']) ?>"
                                    data-plataforma-id="<?= e((string) $modalidad['plataforma_id']) ?>"
                                    data-costo="<?= e(format_decimal_amount($modalidad['costo'] ?? 0)) ?>"
                                    data-precio="<?= e(format_decimal_amount($modalidad['precio'] ?? 0)) ?>"
                                    <?= $oldMod === (string) $modalidad['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $modalidad['plataforma_nombre']) ?>
                                    -
                                    <?= e((string) $modalidad['nombre_modalidad']) ?>
                                    (<?= e(Modalidad::tipoCuentaLabel((string) ($modalidad['tipo_cuenta'] ?? 'CUENTA_COMPLETA'), isset($modalidad['dispositivos']) ? (int) $modalidad['dispositivos'] : null)) ?>,
                                    <?= e(duration_label(max(1, (int) ($modalidad['duracion_meses'] ?? 1)))) ?>,
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
                            step="0.01"
                            min="0.01"
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
                            step="0.01"
                            min="0.01"
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
                                value="<?= e(old('fecha_vencimiento', shift_months_clamped(new DateTimeImmutable('today'), 1)->format('Y-m-d'))) ?>"
                                required
                            >
                        </div>
                    </div>
                    <small class="text-secondary d-block mt-1">Define manualmente la vigencia de la cuenta para este cliente.</small>

                    <div class="mt-3 mb-3 js-usuario-wrap">
                        <label class="form-label js-usuario-label" for="usuario_proveedor">Dato de la cuenta para renovar</label>
                        <input
                            type="text"
                            class="form-control js-usuario-input"
                            id="usuario_proveedor"
                            name="usuario_proveedor"
                            value="<?= e(old('usuario_proveedor')) ?>"
                            placeholder="Ej: usuario123"
                        >
                        <small class="text-secondary js-usuario-help">Se pedirá según la configuración de la plataforma.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password_cuenta">Contraseña de la cuenta (opcional)</label>
                        <input type="text" class="form-control" id="password_cuenta" name="password_cuenta" value="<?= e(old('password_cuenta')) ?>" placeholder="Contraseña del proveedor" autocomplete="off">
                        <small class="text-secondary">Se guarda cifrada. Sirve para tener a mano el acceso de la cuenta.</small>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="flag_no_renovo" name="flag_no_renovo" <?= old('flag_no_renovo') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="flag_no_renovo">
                            Marcar como no renovado
                        </label>
                    </div>

                    <button type="submit" class="btn btn-success w-100 btn-lg"><?= icon('save') ?><span class="button-label">Guardar suscripción</span></button>
                </form>
            </div>
        </details>
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
                usuarioHelp.textContent = 'Ingresa el correo exacto de la cuenta que se usará para renovar.';
            } else {
                usuarioLabel.textContent = 'Usuario de la cuenta para renovar';
                usuarioInput.type = 'text';
                usuarioInput.placeholder = 'Ej: usuario123';
                usuarioHelp.textContent = 'Ingresa el usuario exacto de la cuenta que se usará para renovar.';
            }
        }

        const selectedModalidad = modalidadSelect.options[modalidadSelect.selectedIndex];
        if (selectedModalidad && selectedModalidad.dataset.costo) {
            costoBaseInput.value = selectedModalidad.dataset.costo;
        }
        if (selectedModalidad && selectedModalidad.dataset.precio) {
            precioVentaInput.value = selectedModalidad.dataset.precio;
        }
        if (!selectedModalidad || !selectedModalidad.value) {
            costoBaseInput.value = '';
            precioVentaInput.value = '';
        }
        applyGanancia();
    };

    const applyGanancia = () => {
        const costo = Number.parseFloat(costoBaseInput.value || '0');
        const precio = Number.parseFloat(precioVentaInput.value || '0');
        const ganancia = (Number.isNaN(precio) ? 0 : precio) - (Number.isNaN(costo) ? 0 : costo);
        const sign = ganancia < 0 ? '-' : '';
        const abs = Math.abs(ganancia).toLocaleString('es-BO', {
            minimumFractionDigits: Number.isInteger(Math.abs(ganancia)) ? 0 : 2,
            maximumFractionDigits: 2,
        });
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

