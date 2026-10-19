<?php
declare(strict_types=1);

use App\Models\Modalidad;
use App\Models\Plataforma;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Editar suscripción</h1>
    <a href="<?= e(url('/suscripciones')) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver a suscripciones</span></a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(url('/suscripciones/actualizar/' . (int) $item['id'])) ?>" id="edit-subscription-form" data-form-behavior="subscription">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="cliente_id">Cliente</label>
                    <select class="form-select" id="cliente_id" name="cliente_id" required>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= e((string) $cliente['id']) ?>" <?= (int) $item['cliente_id'] === (int) $cliente['id'] ? 'selected' : '' ?>>
                                <?= e((string) $cliente['nombre']) ?> - <?= e((string) $cliente['telefono']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="plataforma_id">Plataforma</label>
                    <select class="form-select js-plataforma" id="plataforma_id" name="plataforma_id" required>
                        <?php foreach ($plataformas as $plataforma): ?>
                            <option
                                value="<?= e((string) $plataforma['id']) ?>"
                                data-tipo="<?= e(service_type_label((string) $plataforma['tipo_servicio'])) ?>"
                                data-dato-renovacion="<?= e((string) Plataforma::normalizeDatoRenovacion((string) ($plataforma['dato_renovacion'] ?? ''), (string) ($plataforma['tipo_servicio'] ?? ''))) ?>"
                                <?= (int) $item['plataforma_id'] === (int) $plataforma['id'] ? 'selected' : '' ?>
                            >
                                <?= e((string) $plataforma['nombre']) ?> (<?= e(service_type_label((string) $plataforma['tipo_servicio'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="modalidad_id">Plan de suscripción</label>
                    <select class="form-select js-modalidad" id="modalidad_id" name="modalidad_id" required>
                        <?php foreach ($tiposSuscripcion as $modalidad): ?>
                            <option
                                value="<?= e((string) $modalidad['id']) ?>"
                                data-plataforma-id="<?= e((string) $modalidad['plataforma_id']) ?>"
                                data-costo="<?= e(format_decimal_amount($modalidad['costo'] ?? 0)) ?>"
                                data-precio="<?= e(format_decimal_amount($modalidad['precio'] ?? 0)) ?>"
                                <?= (int) $item['modalidad_id'] === (int) $modalidad['id'] ? 'selected' : '' ?>
                            >
                                <?= e((string) $modalidad['plataforma_nombre']) ?>
                                -
                                <?= e((string) $modalidad['nombre_modalidad']) ?>
                                (<?= e(Modalidad::tipoCuentaLabel((string) ($modalidad['tipo_cuenta'] ?? 'CUENTA_COMPLETA'), isset($modalidad['dispositivos']) ? (int) $modalidad['dispositivos'] : null)) ?>,
                                <?= e(duration_label(max(1, (int) ($modalidad['duracion_meses'] ?? 1)))) ?>,
                                Costo: <?= e(display_money((float) ($modalidad['costo'] ?? 0))) ?>,
                                Venta: <?= e(display_money((float) $modalidad['precio'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="costo_base">Costo de la cuenta (Bs)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control js-costo-base"
                        id="costo_base"
                        name="costo_base"
                        value="<?= e(isset($item['costo_base']) && $item['costo_base'] !== null ? format_decimal_amount($item['costo_base']) : '') ?>"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="precio_venta">Precio final de venta (Bs)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control js-precio-venta"
                        id="precio_venta"
                        name="precio_venta"
                        value="<?= e(isset($item['precio_venta']) && $item['precio_venta'] !== null ? format_decimal_amount($item['precio_venta']) : '') ?>"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ganancia</label>
                    <div class="form-control bg-light">
                        <strong class="js-ganancia">
                            <?= e(display_money((float) ((float) ($item['precio_venta'] ?? 0) - (float) ($item['costo_base'] ?? 0)))) ?>
                        </strong>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="fecha_inicio">Fecha inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= e(old('fecha_inicio', (string) $item['fecha_inicio'])) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="fecha_vencimiento">Fecha vencimiento</label>
                    <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" value="<?= e(old('fecha_vencimiento', (string) $item['fecha_vencimiento'])) ?>" required>
                </div>

                <div class="col-md-6 js-usuario-wrap">
                    <label class="form-label js-usuario-label" for="usuario_proveedor">Dato de la cuenta para renovar</label>
                    <input
                        type="text"
                        class="form-control js-usuario-input"
                        id="usuario_proveedor"
                        name="usuario_proveedor"
                        value="<?= e((string) ($item['usuario_proveedor'] ?? '')) ?>"
                    >
                    <small class="text-secondary js-usuario-help">Se pedirá según la configuración de la plataforma.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_cuenta">Contraseña de la cuenta</label>
                    <input
                        type="text"
                        class="form-control"
                        id="password_cuenta"
                        name="password_cuenta"
                        value="<?= e(decrypt_secret((string) ($item['password_cuenta'] ?? ''))) ?>"
                        placeholder="Contraseña del proveedor"
                        autocomplete="off"
                    >
                    <small class="text-secondary">Se guarda cifrada.</small>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="flag_no_renovo" name="flag_no_renovo" <?= (int) $item['flag_no_renovo'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="flag_no_renovo">Marcar como no renovado</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="notas">Notas internas</label>
                    <textarea class="form-control" id="notas" name="notas" rows="2" placeholder="Ej: cliente paga en efectivo, acuerdo especial..."><?= e((string) ($item['notas'] ?? '')) ?></textarea>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-lg w-100 w-sm-auto" type="submit"><?= icon('save') ?><span class="button-label">Guardar cambios</span></button>
                    <a href="<?= e(url('/suscripciones')) ?>" class="btn btn-outline-secondary btn-lg w-100 w-sm-auto"><?= icon('arrow-left') ?><span class="button-label">Cancelar</span></a>
                </div>
            </div>
        </form>
    </div>
</div>
