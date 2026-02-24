<?php
declare(strict_types=1);

use App\Models\Modalidad;

$hasSubscriptionCatalog = !empty($plataformas ?? []) && !empty($tiposSuscripcion ?? []);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Clientes</h1>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al panel</a>
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
                            placeholder="Busca por nombre o telefono"
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
                                <th>Nombre</th>
                                <th>Telefono</th>
                                <th>Notas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="4" class="text-center text-secondary py-4">No hay clientes registrados.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $item['nombre']) ?></td>
                                    <td><?= e((string) $item['telefono']) ?></td>
                                    <td><?= e((string) ($item['notas'] ?? '')) ?></td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-end">
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
                            Antes de crear clientes, registra al menos una plataforma y un tipo de suscripcion.
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= e(old('nombre')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="telefono">Telefono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?= e(old('telefono')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notas">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"><?= e(old('notas')) ?></textarea>
                    </div>
                    <hr>
                    <h3 class="h6 mb-3">Suscripcion inicial del cliente</h3>
                    <div class="mb-3">
                        <label class="form-label" for="plataforma_id">Plataforma</label>
                        <select class="form-select js-plataforma" id="plataforma_id" name="plataforma_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldPlat = old('plataforma_id'); ?>
                            <?php foreach (($plataformas ?? []) as $plataforma): ?>
                                <option
                                    value="<?= e((string) $plataforma['id']) ?>"
                                    <?= $oldPlat === (string) $plataforma['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $plataforma['nombre']) ?> (<?= e((string) $plataforma['tipo_servicio']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="modalidad_id">Plan y duracion</label>
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
                        <small class="text-secondary">La suscripcion inicia hoy y el vencimiento se calcula segun la duracion elegida.</small>
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg" <?= !$hasSubscriptionCatalog ? 'disabled' : '' ?>>Guardar cliente</button>
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

    const applyFilters = () => {
        const plataformaId = plataformaSelect.value;
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
    };

    plataformaSelect.addEventListener('change', applyFilters);
    applyFilters();
})();
</script>
