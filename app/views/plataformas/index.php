<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Plataformas</h1>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al dashboard</a>
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
                            placeholder="Buscar por nombre o tipo"
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
                                <th>Tipo</th>
                                <th>Mensajes</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="4" class="text-center text-secondary py-4">Sin registros.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <?php
                                $m2 = (string) ($item['mensaje_menos_2'] ?? '');
                                $m1 = (string) ($item['mensaje_menos_1'] ?? '');
                                if (strlen($m2) > 40) {
                                    $m2 = substr($m2, 0, 37) . '...';
                                }
                                if (strlen($m1) > 40) {
                                    $m1 = substr($m1, 0, 37) . '...';
                                }
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $item['nombre']) ?></td>
                                    <td><span class="badge text-bg-dark"><?= e((string) $item['tipo_servicio']) ?></span></td>
                                    <td>
                                        <small class="d-block text-secondary">-2: <?= e($m2) ?></small>
                                        <small class="d-block text-secondary">-1: <?= e($m1) ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-end">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/plataformas/editar/' . (int) $item['id'])) ?>">Editar</a>
                                            <form method="post" action="<?= e(url('/plataformas/eliminar/' . (int) $item['id'])) ?>" onsubmit="return confirm('Eliminar plataforma?')">
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
                <form method="post" action="<?= e(url('/plataformas')) ?>">
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
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_menos_2">Plantilla -2 dias</label>
                        <textarea class="form-control" id="mensaje_menos_2" name="mensaje_menos_2" rows="3"><?= e(old('mensaje_menos_2')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_menos_1">Plantilla -1 dia</label>
                        <textarea class="form-control" id="mensaje_menos_1" name="mensaje_menos_1" rows="3"><?= e(old('mensaje_menos_1')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_rec_7">Plantilla recuperacion 7 dias</label>
                        <textarea class="form-control" id="mensaje_rec_7" name="mensaje_rec_7" rows="2"><?= e(old('mensaje_rec_7')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_rec_15">Plantilla recuperacion 15 dias</label>
                        <textarea class="form-control" id="mensaje_rec_15" name="mensaje_rec_15" rows="2"><?= e(old('mensaje_rec_15')) ?></textarea>
                    </div>
                    <div class="alert alert-light border small">
                        Variables: <code>{NOMBRE}</code>, <code>{PLATAFORMA}</code>, <code>{PLAN}</code>, <code>{FECHA_VENCE}</code>, <code>{PRECIO}</code>.
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg">Guardar plataforma</button>
                </form>
            </div>
        </div>
    </div>
</div>
