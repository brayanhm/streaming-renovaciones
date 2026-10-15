<?php
declare(strict_types=1);

$canAdmin = $canAdmin ?? false;
$service = 'chatgpt';
$unit = 'dispositivos';
$q = $q ?? $search ?? '';
$summary = $summary ?? [];
$chatgptDate = static fn (?string $value): string => $value ? (new DateTimeImmutable($value))->format('d/m/Y') : '—';
$chatgptMoney = static fn (mixed $value): string => number_format((float) $value, 2, ',', '.');
$periodLabels = ['PAGADO' => 'Mes pagado', 'GRACIA' => 'Mes de gracia', 'GRATIS' => 'Gratis', 'SIN_PAGO' => 'Fechas pendientes de revisión'];
$periodColors = ['PAGADO' => 'success', 'GRACIA' => 'warning', 'GRATIS' => 'secondary', 'SIN_PAGO' => 'secondary'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">ChatGPT</h1>
        <p class="text-secondary mb-0">Cuentas principales, pagos y ventas por dispositivos.</p>
    </div>
    <div class="gs-page-actions"><?php if ($canAdmin): ?><a href="<?= e(url('/chatgpt?nuevo=1')) ?>#nueva-cuenta" class="btn btn-primary" data-open-panel="nueva-cuenta"><?= icon('plus') ?><span class="button-label">Nueva cuenta</span></a><?php endif; ?>
    <?php if ($canAdmin): ?><a class="btn btn-outline-primary" href="<?= e(url('/chatgpt?nuevo=importar')) ?>#importar-cuentas" data-open-panel="importar-cuentas">Registrar existentes</a><?php endif; ?>
    <a href="<?= e(url('/chatgpt/distribucion')) ?>" class="btn btn-primary"><?= icon('copy') ?><span class="button-label">Cuenta en distribución</span></a></div>
</div>

<?php require __DIR__ . '/../cuentas_compartidas/actual.php'; ?>
<?php require __DIR__ . '/resumen_avisos.php'; ?>

<details class="mb-3" data-account-overview><summary class="text-primary mb-2">Resumen <?= ($q ?? '') !== '' ? 'de los resultados' : 'de todas las cuentas' ?></summary>
<div class="row row-cols-2 row-cols-lg-5 g-3 mb-3">
    <?php foreach ([['Cuentas', (int) ($summary['cuentas'] ?? 0)], ['Dispositivos ocupados', (int) ($summary['ocupados'] ?? 0)], ['Capacidad sin ocupar', max(0, (int) ($summary['libres'] ?? 0))], ['Ventas históricas', 'Bs ' . $chatgptMoney($summary['ventas_bs'] ?? 0)], ['Pagos históricos', 'Bs ' . $chatgptMoney($summary['pagos_bs'] ?? 0)]] as [$label, $value]): ?>
        <div class="col"><div class="card shadow-sm h-100"><div class="card-body">
            <div class="small text-secondary mb-1"><?= e($label) ?></div>
            <div class="h4 mb-0"><?= e((string) $value) ?></div>
        </div></div></div>
    <?php endforeach; ?>
</div>
<p class="small text-secondary">La capacidad sin ocupar incluye cuentas en reserva, Gratis, inactivas o pendientes. Los cupos para vender se muestran en la cuenta seleccionada.</p>
</details>

<div class="row g-3 gs-page-layout">
    <div class="<?= $canAdmin ? 'col-xl-8' : 'col-12' ?>">
        <form method="get" action="<?= e(url('/chatgpt')) ?>" class="d-flex gap-2 mb-3" role="search">
            <label class="visually-hidden" for="chatgpt-search">Buscar cuenta</label>
            <input type="search" class="form-control" id="chatgpt-search" name="q" value="<?= e((string) ($q ?? '')) ?>" placeholder="Buscar por correo o nombre de cuenta">
            <button type="submit" class="btn btn-outline-primary"><?= icon('search') ?><span class="button-label">Buscar</span></button>
            <?php if (($q ?? '') !== ''): ?><a href="<?= e(url('/chatgpt')) ?>" class="btn btn-outline-secondary"><?= icon('funnel-x') ?><span class="button-label">Limpiar</span></a><?php endif; ?>
        </form>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table data-responsive-cards data-mobile-primary="0,1,2,4" class="table table-striped align-middle mb-0">
                        <thead class="table-dark"><tr><th>Cuenta</th><th>Vigencia</th><th>Dispositivos</th><th class="text-end">Último pago</th><th></th></tr></thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-4"><?= ($q ?? '') !== '' ? 'No hay cuentas que coincidan con la búsqueda.' : 'Todavía no hay cuentas ChatGPT.' ?></td></tr>
                            <?php endif; ?>
                            <?php foreach (($rows ?? []) as $row): ?>
                                <?php $period = strtoupper((string) ($row['estado_periodo'] ?? 'SIN_PAGO')); $occupied = (int) ($row['ocupados'] ?? 0); $recommended = (int) ($row['recomendado'] ?? 20); $remaining = max(0, $recommended - $occupied); $exceeded = max(0, $occupied - $recommended); ?>
                                <tr>
                                    <td>
                                        <a class="fw-semibold text-decoration-none" href="<?= e(url('/chatgpt/' . (int) $row['id'])) ?>"><?= e((string) $row['etiqueta']) ?></a>
                                        <small class="d-block text-secondary text-break"><?= e((string) ($row['correo'] ?? '')) ?></small>
                                        <span class="badge text-bg-<?= !empty($row['llena']) ? 'success' : (!empty($row['distribucion_actual']) ? 'primary' : 'secondary') ?> mt-1"><?= e((string) $row['estado_distribucion']) ?></span>
                                        <?php if (!empty($row['motivo_bloqueo'])): ?><small class="d-block text-danger mt-1"><?= e($row['motivo_bloqueo']) ?></small><?php endif; ?>
                                        <?php if (!empty($row['distribucion_actual'])): ?><small class="d-block text-primary">Cuenta seleccionada</small><?php endif; ?>
                                        <?php if ((int) ($row['activo'] ?? 1) !== 1): ?><span class="badge text-bg-secondary mt-1">Inactiva</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $calendarPeriod = (string) ($row['estado_vigencia'] ?? $period); ?>
                                        <span class="badge text-bg-<?= e($periodColors[$calendarPeriod] ?? 'secondary') ?>"><?= e($periodLabels[$calendarPeriod] ?? $calendarPeriod) ?></span>
                                        <?php if ((int) ($row['plan_gratis'] ?? 0) === 1): ?><small class="d-block text-danger">Plan marcado Gratis</small><?php endif; ?>
                                        <small class="d-block text-secondary mt-1">Pagado hasta <?= e($chatgptDate($row['fecha_vencimiento'] ?? null)) ?></small>
                                        <small class="d-block text-secondary">Gracia hasta <?= e($chatgptDate($row['fin_gracia'] ?? null)) ?></small>
                                        <?php if ($calendarPeriod === 'PAGADO'): ?><small class="text-success">En <?= (int) ($row['dias_pagado'] ?? 0) ?> días comienza la gracia</small><?php elseif ($calendarPeriod === 'GRACIA'): ?><small class="text-warning">Gracia: <?= (int) ($row['dias_gracia'] ?? 0) ?> días restantes</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="<?= $exceeded > 0 ? 'text-danger' : ($remaining === 0 ? 'text-warning' : '') ?>"><?= $occupied ?> ocupados</strong>
                                        <small class="d-block text-secondary">Capacidad: <?= $recommended ?></small>
                                        <small class="d-block text-secondary">Libres: <?= $remaining ?></small>
                                        <?php if ($exceeded > 0): ?><span class="badge text-bg-danger mt-1">+<?= $exceeded ?> adicionales</span><?php elseif ($remaining === 0): ?><span class="badge text-bg-warning mt-1">Capacidad alcanzada</span><?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap"><?php if (($row['ultimo_costo_bs'] ?? null) !== null): ?>Bs <?= e($chatgptMoney($row['ultimo_costo_bs'])) ?><?php else: ?><span class="text-secondary">Sin registrar</span><?php endif; ?></td>
                                    <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="<?= e(url('/chatgpt/' . (int) $row['id'])) ?>"><?= icon('arrow-right') ?><span class="button-label">Abrir</span></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <p class="small text-secondary mt-2 mb-0">Al ocupar todos sus cupos, la cuenta pasa automáticamente a Vendida. Solo se vende en la cuenta seleccionada; únicamente el administrador puede superar su capacidad.</p>
    </div>
    <?php if ($canAdmin): ?>
    <div class="col-xl-4 gs-create-column">
        <details class="card gs-form-panel shadow-sm mb-3" id="importar-cuentas" data-form-panel <?= ($_GET['nuevo'] ?? '') === 'importar' || ($_SESSION['_old_action'] ?? '') === '/chatgpt/importar-cuentas' ? 'open' : '' ?>>
            <summary>Registrar cuentas existentes</summary>
            <div class="card-body">
            <p class="small text-secondary">Quedan pendientes de revisar fechas y credenciales. No registra pagos ni ventas.</p>
            <form method="post" action="<?= e(url('/chatgpt/importar-cuentas')) ?>" data-chatgpt-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="token_operacion" value="<?= e((string) ($importToken ?? '')) ?>">
                <div class="mb-3">
                    <label class="form-label" for="correos_existentes">Correos de las cuentas</label>
                    <textarea class="form-control" id="correos_existentes" name="correos" rows="6" placeholder="Una cuenta por línea" required><?= e(old('correos')) ?></textarea>
                    <small class="text-secondary">Escribe un correo por línea. Luego abre cada cuenta para completar la contraseña y revisar su vigencia.</small>
                </div>
                <button type="submit" class="btn btn-primary w-100"><?= icon('plus') ?><span class="button-label">Registrar cuentas existentes</span></button>
            </form>
        </div></details>
        <details class="card gs-form-panel shadow-sm" id="nueva-cuenta" data-form-panel <?= ($_GET['nuevo'] ?? '') === '1' || ($_SESSION['_old_action'] ?? '') === '/chatgpt' ? 'open' : '' ?>>
            <summary>Nueva cuenta ChatGPT</summary>
            <div class="card-body">
            <form method="post" action="<?= e(url('/chatgpt')) ?>" autocomplete="off" data-chatgpt-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="token_operacion" value="<?= e((string) ($creationToken ?? '')) ?>">
                <div class="mb-3">
                    <label class="form-label" for="etiqueta">Nombre interno</label>
                    <input type="text" class="form-control" id="etiqueta" name="etiqueta" value="<?= e(old('etiqueta')) ?>" maxlength="100" placeholder="ChatGPT 01" required>
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
                <p class="small text-secondary">Pago nominal: USD 20. Cada pago inicia un mes calendario pagado y un mes calendario de gracia.</p>
                <div class="mb-3">
                    <label class="form-label" for="capacidad">Capacidad en dispositivos</label>
                    <input type="number" class="form-control" id="capacidad" name="capacidad" value="<?= e(old('capacidad', '20')) ?>" min="1" max="99999" step="1" required>
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
