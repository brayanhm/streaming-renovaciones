<?php
declare(strict_types=1);

use App\Models\Modalidad;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Editar costos y precios por plataforma</h1>
        <small class="text-secondary">Cada duracion tiene su propio costo y precio de venta. La ganancia se calcula como venta menos costo.</small>
    </div>
    <a href="<?= e(url('/tipos-suscripcion')) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver a planes</span></a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="<?= e(url('/tipos-suscripcion/precios')) ?>" class="row g-2 align-items-end">
            <div class="col-12 col-md-8">
                <label class="form-label" for="plataforma_id">Plataforma</label>
                <select class="form-select form-select-lg" id="plataforma_id" name="plataforma_id" required>
                    <option value="">Seleccionar...</option>
                    <?php foreach (($platforms ?? []) as $platform): ?>
                        <option
                            value="<?= e((string) $platform['id']) ?>"
                            <?= (int) ($selectedPlatformId ?? 0) === (int) $platform['id'] ? 'selected' : '' ?>
                        >
                            <?= e((string) $platform['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4 d-grid">
                <button class="btn btn-primary btn-lg" type="submit"><?= icon('layers') ?><span class="button-label">Cargar planes</span></button>
            </div>
        </form>
    </div>
</div>

<?php if ((int) ($selectedPlatformId ?? 0) > 0): ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">
                    Plataforma: <?= e((string) ($selectedPlatform['nombre'] ?? '')) ?>
                </h2>
                <span class="badge text-bg-secondary">Planes: <?= e((string) count($rows ?? [])) ?></span>
            </div>

            <form method="post" action="<?= e(url('/tipos-suscripcion/precios')) ?>" id="bulk-pricing-form" data-form-behavior="bulk-pricing">
                <?= csrf_field() ?>
                <input type="hidden" name="plataforma_id" value="<?= e((string) (int) ($selectedPlatformId ?? 0)) ?>">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Plan</th>
                                <th>Cuenta</th>
                                <th>Duracion</th>
                                <th>Costo (Bs)</th>
                                <th>Precio venta (Bs)</th>
                                <th>Ganancia (Bs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="7" class="text-center text-secondary py-4">No hay planes para esta plataforma.</td></tr>
                            <?php endif; ?>
                            <?php foreach (($rows ?? []) as $item): ?>
                                <?php
                                $id = (int) ($item['id'] ?? 0);
                                $costo = (float) ($item['costo'] ?? 0);
                                $precio = (float) ($item['precio'] ?? 0);
                                $ganancia = $precio - $costo;
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= $id ?></td>
                                    <td><?= e((string) ($item['nombre_modalidad'] ?? '')) ?></td>
                                    <td><?= e(Modalidad::tipoCuentaLabel((string) ($item['tipo_cuenta'] ?? 'CUENTA_COMPLETA'), isset($item['dispositivos']) ? (int) $item['dispositivos'] : null)) ?></td>
                                    <td><?= e(duration_label(max(1, (int) ($item['duracion_meses'] ?? 1)))) ?></td>
                                    <td>
                                        <input
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            class="form-control js-costo"
                                            name="costo[<?= $id ?>]"
                                            value="<?= e(format_decimal_amount($costo)) ?>"
                                            required
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            class="form-control js-precio"
                                            name="precio[<?= $id ?>]"
                                            value="<?= e(format_decimal_amount($precio)) ?>"
                                            required
                                        >
                                    </td>
                                    <td>
                                        <strong class="js-ganancia <?= $ganancia < 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= e(display_money($ganancia)) ?>
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($rows)): ?>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-success btn-lg"><?= icon('save') ?><span class="button-label">Guardar costos y precios</span></button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
<?php endif; ?>
