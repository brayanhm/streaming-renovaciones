<?php
declare(strict_types=1);

use App\Models\Modalidad;

$returnPlatformId = (int) ($returnPlatformId ?? 0);
$returnQuery = $returnPlatformId > 0
    ? '?' . http_build_query(['plataforma_id' => $returnPlatformId])
    : '';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Editar plan de suscripción</h1>
    <a href="<?= e(url('/tipos-suscripcion' . $returnQuery)) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver a tipos</span></a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(url('/tipos-suscripcion/actualizar/' . (int) $item['id'])) ?>" id="edit-plan-form" data-form-behavior="plan">
            <?= csrf_field() ?>
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
                    <label class="form-label" for="nombre_modalidad">Nombre del plan de suscripción</label>
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
                    <label class="form-label" for="duracion_meses">Duración (meses)</label>
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
                    <label class="form-label" for="costo">Costo (Bs)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control js-costo"
                        id="costo"
                        name="costo"
                        value="<?= e(format_decimal_amount($item['costo'] ?? 0)) ?>"
                        required
                    >
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="precio">Precio de venta (Bs)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control js-precio"
                        id="precio"
                        name="precio"
                        value="<?= e(format_decimal_amount($item['precio'] ?? 0)) ?>"
                        required
                    >
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ganancia por plan</label>
                    <div class="form-control bg-light">
                        <strong class="js-ganancia">
                            <?= e(display_money((float) ((float) ($item['precio'] ?? 0) - (float) ($item['costo'] ?? 0)))) ?>
                        </strong>
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-lg w-100 w-sm-auto" type="submit"><?= icon('save') ?><span class="button-label">Guardar plan</span></button>
                    <a href="<?= e(url('/tipos-suscripcion' . $returnQuery)) ?>" class="btn btn-outline-secondary btn-lg w-100 w-sm-auto"><?= icon('arrow-left') ?><span class="button-label">Cancelar</span></a>
                </div>
            </div>
        </form>
    </div>
</div>
