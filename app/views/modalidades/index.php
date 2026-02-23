<?php
declare(strict_types=1);

use App\Models\Modalidad;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Tipos de suscripcion</h1>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al dashboard</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" action="<?= e(url('/tipos-suscripcion')) ?>" class="row g-2 mb-3">
                    <div class="col-12 col-md-9">
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            name="q"
                            placeholder="Buscar por tipo, cuenta o plataforma"
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
                                <th>Plataforma</th>
                                <th>Tipo suscripcion</th>
                                <th>Cuenta</th>
                                <th>Duracion</th>
                                <th>Dispositivos</th>
                                <th>Precio</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="7" class="text-center text-secondary py-4">Sin registros.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <tr>
                                    <td><?= e((string) $item['plataforma_nombre']) ?></td>
                                    <td class="fw-semibold"><?= e((string) $item['nombre_modalidad']) ?></td>
                                    <td><?= e(Modalidad::tipoCuentaLabel((string) ($item['tipo_cuenta'] ?? 'CUENTA_COMPLETA'), isset($item['dispositivos']) ? (int) $item['dispositivos'] : null)) ?></td>
                                    <td><?= e((string) max(1, (int) ($item['duracion_meses'] ?? 1))) ?> mes(es)</td>
                                    <td><?= e((string) ($item['dispositivos'] ?? '-')) ?></td>
                                    <td>$<?= e(number_format((float) $item['precio'], 2, '.', ',')) ?></td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-end">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/tipos-suscripcion/editar/' . (int) $item['id'])) ?>">Editar</a>
                                            <form method="post" action="<?= e(url('/tipos-suscripcion/eliminar/' . (int) $item['id'])) ?>" onsubmit="return confirm('Eliminar tipo de suscripcion?')">
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
                    <div class="mb-3">
                        <label class="form-label" for="plataforma_id">Plataforma</label>
                        <select class="form-select" id="plataforma_id" name="plataforma_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldPid = old('plataforma_id'); ?>
                            <?php foreach ($platforms as $platform): ?>
                                <option value="<?= e((string) $platform['id']) ?>" <?= $oldPid === (string) $platform['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $platform['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nombre_modalidad">Nombre tipo suscripcion</label>
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
                        <input type="number" min="1" class="form-control" id="duracion_meses" name="duracion_meses" value="<?= e(old('duracion_meses', '1')) ?>" required>
                    </div>
                    <div class="mb-3 js-dispositivos-wrap">
                        <label class="form-label" for="dispositivos">Cantidad de dispositivos (si aplica)</label>
                        <input type="number" min="1" class="form-control" id="dispositivos" name="dispositivos" value="<?= e(old('dispositivos')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="precio">Precio</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="precio" name="precio" value="<?= e(old('precio')) ?>" required>
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
