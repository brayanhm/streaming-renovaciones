<?php
declare(strict_types=1);

use App\Models\Plataforma;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Plataformas</h1>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" action="<?= e(url('/plataformas')) ?>" class="row g-2 mb-3">
                    <div class="col-12 col-md-9">
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            name="q"
                            placeholder="Busca por nombre o tipo de servicio"
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
                                <th>Tipo de servicio</th>
                                <th>Dato renovacion</th>
                                <th>Duraciones</th>
                                <th>Plantillas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="6" class="text-center text-secondary py-4">No hay plataformas registradas.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <?php
                                $m2 = (string) ($item['mensaje_menos_2'] ?? '');
                                $m1 = (string) ($item['mensaje_menos_1'] ?? '');
                                $r3 = (string) ($item['mensaje_rec_7'] ?? '');
                                if (strlen($m2) > 40) {
                                    $m2 = substr($m2, 0, 37) . '...';
                                }
                                if (strlen($m1) > 40) {
                                    $m1 = substr($m1, 0, 37) . '...';
                                }
                                if (strlen($r3) > 40) {
                                    $r3 = substr($r3, 0, 37) . '...';
                                }
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $item['nombre']) ?></td>
                                    <td><span class="badge text-bg-dark"><?= e((string) $item['tipo_servicio']) ?></span></td>
                                    <td>
                                        <span class="badge text-bg-secondary">
                                            <?= e(Plataforma::datoRenovacionLabel((string) ($item['dato_renovacion'] ?? 'NO_APLICA'))) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-light border">
                                            <?= e((string) (($item['duraciones_disponibles'] ?? '') !== '' ? $item['duraciones_disponibles'] : 'Libre')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="d-block text-secondary">-3: <?= e($m2) ?></small>
                                        <small class="d-block text-secondary">0: <?= e($m1) ?></small>
                                        <small class="d-block text-secondary">+3: <?= e($r3) ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/tipos-suscripcion?plataforma_id=' . (int) $item['id'])) ?>">Ver tipos</a>
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/plataformas/editar/' . (int) $item['id'])) ?>">Editar</a>
                                            <form method="post" action="<?= e(url('/plataformas/eliminar/' . (int) $item['id'])) ?>" onsubmit="return confirm('Eliminar esta plataforma?')">
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
                <h2 class="h5 mb-3">Nueva plataforma</h2>
                <form method="post" action="<?= e(url('/plataformas')) ?>" id="create-platform-form">
                    <div class="mb-3">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= e(old('nombre')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="tipo_servicio">Tipo de servicio</label>
                        <select class="form-select" id="tipo_servicio" name="tipo_servicio" required>
                            <?php $oldType = old('tipo_servicio', 'RENOVABLE'); ?>
                            <option value="RENOVABLE" <?= $oldType === 'RENOVABLE' ? 'selected' : '' ?>>RENOVABLE</option>
                            <option value="DESECHABLE" <?= $oldType === 'DESECHABLE' ? 'selected' : '' ?>>DESECHABLE</option>
                        </select>
                    </div>
                    <div class="mb-3 js-dato-renovacion-wrap">
                        <label class="form-label" for="dato_renovacion">Dato requerido para renovar</label>
                        <?php $oldDato = old('dato_renovacion', 'USUARIO'); ?>
                        <select class="form-select js-dato-renovacion" id="dato_renovacion" name="dato_renovacion">
                            <option value="USUARIO" <?= $oldDato === 'USUARIO' ? 'selected' : '' ?>>USUARIO</option>
                            <option value="CORREO" <?= $oldDato === 'CORREO' ? 'selected' : '' ?>>CORREO</option>
                            <option value="NO_APLICA" <?= $oldDato === 'NO_APLICA' ? 'selected' : '' ?>>NO_APLICA</option>
                        </select>
                        <small class="text-secondary js-dato-renovacion-help">Define el dato que se pedira para renovar esta plataforma.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="duraciones_disponibles">Duraciones disponibles (meses)</label>
                        <input
                            type="text"
                            class="form-control"
                            id="duraciones_disponibles"
                            name="duraciones_disponibles"
                            value="<?= e(old('duraciones_disponibles')) ?>"
                            placeholder="Ej: 1,3,7"
                        >
                        <small class="text-secondary">Opcional. Si lo defines, solo estas duraciones estaran permitidas en los tipos de esta plataforma.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_menos_2">Mensaje renovacion (3 dias antes)</label>
                        <textarea class="form-control" id="mensaje_menos_2" name="mensaje_menos_2" rows="3"><?= e(old('mensaje_menos_2')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_menos_1">Mensaje renovacion (dia de vencimiento)</label>
                        <textarea class="form-control" id="mensaje_menos_1" name="mensaje_menos_1" rows="3"><?= e(old('mensaje_menos_1')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_rec_7">Mensaje de recuperacion (3 dias despues)</label>
                        <textarea class="form-control" id="mensaje_rec_7" name="mensaje_rec_7" rows="2"><?= e(old('mensaje_rec_7')) ?></textarea>
                    </div>
                    <div class="alert alert-light border small">
                        Variables disponibles: <code>{NOMBRE}</code>, <code>{PLATAFORMA}</code>, <code>{PLAN}</code>, <code>{FECHA_VENCE}</code>, <code>{PRECIO}</code>.
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg">Guardar plataforma</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('create-platform-form');
    if (!form) return;

    const tipoServicio = form.querySelector('#tipo_servicio');
    const datoWrap = form.querySelector('.js-dato-renovacion-wrap');
    const datoSelect = form.querySelector('.js-dato-renovacion');
    const datoHelp = form.querySelector('.js-dato-renovacion-help');

    const apply = () => {
        const tipo = tipoServicio.value;
        if (tipo === 'RENOVABLE') {
            datoWrap.classList.remove('d-none');
            if (datoSelect.value === 'NO_APLICA') {
                datoSelect.value = 'USUARIO';
            }
            datoHelp.textContent = 'Define si la renovacion se realiza usando usuario o correo.';
            return;
        }

        datoSelect.value = 'NO_APLICA';
        datoWrap.classList.add('d-none');
        datoHelp.textContent = 'No aplica para servicios desechables.';
    };

    tipoServicio.addEventListener('change', apply);
    apply();
})();
</script>
