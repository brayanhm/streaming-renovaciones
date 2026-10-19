<?php
declare(strict_types=1);

$canAdmin = $canAdmin ?? false;
$service = 'claude';
$unit = 'cupos';
$q = $q ?? $search ?? '';
$summary = $summary ?? [];
$claudeDate = static fn (?string $value): string => $value ? (new DateTimeImmutable($value))->format('d/m/Y') : '—';
$claudeMoney = static fn (mixed $value): string => number_format((float) $value, 2, ',', '.');
$periodLabels = ['PAGADO' => 'Mes pagado', 'GRATIS' => 'Gratis', 'SIN_PAGO' => 'Sin pago registrado'];
$periodColors = ['PAGADO' => 'success', 'GRATIS' => 'secondary', 'SIN_PAGO' => 'secondary'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Claude</h1>
        <p class="text-secondary mb-0 d-none d-sm-block">Cuentas principales, pagos y ventas por cupos.</p>
    </div>
    <div class="gs-page-actions"><?php if ($canAdmin): ?><a href="<?= e(url('/claude?nuevo=1')) ?>#nueva-cuenta" class="btn btn-primary" data-open-panel="nueva-cuenta"><?= icon('plus') ?><span class="button-label">Nueva cuenta</span></a><?php endif; ?>
    <a href="<?= e(url('/claude/distribucion')) ?>" class="btn btn-outline-primary d-none d-lg-inline-flex"><?= icon('copy') ?><span class="button-label">Cuenta en distribución</span></a></div>
</div>

<?php require __DIR__ . '/../cuentas_compartidas/actual.php'; ?>

<details class="mb-3" data-account-overview><summary class="text-primary mb-2">Resumen <?= ($q ?? '') !== '' ? 'de los resultados' : 'de todas las cuentas' ?></summary>
<div class="row row-cols-2 row-cols-lg-5 g-3 mb-3">
    <?php foreach ([['Cuentas', (int) ($summary['cuentas'] ?? 0)], ['Cupos ocupados', (int) ($summary['ocupados'] ?? 0)], ['Capacidad sin ocupar', max(0, (int) ($summary['libres'] ?? 0))], ['Ventas históricas', 'Bs ' . $claudeMoney($summary['ventas_bs'] ?? 0)], ['Pagos históricos', 'Bs ' . $claudeMoney($summary['pagos_bs'] ?? 0)]] as [$label, $value]): ?>
        <div class="col"><div class="card shadow-sm h-100"><div class="card-body">
            <div class="small text-secondary mb-1"><?= e($label) ?></div>
            <div class="h4 mb-0"><?= e((string) $value) ?></div>
        </div></div></div>
    <?php endforeach; ?>
</div>
<p class="small text-secondary">La capacidad sin ocupar incluye cuentas en reserva, Gratis, inactivas o pendientes. Alcanzar la referencia no bloquea las entregas de la cuenta seleccionada.</p>
</details>

<div class="row g-3 gs-page-layout">
    <div class="<?= $canAdmin ? 'col-xl-8' : 'col-12' ?>">
        <?php require __DIR__ . '/../cuentas_compartidas/filtros.php'; ?>
        <p class="small text-secondary mb-2"><span class="d-none d-lg-inline">Orden: cuenta actual, siguientes listas por vencimiento más cercano y demás cuentas. La selección es manual.</span><span class="d-lg-none">Actual, próximas por vencimiento y demás. Selección manual.</span></p>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table data-responsive-cards data-mobile-primary="0,1,2,4" class="table table-striped align-middle mb-0 gs-account-table">
                        <thead class="table-dark"><tr><th>Cuenta</th><th>Vigencia</th><th>Cupos</th><th class="text-end">Último pago</th><th class="text-end">Acciones</th></tr></thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-4">No hay cuentas con estos filtros. <?php if ($q !== '' || ($filter ?? '') !== ''): ?><a href="<?= e(url('/claude')) ?>">Ver todas las cuentas</a><?php else: ?>Registra una cuenta para comenzar.<?php endif; ?></td></tr>
                            <?php endif; ?>
                            <?php foreach (($rows ?? []) as $row): ?>
                                <?php $period = strtoupper((string) ($row['estado_periodo'] ?? 'SIN_PAGO')); $occupied = (int) ($row['ocupados'] ?? 0); $recommended = (int) ($row['recomendado'] ?? 5); $remaining = max(0, $recommended - $occupied); $exceeded = max(0, $occupied - $recommended); ?>
                                <tr data-record-key="claude-<?= (int) $row['id'] ?>">
                                    <td>
                                        <a class="fw-semibold text-decoration-none" href="<?= e(url('/claude/' . (int) $row['id'])) ?>"><?= e((string) $row['etiqueta']) ?></a>
                                        <?php if (strcasecmp(trim((string) $row['etiqueta']), trim((string) ($row['correo'] ?? ''))) !== 0): ?><small class="d-block text-secondary text-break"><?= e((string) ($row['correo'] ?? '')) ?></small><?php endif; ?>
                                        <span class="badge text-bg-<?= !empty($row['distribucion_actual']) ? 'primary' : (!empty($row['llena']) ? 'success' : 'secondary') ?> mt-1"><?= e((string) $row['estado_distribucion']) ?></span>
                                        <?php if (!empty($row['lista_para_distribucion'])): ?>
                                            <small class="d-block text-primary fw-semibold"><?= ($row['turno_distribucion'] ?? 0) === 1 ? 'Siguiente para distribución' : (isset($row['turno_distribucion']) ? 'En cola · ' . (int) $row['turno_distribucion'] : 'Lista para distribuir') ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($row['motivo_bloqueo'])): ?><small class="d-block text-danger mt-1"><?= e($row['motivo_bloqueo']) ?></small><?php endif; ?>
                                        <?php if ((int) ($row['activo'] ?? 1) !== 1): ?><span class="badge text-bg-secondary mt-1">Inactiva</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-<?= e($periodColors[$period] ?? 'secondary') ?>"><?= e($periodLabels[$period] ?? $period) ?></span>
                                        <small class="d-block text-secondary mt-1">Pagado hasta <?= e($claudeDate($row['fecha_vencimiento'] ?? null)) ?></small>
                                        
                                        <?php if ($period === 'PAGADO'): ?><small class="text-success"><?= (int) ($row['dias_pagado'] ?? 0) ?> días restantes</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php require __DIR__ . '/../cuentas_compartidas/ocupacion_listado.php'; ?>
                                    </td>
                                    <td class="text-end text-nowrap"><?php if (($row['ultimo_costo_bs'] ?? null) !== null): ?>Bs <?= e($claudeMoney($row['ultimo_costo_bs'])) ?><?php else: ?><span class="text-secondary">Sin registrar</span><?php endif; ?></td>
                                    <td class="text-end"><?php require __DIR__ . '/../cuentas_compartidas/acciones_listado.php'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php require __DIR__ . '/../cuentas_compartidas/paginacion.php'; ?>
        <p class="small text-secondary mt-2 mb-0">La cuenta seleccionada sigue en distribución aunque alcance su capacidad de referencia. Todos los vendedores pueden seguir entregando hasta que el administrador seleccione otra cuenta.</p>
    </div>
    <?php if ($canAdmin): ?>
    <div class="col-xl-4 gs-create-column">
        <details class="card gs-form-panel shadow-sm" id="nueva-cuenta" data-form-panel <?= ($_GET['nuevo'] ?? '') === '1' || ($_SESSION['_old_action'] ?? '') === '/claude' ? 'open' : '' ?>>
            <summary>Nueva cuenta Claude</summary>
            <div class="card-body">
            <form method="post" action="<?= e(url('/claude')) ?>" autocomplete="off" data-chatgpt-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="token_operacion" value="<?= e((string) ($creationToken ?? '')) ?>">
                <div class="mb-3">
                    <label class="form-label" for="etiqueta">Nombre interno</label>
                    <input type="text" class="form-control" id="etiqueta" name="etiqueta" value="<?= e(old('etiqueta')) ?>" maxlength="100" placeholder="Claude 01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="correo">Correo de la cuenta</label>
                    <input type="email" class="form-control" id="correo" name="correo" value="<?= e(old('correo')) ?>" maxlength="150" autocomplete="off" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_cuenta">Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password_cuenta" name="password_cuenta" autocomplete="new-password" required>
                        <button type="button" class="btn btn-outline-secondary" data-password-target="password_cuenta" aria-pressed="false"><?= icon('eye') ?><span class="button-label">Mostrar</span></button>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="fecha_pago">Fecha de pago</label>
                        <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" value="<?= e(old('fecha_pago', date('Y-m-d'))) ?>" max="<?= e(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="monto_bs">Costo real en Bs</label>
                        <input type="number" class="form-control" id="monto_bs" name="monto_bs" value="<?= e(old('monto_bs')) ?>" min="0.01" step="0.01" placeholder="Monto cobrado" required>
                    </div>
                </div>
                <p class="small text-secondary">Cada pago inicia un mes calendario de vigencia. Registra el importe real cobrado en bolivianos.</p>
                <div class="mb-3">
                    <label class="form-label" for="capacidad">Capacidad en cupos</label>
                    <input type="number" class="form-control" id="capacidad" name="capacidad" value="<?= e(old('capacidad', '5')) ?>" min="1" max="99999" step="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="notas">Notas</label>
                    <textarea class="form-control" id="notas" name="notas" rows="2"><?= e(old('notas')) ?></textarea>
                </div>
                <button type="submit" class="btn btn-success w-100"><?= icon('plus') ?><span class="button-label">Crear cuenta y registrar pago</span></button>
            </form>
        </div></details>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/scripts.php'; ?>
