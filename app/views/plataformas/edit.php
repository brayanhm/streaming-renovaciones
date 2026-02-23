<?php
declare(strict_types=1);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Editar plataforma</h1>
    <a href="<?= e(url('/plataformas')) ?>" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(url('/plataformas/actualizar/' . (int) $item['id'])) ?>">
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
                <div class="col-md-6">
                    <label class="form-label" for="mensaje_menos_2">Plantilla -2 dias</label>
                    <textarea class="form-control" id="mensaje_menos_2" name="mensaje_menos_2" rows="4"><?= e((string) ($item['mensaje_menos_2'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mensaje_menos_1">Plantilla -1 dia</label>
                    <textarea class="form-control" id="mensaje_menos_1" name="mensaje_menos_1" rows="4"><?= e((string) ($item['mensaje_menos_1'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mensaje_rec_7">Plantilla recuperacion 7 dias</label>
                    <textarea class="form-control" id="mensaje_rec_7" name="mensaje_rec_7" rows="3"><?= e((string) ($item['mensaje_rec_7'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mensaje_rec_15">Plantilla recuperacion 15 dias</label>
                    <textarea class="form-control" id="mensaje_rec_15" name="mensaje_rec_15" rows="3"><?= e((string) ($item['mensaje_rec_15'] ?? '')) ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary btn-lg" type="submit">Guardar cambios</button>
                    <a href="<?= e(url('/plataformas')) ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>
