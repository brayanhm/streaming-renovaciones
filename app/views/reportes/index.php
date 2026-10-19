<?php
declare(strict_types=1);

$mesesNombres = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Reportes por servicio</h1>
        <p class="text-secondary mb-0">Ingresos, costos y saldo de los registros del período seleccionado.</p>
    </div>
    <a href="<?= e(url('/reportes/exportar?' . http_build_query(['servicio' => $servicio, 'meses' => $meses]))) ?>" class="btn btn-outline-primary"><?= icon('download') ?><span class="button-label">Exportar CSV</span></a>
</div>

<form method="get" action="<?= e(url('/reportes')) ?>" class="card card-body mb-3">
    <div class="row g-3 align-items-end">
        <div class="col-md-5"><label class="form-label" for="reporte-servicio">Servicio</label>
            <select class="form-select" id="reporte-servicio" name="servicio">
            <?php foreach ($servicios as $key => $label): ?><option value="<?= e($key) ?>" <?= $servicio === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4"><label class="form-label" for="reporte-periodo">Período</label>
            <select class="form-select" id="reporte-periodo" name="meses">
            <?php foreach ([6,12,24] as $number): ?><option value="<?= $number ?>" <?= $meses === $number ? 'selected' : '' ?>>Últimos <?= $number ?> meses, incluido el actual</option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-grid"><button type="submit" class="btn btn-primary"><?= icon('search') ?>Ver reporte</button></div>
    </div>
</form>
<div class="mb-3 gs-financial-scope">
    <p class="fw-semibold mb-1"><?= e($servicios[$servicio]) ?> · <?= e(display_date($desde)) ?> al <?= e(display_date($hasta)) ?></p>
    <p class="small text-secondary mb-1">Las renovaciones incluyen el costo guardado en cada operación. En ChatGPT y Claude, los costos corresponden a los pagos de las cuentas en su fecha de pago.</p>
    <p class="small text-secondary mb-0">Saldo registrado = ingresos menos costos del período. Las ventas de ciclos anteriores se conservan. El CSV usa estos mismos filtros.</p>
</div>

<?php
$cartera = $cartera ?? [];
$totalCartera = (int) ($cartera['total'] ?? 0);
$noRenov = (int) ($cartera['no_renovaron'] ?? 0);
$churn = $totalCartera > 0 ? round($noRenov / $totalCartera * 100, 1) : 0.0;
?>
<?php if (in_array($servicio, ['todos','streaming'], true)): ?>
<details class="mb-3"><summary class="text-primary mb-2">Estado de las suscripciones del historial general</summary>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-secondary mb-1">Activos</h2>
            <div class="h4 mb-0 text-success"><?= e((string) (int) ($cartera['activos'] ?? 0)) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-secondary mb-1">Vencen en 7 días</h2>
            <div class="h4 mb-0 text-warning"><?= e((string) (int) ($cartera['vencen_7'] ?? 0)) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-secondary mb-1">Vencen en 30 días</h2>
            <div class="h4 mb-0"><?= e((string) (int) ($cartera['vencen_30'] ?? 0)) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-secondary mb-1">Tasa de no-renovación</h2>
            <div class="h4 mb-0 <?= $churn >= 20 ? 'text-danger' : 'text-secondary' ?>"><?= e((string) $churn) ?>%</div>
            <small class="text-secondary"><?= e((string) $noRenov) ?> de <?= e((string) $totalCartera) ?></small>
        </div></div>
    </div>
</div>

</details>
<?php endif; ?>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Ventas / renovaciones del período</h2>
                <div class="h4 mb-0"><?= e((string) (int) ($totalPeriodo['renovaciones'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Ingresos registrados del período</h2>
                <div class="h4 mb-0"><?= e(display_money((float) ($totalPeriodo['total_monto'] ?? 0))) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Saldo registrado del período</h2>
                <p class="small text-secondary mb-1">Costos: <?= e(display_money((float) $totalPeriodo['total_costo'])) ?></p>
                <?php $ganMes = (float) ($totalPeriodo['total_utilidad'] ?? 0); ?>
                <div class="h4 mb-0 <?= $ganMes < 0 ? 'text-danger' : 'text-success' ?>">
                    <?= e(display_money($ganMes)) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<p class="small text-secondary mb-3">Este mes: <?= (int) $mesActual['renovaciones'] ?> ventas o renovaciones · Ingresos <?= e(display_money((float) $mesActual['total_monto'])) ?> · Costos <?= e(display_money((float) $mesActual['total_costo'])) ?> · Saldo <?= e(display_money((float) $mesActual['total_utilidad'])) ?>.</p>
<?php if (!empty($porMes)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Ingresos y saldo por mes</h2>
        <p class="small text-secondary" id="report-chart-description">Comparación mensual de ingresos y saldo registrados. Las cifras completas están en la tabla «Por mes».</p>
        <canvas id="chartGanancia" height="80" role="img" aria-label="Ingresos y saldo por mes" aria-describedby="report-chart-description"></canvas>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom">
                    <h2 class="h6 mb-0">Por mes</h2>
                </div>
                <div class="table-responsive">
                    <table data-responsive-cards data-mobile-primary="" class="table table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Mes</th>
                                <th class="text-end">Ventas / renovaciones</th>
                                <th class="text-end">Ingresos</th>
                                <th class="text-end">Costos</th>
                                <th class="text-end">Saldo registrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($porMes)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-secondary">Sin movimientos registrados.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($porMes as $fila): ?>
                                <?php $gan = (float) ($fila['total_utilidad'] ?? 0); ?>
                                <tr>
                                    <td><?= e($mesesNombres[(int) $fila['mes']] . ' ' . $fila['anio']) ?></td>
                                    <td class="text-end"><?= e((string) (int) $fila['renovaciones']) ?></td>
                                    <td class="text-end"><?= e(display_money((float) ($fila['total_monto'] ?? 0))) ?></td>
                                    <td class="text-end"><?= e(display_money((float) ($fila['total_costo'] ?? 0))) ?></td>
                                    <td class="text-end fw-semibold <?= $gan < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= e(display_money($gan)) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom">
                    <h2 class="h6 mb-0">Por plataforma</h2>
                </div>
                <div class="table-responsive">
                    <table data-responsive-cards data-mobile-primary="" class="table table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Plataforma</th>
                                <th class="text-end">Ventas / renovaciones</th>
                                <th class="text-end">Ingresos</th><th class="text-end">Costos</th>
                                <th class="text-end">Saldo registrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($porPlataforma)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-secondary">Sin datos.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($porPlataforma as $fila): ?>
                                <?php $gan = (float) ($fila['total_utilidad'] ?? 0); ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $fila['plataforma']) ?><small class="d-block text-secondary"><?= e($servicios[$fila['servicio']] ?? '') ?></small></td>
                                    <td class="text-end"><?= e((string) (int) $fila['renovaciones']) ?></td>
                                    <td class="text-end"><?= e(display_money((float) $fila['total_monto'])) ?></td><td class="text-end"><?= e(display_money((float) $fila['total_costo'])) ?></td>
                                    <td class="text-end fw-semibold <?= $gan < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= e(display_money($gan)) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($porMes)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(() => {
    if (typeof Chart === 'undefined') return;
    const labels = <?= json_encode(array_map(
        fn($f) => $mesesNombres[(int)$f['mes']] . ' ' . $f['anio'],
        $porMes
    )) ?>;
    const ganancia = <?= json_encode(array_map(fn($f) => round((float)($f['total_utilidad'] ?? 0), 2), $porMes)) ?>;
    const ingreso  = <?= json_encode(array_map(fn($f) => round((float)($f['total_monto'] ?? 0), 2), $porMes)) ?>;

    const theme = getComputedStyle(document.documentElement);
    const themeColor = token => theme.getPropertyValue(token).trim();
    const positiveColor = themeColor('--gs-chart-gain');
    const negativeColor = themeColor('--gs-chart-loss');
    const ctx = document.getElementById('chartGanancia').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Ingreso (Bs)',
                    data: ingreso,
                    backgroundColor: themeColor('--gs-chart-income-fill'),
                    borderColor: themeColor('--gs-chart-income'),
                    borderWidth: 1,
                },
                {
                    label: 'Saldo registrado (Bs)',
                    data: ganancia,
                    backgroundColor: ganancia.map(v => themeColor(v < 0 ? '--gs-chart-loss-fill' : '--gs-chart-gain-fill')),
                    borderColor: ganancia.map(v => v < 0 ? negativeColor : positiveColor),
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top', labels: { color: themeColor('--gs-text') } },
            },
            scales: {
                x: { ticks: { color: themeColor('--gs-dim') }, grid: { display: false } },
                y: {
                    beginAtZero: true,
                    ticks: { color: themeColor('--gs-dim') },
                    grid: { color: themeColor('--gs-border') },
                },
            },
        },
    });
})();
</script>
<?php endif; ?>
