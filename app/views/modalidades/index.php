<?php
declare(strict_types=1);

use App\Models\Modalidad;

$selectedPlatformId = (int) ($selectedPlatformId ?? 0);
$createPlatformValue = old('plataforma_id', $selectedPlatformId > 0 ? (string) $selectedPlatformId : '');
$returnQuery = $selectedPlatformId > 0
    ? '?' . http_build_query(['plataforma_id' => $selectedPlatformId])
    : '';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Tipos de suscripcion</h1>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<?php if (!empty($selectedPlatform)): ?>
    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>
            Mostrando tipos solo para la plataforma: <strong><?= e((string) $selectedPlatform['nombre']) ?></strong>
        </span>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/tipos-suscripcion')) ?>">Ver todas las plataformas</a>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" action="<?= e(url('/tipos-suscripcion')) ?>" class="row g-2 mb-3">
                    <div class="col-12 col-md-5">
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            name="q"
                            placeholder="Busca por tipo, cuenta o plataforma"
                            value="<?= e($search ?? '') ?>"
                        >
                    </div>
                    <div class="col-12 col-md-4">
                        <select class="form-select form-select-lg" name="plataforma_id">
                            <option value="">Todas las plataformas</option>
                            <?php foreach ($platforms as $platform): ?>
                                <option
                                    value="<?= e((string) $platform['id']) ?>"
                                    <?= $selectedPlatformId === (int) $platform['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $platform['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-grid">
                        <button class="btn btn-primary btn-lg" type="submit">Buscar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Plataforma</th>
                                <th>Tipo de suscripcion</th>
                                <th>Tipo de cuenta</th>
                                <th>Duracion</th>
                                <th>Dispositivos</th>
                                <th>Precio (Bs)</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="7" class="text-center text-secondary py-4">No hay tipos de suscripcion registrados.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <tr>
                                    <td><?= e((string) $item['plataforma_nombre']) ?></td>
                                    <td class="fw-semibold"><?= e((string) $item['nombre_modalidad']) ?></td>
                                    <td><?= e(Modalidad::tipoCuentaLabel((string) ($item['tipo_cuenta'] ?? 'CUENTA_COMPLETA'), isset($item['dispositivos']) ? (int) $item['dispositivos'] : null)) ?></td>
                                    <td><?= e((string) max(1, (int) ($item['duracion_meses'] ?? 1))) ?> mes(es)</td>
                                    <td><?= e((string) ($item['dispositivos'] ?? '-')) ?></td>
                                    <td><?= e(money((float) $item['precio'])) ?></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/tipos-suscripcion/editar/' . (int) $item['id'] . $returnQuery)) ?>">Editar</a>
                                            <form method="post" action="<?= e(url('/tipos-suscripcion/eliminar/' . (int) $item['id'])) ?>" onsubmit="return confirm('Eliminar este tipo de suscripcion?')">
                                                <?php if ($selectedPlatformId > 0): ?>
                                                    <input type="hidden" name="return_plataforma_id" value="<?= e((string) $selectedPlatformId) ?>">
                                                <?php endif; ?>
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
                <h2 class="h5 mb-3">Nuevo tipo de suscripcion</h2>
                <form method="post" action="<?= e(url('/tipos-suscripcion')) ?>" id="create-plan-form">
                    <?php if ($selectedPlatformId > 0): ?>
                        <input type="hidden" name="return_plataforma_id" value="<?= e((string) $selectedPlatformId) ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="plataforma_id">Plataforma</label>
                        <select class="form-select js-plataforma" id="plataforma_id" name="plataforma_id" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($platforms as $platform): ?>
                                <option
                                    value="<?= e((string) $platform['id']) ?>"
                                    data-duraciones="<?= e((string) ($platform['duraciones_disponibles'] ?? '')) ?>"
                                    <?= $createPlatformValue === (string) $platform['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $platform['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nombre_modalidad">Nombre del tipo de suscripcion</label>
                        <input type="text" class="form-control" id="nombre_modalidad" name="nombre_modalidad" value="<?= e(old('nombre_modalidad')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="tipo_cuenta">Tipo de cuenta</label>
                        <?php $oldTipoCuenta = old('tipo_cuenta', 'CUENTA_COMPLETA'); ?>
                        <select class="form-select js-tipo-cuenta" id="tipo_cuenta" name="tipo_cuenta" required>
                            <?php foreach ($tiposCuenta as $tipoCuenta): ?>
                                <option value="<?= e($tipoCuenta) ?>" <?= $oldTipoCuenta === $tipoCuenta ? 'selected' : '' ?>>
                                    <?= e($tipoCuenta) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="duracion_meses">Duracion del plan (meses)</label>
                        <input
                            type="number"
                            min="1"
                            class="form-control js-duracion"
                            id="duracion_meses"
                            name="duracion_meses"
                            value="<?= e(old('duracion_meses', '1')) ?>"
                            list="duraciones-list"
                            required
                        >
                        <datalist id="duraciones-list"></datalist>
                        <small class="text-secondary js-duracion-help">Ejemplo: 1, 3, 7 meses.</small>
                    </div>
                    <div class="mb-3 js-dispositivos-wrap">
                        <label class="form-label" for="dispositivos">Cantidad de dispositivos (si aplica)</label>
                        <input type="number" min="1" class="form-control" id="dispositivos" name="dispositivos" value="<?= e(old('dispositivos')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="precio">Precio (Bs)</label>
                        <input type="number" step="1" min="1" class="form-control" id="precio" name="precio" value="<?= e(old('precio')) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg">Guardar tipo</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('create-plan-form');
    if (!form) return;

    const tipoCuenta = form.querySelector('.js-tipo-cuenta');
    const plataforma = form.querySelector('.js-plataforma');
    const duracion = form.querySelector('.js-duracion');
    const duracionesList = form.querySelector('#duraciones-list');
    const duracionHelp = form.querySelector('.js-duracion-help');
    const dispositivosWrap = form.querySelector('.js-dispositivos-wrap');
    const dispositivos = form.querySelector('#dispositivos');

    const parseDuraciones = (csv) => {
        if (!csv) return [];
        const values = [];
        for (const raw of csv.split(',')) {
            const parsed = Number.parseInt(raw.trim(), 10);
            if (Number.isNaN(parsed) || parsed <= 0 || values.includes(parsed)) {
                continue;
            }
            values.push(parsed);
        }
        values.sort((a, b) => a - b);
        return values;
    };

    const applyDuraciones = () => {
        const selected = plataforma.options[plataforma.selectedIndex];
        const values = parseDuraciones(selected ? selected.dataset.duraciones : '');

        duracionesList.innerHTML = '';
        for (const value of values) {
            const option = document.createElement('option');
            option.value = String(value);
            duracionesList.appendChild(option);
        }

        if (values.length > 0) {
            duracionHelp.textContent = 'Duraciones configuradas para esta plataforma: ' + values.join(', ') + ' meses.';
        } else {
            duracionHelp.textContent = 'Esta plataforma no tiene duraciones fijas. Puedes indicar cualquier mes positivo.';
        }
    };

    const apply = () => {
        const value = tipoCuenta.value;
        const needs = value === 'POR_DISPOSITIVOS' || value === 'AMBOS';
        dispositivosWrap.classList.toggle('d-none', !needs);
        dispositivos.required = value === 'POR_DISPOSITIVOS';
        if (!needs) {
            dispositivos.value = '';
        }
    };

    plataforma.addEventListener('change', applyDuraciones);
    tipoCuenta.addEventListener('change', apply);
    applyDuraciones();
    apply();
})();
</script>
