<?php
declare(strict_types=1);

use App\Models\Modalidad;
use App\Models\Plataforma;

$hasSubscriptionCatalog = !empty($plataformas ?? []) && !empty($tiposSuscripcion ?? []);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Clientes</h1>
    <div class="d-flex gap-2">
        <?php if ((int) ($missingContactCount ?? 0) > 0): ?>
            <a href="<?= e(url('/clientes/completar')) ?>" class="btn btn-warning">
                Completar faltantes (<?= (int) $missingContactCount ?>)
            </a>
        <?php endif; ?>
        <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al panel</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" action="<?= e(url('/clientes')) ?>" class="row g-2 mb-3">
                    <div class="col-12 col-md-9">
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            name="q"
                            placeholder="Busca por contacto, usuario o número"
                            value="<?= e($search ?? '') ?>"
                        >
                    </div>
                    <div class="col-12 col-md-3 d-grid">
                        <button class="btn btn-primary btn-lg" type="submit">Buscar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Contacto</th>
                                <th>Número</th>
                                <th>Usuario</th>
                                <th>Notas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-4">No hay clientes registrados.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $item['nombre']) ?></td>
                                    <td><?= e((string) $item['telefono']) ?></td>
                                    <td><?= e((string) ($item['usuario_proveedor'] ?? '')) ?></td>
                                    <td><?= e((string) ($item['notas'] ?? '')) ?></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/suscripciones?cliente_id=' . (int) $item['id'] . '#nueva-vigencia')) ?>">Asignar vigencia</a>
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/clientes/editar/' . (int) $item['id'])) ?>">Editar</a>
                                            <form method="post" action="<?= e(url('/clientes/eliminar/' . (int) $item['id'])) ?>" onsubmit="return confirm('Eliminar este cliente?')">
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

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Nuevo cliente</h2>
                <form method="post" action="<?= e(url('/clientes')) ?>" id="create-client-form">
                    <?php if (!$hasSubscriptionCatalog): ?>
                        <div class="alert alert-warning">
                            Antes de crear clientes, registra al menos una plataforma y un tipo de suscripción.
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="contacto">Contacto</label>
                        <input
                            type="text"
                            class="form-control"
                            id="contacto"
                            name="contacto"
                            value="<?= e(old('contacto', old('nombre'))) ?>"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="numero">Número</label>
                        <input
                            type="text"
                            class="form-control"
                            id="numero"
                            name="numero"
                            value="<?= e(old('numero', old('telefono'))) ?>"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notas">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"><?= e(old('notas')) ?></textarea>
                    </div>
                    <hr>
                    <h3 class="h6 mb-3">Suscripción inicial del cliente</h3>
                    <div class="mb-3">
                        <label class="form-label" for="plataforma_id">Plataforma</label>
                        <select class="form-select js-plataforma" id="plataforma_id" name="plataforma_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldPlat = old('plataforma_id'); ?>
                            <?php foreach (($plataformas ?? []) as $plataforma): ?>
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
                        <label class="form-label" for="modalidad_id">Plan y duración</label>
                        <select class="form-select js-modalidad" id="modalidad_id" name="modalidad_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldMod = old('modalidad_id'); ?>
                            <?php foreach (($tiposSuscripcion ?? []) as $modalidad): ?>
                                <option
                                    value="<?= e((string) $modalidad['id']) ?>"
                                    data-plataforma-id="<?= e((string) $modalidad['plataforma_id']) ?>"
                                    <?= $oldMod === (string) $modalidad['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $modalidad['nombre_modalidad']) ?> -
                                    <?= e(Modalidad::tipoCuentaLabel((string) ($modalidad['tipo_cuenta'] ?? 'CUENTA_COMPLETA'), isset($modalidad['dispositivos']) ? (int) $modalidad['dispositivos'] : null)) ?> -
                                    <?= e((string) max(1, (int) ($modalidad['duracion_meses'] ?? 1))) ?> mes(es)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-secondary">La suscripción inicia hoy y el vencimiento se calcula según la duración elegida.</small>
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
                        <small class="text-secondary js-usuario-help">Se pedirá según la configuración de la plataforma.</small>
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg" <?= !$hasSubscriptionCatalog ? 'disabled' : '' ?>>Guardar cliente</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h2 class="h5 mb-3">Agregar cliente antiguo</h2>
                <form method="post" action="<?= e(url('/clientes/antiguo')) ?>">
                    <?php if (!$hasSubscriptionCatalog): ?>
                        <div class="alert alert-warning">
                            Debes tener plataformas y planes creados para registrar clientes antiguos.
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="contacto_antiguo">Contacto</label>
                        <input
                            type="text"
                            class="form-control"
                            id="contacto_antiguo"
                            name="contacto_antiguo"
                            value="<?= e(old('contacto_antiguo')) ?>"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="numero_antiguo">Numero</label>
                        <input
                            type="text"
                            class="form-control"
                            id="numero_antiguo"
                            name="numero_antiguo"
                            value="<?= e(old('numero_antiguo')) ?>"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="plataforma_id_antiguo">Plataforma</label>
                        <select class="form-select" id="plataforma_id_antiguo" name="plataforma_id_antiguo" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldPlatAntiguo = old('plataforma_id_antiguo'); ?>
                            <?php foreach (($plataformas ?? []) as $plataforma): ?>
                                <option
                                    value="<?= e((string) $plataforma['id']) ?>"
                                    <?= $oldPlatAntiguo === (string) $plataforma['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $plataforma['nombre']) ?> (<?= e((string) $plataforma['tipo_servicio']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-secondary">Se usara automaticamente el primer plan disponible de la plataforma.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="fecha_finalizacion">Fecha de finalizacion</label>
                        <input
                            type="date"
                            class="form-control"
                            id="fecha_finalizacion"
                            name="fecha_finalizacion"
                            value="<?= e(old('fecha_finalizacion')) ?>"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notas_antiguo">Notas</label>
                        <textarea class="form-control" id="notas_antiguo" name="notas_antiguo" rows="2"><?= e(old('notas_antiguo')) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100" <?= !$hasSubscriptionCatalog ? 'disabled' : '' ?>>
                        Guardar cliente antiguo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('create-client-form');
    if (!form) return;

    const plataformaSelect = form.querySelector('.js-plataforma');
    const modalidadSelect = form.querySelector('.js-modalidad');
    const usuarioWrap = form.querySelector('.js-usuario-wrap');
    const usuarioLabel = form.querySelector('.js-usuario-label');
    const usuarioInput = form.querySelector('.js-usuario-input');
    const usuarioHelp = form.querySelector('.js-usuario-help');

    const applyFilters = () => {
        const plataformaId = plataformaSelect.value;
        const selectedPlatformOption = plataformaSelect.options[plataformaSelect.selectedIndex];
        const tipoServicio = selectedPlatformOption ? selectedPlatformOption.dataset.tipo : '';
        const datoRenovacion = selectedPlatformOption ? selectedPlatformOption.dataset.datoRenovacion : 'NO_APLICA';
        let hasVisible = false;

        for (const option of modalidadSelect.options) {
            if (option.value === '') continue;
            const visible = plataformaId !== '' && option.dataset.plataformaId === plataformaId;
            option.hidden = !visible;
            option.disabled = !visible;
            hasVisible = hasVisible || visible;
        }

        if (modalidadSelect.selectedOptions.length > 0) {
            const selected = modalidadSelect.selectedOptions[0];
            if (selected.value !== '' && selected.dataset.plataformaId !== plataformaId) {
                modalidadSelect.value = '';
            }
        }

        if (!hasVisible) {
            modalidadSelect.value = '';
        }

        if (tipoServicio === 'DESECHABLE' || plataformaId === '') {
            usuarioWrap.classList.add('d-none');
            usuarioInput.required = false;
            usuarioInput.type = 'text';
            usuarioInput.value = '';
            return;
        }

        usuarioWrap.classList.remove('d-none');
        usuarioInput.required = true;
        if (datoRenovacion === 'CORREO') {
            usuarioLabel.textContent = 'Correo de la cuenta para renovar';
            usuarioInput.type = 'email';
            usuarioInput.placeholder = 'correo@dominio.com';
            usuarioHelp.textContent = 'Ingresa el correo de la cuenta para la primera renovación.';
        } else {
            usuarioLabel.textContent = 'Usuario de la cuenta para renovar';
            usuarioInput.type = 'text';
            usuarioInput.placeholder = 'Ej: usuario123';
            usuarioHelp.textContent = 'Ingresa el usuario de la cuenta para la primera renovación.';
        }
    };

    plataformaSelect.addEventListener('change', applyFilters);
    applyFilters();
})();
</script>

