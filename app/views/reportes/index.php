<?php
declare(strict_types=1);

$mesesNombres = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Reportes financieros</h1>
        <p class="text-secondary mb-0">Resumen de renovaciones, ingresos y ganancias.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Renovaciones este mes</h2>
                <div class="h4 mb-0"><?= e((string) (int) ($mesActual['renovaciones'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Ingreso este mes</h2>
                <div class="h4 mb-0"><?= e(money((float) ($mesActual['total_monto'] ?? 0))) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Ganancia este mes</h2>
                <?php $ganMes = (float) ($mesActual['total_utilidad'] ?? 0); ?>
                <div class="h4 mb-0 <?= $ganMes < 0 ? 'text-danger' : 'text-success' ?>">
                    <?= e(money($ganMes)) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($porMes)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Ganancia mensual (últimos 12 meses)</h2>
        <canvas id="chartGanancia" height="80"></canvas>
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
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Mes</th>
                                <th class="text-end">Renovaciones</th>
                                <th class="text-end">Ingreso</th>
                                <th class="text-end">Costo</th>
                                <th class="text-end">Ganancia</th>
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
                                    <td class="text-end"><?= e(money((float) ($fila['total_monto'] ?? 0))) ?></td>
                                    <td class="text-end"><?= e(money((float) ($fila['total_costo'] ?? 0))) ?></td>
                                    <td class="text-end fw-semibold <?= $gan < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= e(money($gan)) ?>
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
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Plataforma</th>
                                <th class="text-end">Renov.</th>
                                <th class="text-end">Ganancia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($porPlataforma)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-secondary">Sin datos.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($porPlataforma as $fila): ?>
                                <?php $gan = (float) ($fila['total_utilidad'] ?? 0); ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $fila['plataforma']) ?></td>
                                    <td class="text-end"><?= e((string) (int) $fila['renovaciones']) ?></td>
                                    <td class="text-end fw-semibold <?= $gan < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= e(money($gan)) ?>
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
    const labels = <?= json_encode(array_map(
        fn($f) => $mesesNombres[(int)$f['mes']] . ' ' . $f['anio'],
        $porMes
    )) ?>;
    const ganancia = <?= json_encode(array_map(fn($f) => round((float)($f['total_utilidad'] ?? 0), 2), $porMes)) ?>;
    const ingreso  = <?= json_encode(array_map(fn($f) => round((float)($f['total_monto'] ?? 0), 2), $porMes)) ?>;

    const ctx = document.getElementById('chartGanancia').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Ingreso (Bs)',
                    data: ingreso,
                    backgroundColor: 'rgba(124,58,237,0.25)',
                    borderColor: 'rgba(124,58,237,0.8)',
                    borderWidth: 1,
                },
                {
                    label: 'Ganancia (Bs)',
                    data: ganancia,
                    backgroundColor: ganancia.map(v => v < 0 ? 'rgba(220,53,69,0.7)' : 'rgba(25,135,84,0.7)'),
                    borderColor: ganancia.map(v => v < 0 ? '#dc3545' : '#198754'),
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } },
        },
    });
})();
</script>
<?php endif; ?>
