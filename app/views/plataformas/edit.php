<?php
declare(strict_types=1);

use App\Models\Plataforma;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Editar plataforma</h1>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(url('/tipos-suscripcion?plataforma_id=' . (int) $item['id'])) ?>" class="btn btn-outline-primary">Ver tipos</a>
        <a href="<?= e(url('/plataformas')) ?>" class="btn btn-outline-secondary">Volver a plataformas</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(url('/plataformas/actualizar/' . (int) $item['id'])) ?>" id="edit-platform-form">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= e((string) $item['nombre']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tipo_servicio">Tipo de servicio</label>
                    <select class="form-select" id="tipo_servicio" name="tipo_servicio" required>
                        <option value="RENOVABLE" <?= (string) $item['tipo_servicio'] === 'RENOVABLE' ? 'selected' : '' ?>>RENOVABLE</option>
                        <option value="DESECHABLE" <?= (string) $item['tipo_servicio'] === 'DESECHABLE' ? 'selected' : '' ?>>DESECHABLE</option>
                    </select>
                </div>
                <div class="col-md-6 js-dato-renovacion-wrap">
                    <label class="form-label" for="dato_renovacion">Dato requerido para renovar</label>
                    <?php $datoRenovacion = Plataforma::normalizeDatoRenovacion((string) ($item['dato_renovacion'] ?? ''), (string) ($item['tipo_servicio'] ?? '')); ?>
                    <select class="form-select js-dato-renovacion" id="dato_renovacion" name="dato_renovacion">
                        <option value="USUARIO" <?= $datoRenovacion === 'USUARIO' ? 'selected' : '' ?>>USUARIO</option>
                        <option value="CORREO" <?= $datoRenovacion === 'CORREO' ? 'selected' : '' ?>>CORREO</option>
                        <option value="NO_APLICA" <?= $datoRenovacion === 'NO_APLICA' ? 'selected' : '' ?>>NO_APLICA</option>
                    </select>
                    <small class="text-secondary js-dato-renovacion-help">Define si la renovacion se realizara por usuario o por correo.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="duraciones_disponibles">Duraciones disponibles (meses)</label>
                    <input
                        type="text"
                        class="form-control"
                        id="duraciones_disponibles"
                        name="duraciones_disponibles"
                        value="<?= e((string) ($item['duraciones_disponibles'] ?? '')) ?>"
                        placeholder="Ej: 1,3,7"
                    >
                    <small class="text-secondary">Opcional. Si lo defines, solo se permitiran estas duraciones en los tipos de esta plataforma.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mensaje_menos_2">Mensaje recordatorio (-2 dias)</label>
                    <textarea class="form-control" id="mensaje_menos_2" name="mensaje_menos_2" rows="4"><?= e((string) ($item['mensaje_menos_2'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mensaje_menos_1">Mensaje recordatorio (-1 dia)</label>
                    <textarea class="form-control" id="mensaje_menos_1" name="mensaje_menos_1" rows="4"><?= e((string) ($item['mensaje_menos_1'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mensaje_rec_7">Mensaje de recuperacion (7+ dias vencido)</label>
                    <textarea class="form-control" id="mensaje_rec_7" name="mensaje_rec_7" rows="3"><?= e((string) ($item['mensaje_rec_7'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mensaje_rec_15">Mensaje de recuperacion (15+ dias vencido)</label>
                    <textarea class="form-control" id="mensaje_rec_15" name="mensaje_rec_15" rows="3"><?= e((string) ($item['mensaje_rec_15'] ?? '')) ?></textarea>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-lg w-100 w-sm-auto" type="submit">Guardar cambios</button>
                    <a href="<?= e(url('/plataformas')) ?>" class="btn btn-outline-secondary btn-lg w-100 w-sm-auto">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('edit-platform-form');
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
