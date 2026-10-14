<?php
declare(strict_types=1);

$canAdmin = $canAdmin ?? false;
$q = $q ?? $search ?? '';
$summary = $summary ?? [];
$chatgptDate = static fn (?string $value): string => $value ? (new DateTimeImmutable($value))->format('d/m/Y') : '—';
$chatgptMoney = static fn (mixed $value): string => number_format((float) $value, 2, ',', '.');
$periodLabels = ['PAGADO' => 'Mes pagado', 'GRACIA' => 'Mes de gracia', 'VENCIDO' => 'Vigencia finalizada', 'SIN_PAGO' => 'Fechas pendientes de revisión'];
$periodColors = ['PAGADO' => 'success', 'GRACIA' => 'warning', 'VENCIDO' => 'danger', 'SIN_PAGO' => 'secondary'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">ChatGPT</h1>
        <p class="text-secondary mb-0">Cuentas principales, pagos y ventas por dispositivos.</p>
    </div>
    <?php if ($canAdmin): ?><a href="<?= e(url('/chatgpt/plantillas')) ?>" class="btn btn-outline-primary">Textos de entrega y renovación</a><?php endif; ?>
</div>

<div class="row row-cols-2 row-cols-lg-5 g-3 mb-4">
    <?php foreach ([['Cuentas', (int) ($summary['cuentas'] ?? 0)], ['Dispositivos ocupados', (int) ($summary['ocupados'] ?? 0)], ['Hasta lo recomendado', max(0, (int) ($summary['hasta_recomendado'] ?? $summary['libres'] ?? 0))], ['Ventas históricas', 'Bs ' . $chatgptMoney($summary['ventas_bs'] ?? 0)], ['Pagos históricos', 'Bs ' . $chatgptMoney($summary['pagos_bs'] ?? 0)]] as [$label, $value]): ?>
        <div class="col"><div class="card shadow-sm h-100"><div class="card-body">
            <div class="small text-secondary mb-1"><?= e($label) ?></div>
            <div class="h4 mb-0"><?= e((string) $value) ?></div>
        </div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="<?= $canAdmin ? 'col-xl-8' : 'col-12' ?>">
        <form method="get" action="<?= e(url('/chatgpt')) ?>" class="d-flex gap-2 mb-3" role="search">
            <label class="visually-hidden" for="chatgpt-search">Buscar cuenta</label>
            <input type="search" class="form-control" id="chatgpt-search" name="q" value="<?= e((string) ($q ?? '')) ?>" placeholder="Buscar por correo o nombre de cuenta">
            <button type="submit" class="btn btn-outline-primary">Buscar</button>
            <?php if (($q ?? '') !== ''): ?><a href="<?= e(url('/chatgpt')) ?>" class="btn btn-outline-secondary">Limpiar</a><?php endif; ?>
        </form>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
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
                                        <?php if ((int) ($row['activo'] ?? 1) !== 1): ?><span class="badge text-bg-secondary mt-1">Inactiva</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-<?= e($periodColors[$period] ?? 'secondary') ?>"><?= e($periodLabels[$period] ?? $period) ?></span>
                                        <small class="d-block text-secondary mt-1">Pagado hasta <?= e($chatgptDate($row['fecha_vencimiento'] ?? null)) ?></small>
                                        <small class="d-block text-secondary">Gracia hasta <?= e($chatgptDate($row['fin_gracia'] ?? null)) ?></small>
                                        <?php if ($period === 'PAGADO'): ?><small class="text-success">En <?= (int) ($row['dias_pagado'] ?? 0) ?> días comienza la gracia</small><?php elseif ($period === 'GRACIA'): ?><small class="text-warning">Gracia: <?= (int) ($row['dias_gracia'] ?? 0) ?> días restantes</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="<?= $exceeded > 0 ? 'text-danger' : ($remaining === 0 ? 'text-warning' : '') ?>"><?= $occupied ?> ocupados</strong>
                                        <small class="d-block text-secondary">Cantidad recomendada: <?= $recommended ?></small>
                                        <small class="d-block text-secondary">Hasta lo recomendado: <?= $remaining ?></small>
                                        <?php if ($exceeded > 0): ?><span class="badge text-bg-danger mt-1">+<?= $exceeded ?> sobre lo recomendado</span><?php elseif ($remaining === 0): ?><span class="badge text-bg-warning mt-1">Cantidad recomendada alcanzada</span><?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap"><?php if (($row['ultimo_costo_bs'] ?? null) !== null): ?>Bs <?= e($chatgptMoney($row['ultimo_costo_bs'])) ?><?php else: ?><span class="text-secondary">Sin registrar</span><?php endif; ?></td>
                                    <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="<?= e(url('/chatgpt/' . (int) $row['id'])) ?>">Abrir</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <p class="small text-secondary mt-2 mb-0">Se recomiendan hasta 20 dispositivos por cuenta. Puedes seguir registrando ventas por encima de esa cantidad. Una venta de 2 dispositivos suma 2 al contador.</p>
    </div>
    <?php if ($canAdmin): ?>
    <div class="col-xl-4">
        <div class="card shadow-sm mb-3"><div class="card-body">
            <h2 class="h5 mb-3">Registrar cuentas existentes</h2>
            <p class="small text-secondary">Quedan pendientes de revisar fechas y credenciales. No registra pagos ni ventas.</p>
            <form method="post" action="<?= e(url('/chatgpt/importar-cuentas')) ?>" data-chatgpt-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="token_operacion" value="<?= e((string) ($importToken ?? '')) ?>">
                <div class="mb-3">
                    <label class="form-label" for="correos_existentes">Correos de las cuentas</label>
                    <textarea class="form-control" id="correos_existentes" name="correos" rows="6" placeholder="Una cuenta por línea" required><?= e(old('correos')) ?></textarea>
                    <small class="text-secondary">Escribe un correo por línea. Luego abre cada cuenta para completar la contraseña y revisar su vigencia.</small>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrar cuentas existentes</button>
            </form>
        </div></div>
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h5 mb-3">Nueva cuenta ChatGPT</h2>
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
                        <button type="button" class="btn btn-outline-secondary" data-password-target="password_cuenta" aria-pressed="false">Mostrar</button>
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
                    <label class="form-label" for="notas">Notas</label>
                    <textarea class="form-control" id="notas" name="notas" rows="2"><?= e(old('notas')) ?></textarea>
                </div>
                <button type="submit" class="btn btn-success w-100">Crear cuenta y registrar pago</button>
            </form>
        </div></div>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/scripts.php'; ?>
