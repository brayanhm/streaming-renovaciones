<?php
declare(strict_types=1);

use App\Models\Modalidad;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Editar costos y precios por plataforma</h1>
        <small class="text-secondary">Cada duracion tiene su propio costo y precio de venta. La ganancia se calcula como venta menos costo.</small>
    </div>
    <a href="<?= e(url('/tipos-suscripcion')) ?>" class="btn btn-outline-secondary">Volver a planes</a>
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
                <button class="btn btn-primary btn-lg" type="submit">Cargar planes</button>
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

            <form method="post" action="<?= e(url('/tipos-suscripcion/precios')) ?>" id="bulk-pricing-form">
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
                                    <td><?= e((string) max(1, (int) ($item['duracion_meses'] ?? 1))) ?> mes(es)</td>
                                    <td>
                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            class="form-control js-costo"
                                            name="costo[<?= $id ?>]"
                                            value="<?= e((string) ((int) round($costo))) ?>"
                                            required
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            class="form-control js-precio"
                                            name="precio[<?= $id ?>]"
                                            value="<?= e((string) ((int) round($precio))) ?>"
                                            required
                                        >
                                    </td>
                                    <td>
                                        <strong class="js-ganancia <?= $ganancia < 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= e(money($ganancia)) ?>
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($rows)): ?>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-success btn-lg">Guardar costos y precios</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
(() => {
    const form = document.getElementById('bulk-pricing-form');
    if (!form) return;

    const money = (value) => {
        const sign = value < 0 ? '-' : '';
        const abs = Math.abs(value).toLocaleString('es-BO');
        return 'Bs ' + sign + abs;
    };

    const recalc = () => {
        const rows = form.querySelectorAll('tbody tr');
        rows.forEach((row) => {
            const costInput = row.querySelector('.js-costo');
            const priceInput = row.querySelector('.js-precio');
            const gainEl = row.querySelector('.js-ganancia');
            if (!costInput || !priceInput || !gainEl) return;

            const cost = Number.parseInt(costInput.value || '0', 10);
            const price = Number.parseInt(priceInput.value || '0', 10);
            const gain = (Number.isNaN(price) ? 0 : price) - (Number.isNaN(cost) ? 0 : cost);
            gainEl.textContent = money(gain);
            gainEl.classList.toggle('text-danger', gain < 0);
            gainEl.classList.toggle('text-success', gain >= 0);
        });
    };

    form.addEventListener('input', (event) => {
        if (event.target instanceof HTMLInputElement) {
            if (event.target.classList.contains('js-costo') || event.target.classList.contains('js-precio')) {
                recalc();
            }
        }
    });
})();
</script>
