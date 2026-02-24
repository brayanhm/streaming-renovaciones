<?php
declare(strict_types=1);

use App\Models\Modalidad;

$returnPlatformId = (int) ($returnPlatformId ?? 0);
$returnQuery = $returnPlatformId > 0
    ? '?' . http_build_query(['plataforma_id' => $returnPlatformId])
    : '';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Editar tipo de suscripcion</h1>
    <a href="<?= e(url('/tipos-suscripcion' . $returnQuery)) ?>" class="btn btn-outline-secondary">Volver a tipos</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(url('/tipos-suscripcion/actualizar/' . (int) $item['id'])) ?>" id="edit-plan-form">
            <?php if ($returnPlatformId > 0): ?>
                <input type="hidden" name="return_plataforma_id" value="<?= e((string) $returnPlatformId) ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="plataforma_id">Plataforma</label>
                    <select class="form-select js-plataforma" id="plataforma_id" name="plataforma_id" required>
                        <?php foreach ($platforms as $platform): ?>
                            <option
                                value="<?= e((string) $platform['id']) ?>"
                                data-duraciones="<?= e((string) ($platform['duraciones_disponibles'] ?? '')) ?>"
                                <?= (int) $item['plataforma_id'] === (int) $platform['id'] ? 'selected' : '' ?>
                            >
                                <?= e((string) $platform['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="nombre_modalidad">Nombre del tipo de suscripcion</label>
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
                        class="form-control js-duracion"
                        id="duracion_meses"
                        name="duracion_meses"
                        value="<?= e((string) max(1, (int) ($item['duracion_meses'] ?? 1))) ?>"
                        list="duraciones-list"
                        required
                    >
                    <datalist id="duraciones-list"></datalist>
                    <small class="text-secondary js-duracion-help"></small>
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
                    <label class="form-label" for="precio">Precio (Bs)</label>
                    <input
                        type="number"
                        step="1"
                        min="1"
                        class="form-control"
                        id="precio"
                        name="precio"
                        value="<?= e((string) ((int) round((float) ($item['precio'] ?? 0)))) ?>"
                        required
                    >
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-lg w-100 w-sm-auto" type="submit">Guardar tipo</button>
                    <a href="<?= e(url('/tipos-suscripcion' . $returnQuery)) ?>" class="btn btn-outline-secondary btn-lg w-100 w-sm-auto">Cancelar</a>
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
            duracionHelp.textContent = 'Duraciones configuradas: ' + values.join(', ') + ' meses.';
        } else {
            duracionHelp.textContent = 'Sin duraciones fijas para esta plataforma.';
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
