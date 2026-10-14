<?php
declare(strict_types=1);

$accountId = (int) $cuenta['id'];
$service = 'claude';
$unit = 'cupos';
$canAdmin = $canAdmin ?? false;
$occupied = (int) ($cuenta['ocupados'] ?? 0);
$recommended = (int) ($cuenta['recomendado'] ?? 5);
$remaining = max(0, $recommended - $occupied);
$exceeded = max(0, $occupied - $recommended);
$occupancyColor = $exceeded > 0 ? 'danger' : ($remaining === 0 ? 'warning' : 'success');
$today = date('Y-m-d');
$period = strtoupper((string) ($cuenta['estado_periodo'] ?? 'SIN_PAGO'));
$periodLabels = ['PAGADO' => 'Mes pagado', 'GRATIS' => 'Gratis', 'SIN_PAGO' => 'Sin pago registrado'];
$periodColors = ['PAGADO' => 'success', 'GRATIS' => 'secondary', 'SIN_PAGO' => 'secondary'];
$isActive = (int) ($cuenta['activo'] ?? 1) === 1;
$credentialsReady = $credentialsReady ?? true;
$canSell = $isActive && $credentialsReady && in_array($period, ['PAGADO', 'GRACIA'], true)
    && !empty($cuenta['distribucion_actual']) && ($canAdmin || $occupied < $recommended);
$paidUntil = (string) ($cuenta['fecha_vencimiento'] ?? '');
$saleEnd = $paidUntil;
$claudeDate = static fn (?string $value): string => $value ? (new DateTimeImmutable($value))->format('d/m/Y') : '—';
$claudeMoney = static fn (mixed $value): string => number_format((float) $value, 2, ',', '.');
$saleTexts = $saleTexts ?? [];
$templates = $templates ?? [];
$renewalText = (string) ($renewalText ?? '');
$lastSaleId = (int) ($lastSaleId ?? 0);
$lastSaleText = (string) ($saleTexts[$lastSaleId] ?? '');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1"><?= e((string) $cuenta['etiqueta']) ?></h1>
        <p class="text-secondary mb-0">Claude · <?= e((string) ($cuenta['correo'] ?? '')) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canAdmin): ?><a href="<?= e(url('/claude/' . $accountId . '/editar')) ?>" class="btn btn-outline-primary"><?= icon('square-pen') ?><span class="button-label">Editar cuenta</span></a><?php endif; ?>
        <a href="<?= e(url('/claude')) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver</span></a>
    </div>
</div>

<?php require __DIR__ . '/../cuentas_compartidas/distribucion.php'; ?>

<?php if ($lastSaleId > 0): ?>
    <div class="alert alert-success">
        <strong>Venta registrada.</strong> El cupo ya está incluido en el contador.
        <?php if ($lastSaleText === ''): ?><span class="d-block">Mensaje pendiente de configurar. Podrás copiarlo desde el historial cuando guardes el texto de entrega.</span><?php endif; ?>
    </div>
<?php endif; ?>

<?php if ((int) ($cuenta['ocupados_anteriores'] ?? 0) > 0): ?>
    <div class="alert alert-info">Esta cuenta conserva <?= (int) $cuenta['ocupados_anteriores'] ?> cupos de asignaciones anteriores, incluidos en el contador. Se conservarán hasta confirmar un nuevo ciclo.</div>
<?php endif; ?>
<?php if ($occupied >= $recommended): ?>
    <div class="alert alert-warning">La cuenta está llena. Solo el administrador puede registrar ventas adicionales en la cuenta seleccionada.
        <?php if ($exceeded > 0): ?><span class="badge text-bg-warning ms-1">+<?= $exceeded ?> adicionales</span><?php endif; ?>
    </div>
<?php endif; ?>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6 text-secondary">Cupos</h2>
        <div class="h3 mb-1 text-<?= $occupancyColor ?>"><?= $occupied ?> <span class="h5 text-secondary">ocupados</span></div>
        <div class="small text-secondary">Capacidad: <?= $recommended ?></div>
        <div class="mb-2">Libres: <?= $remaining ?></div>
        <div class="progress mb-2" role="progressbar" aria-label="Cupos ocupados respecto a su capacidad" aria-valuenow="<?= min($recommended, max(0, $occupied)) ?>" aria-valuemin="0" aria-valuemax="<?= $recommended ?>"><div class="progress-bar bg-<?= $occupancyColor ?>" style="width: <?= min(100, max(0, $occupied * 100 / max(1, $recommended))) ?>%"></div></div>
        <small class="text-secondary">Los cupos se reinician al confirmar un nuevo ciclo.</small>
    </div></div></div>
    <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6 text-secondary">Pago y vigencia</h2>
        <span class="badge text-bg-<?= e($periodColors[$period] ?? 'secondary') ?> mb-2"><?= e($periodLabels[$period] ?? $period) ?></span>
        <div class="small mb-1">Último pago: <strong><?= e($claudeDate($cuenta['fecha_inicio'] ?? null)) ?></strong></div>
        <div class="small mb-1">Fin de vigencia: <strong><?= e($claudeDate($paidUntil)) ?></strong></div>
        
        <?php if ($period === 'PAGADO'): ?><small class="text-success">Quedan <?= (int) ($cuenta['dias_pagado'] ?? 0) ?> días de vigencia.</small><?php elseif ($period === 'GRATIS'): ?><small class="text-danger">La vigencia terminó. Registra un pago para habilitar nuevas ventas.</small><?php endif; ?>
    </div></div></div>
    <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6 text-secondary">Totales históricos</h2>
        <div class="mb-2">Ventas: <strong>Bs <?= e($claudeMoney($cuenta['ventas_bs'] ?? 0)) ?></strong></div>
        <div class="mb-2">Pagos: <strong>Bs <?= e($claudeMoney($cuenta['pagos_bs'] ?? 0)) ?></strong></div>
        <span class="badge text-bg-<?= $isActive ? 'success' : 'secondary' ?>"><?= $isActive ? 'Cuenta activa' : 'Cuenta inactiva' ?></span>
        <?php if ($canAdmin): ?>
            <form method="post" action="<?= e(url('/claude/' . $accountId . '/estado')) ?>" class="mt-2" data-chatgpt-submit>
                <?= csrf_field() ?><input type="hidden" name="token_operacion" value="<?= e((string) ($stateToken ?? '')) ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary"><?= icon($isActive ? 'circle-x' : 'circle-check') ?><span class="button-label"><?= $isActive ? 'Desactivar cuenta' : 'Activar cuenta' ?></span></button>
            </form>
        <?php endif; ?>
    </div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-3"><div class="card-body">
            <h2 class="h5 mb-3">Datos de acceso</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="account-email">Correo</label><div class="input-group">
                    <input type="text" class="form-control" id="account-email" value="<?= e((string) ($cuenta['correo'] ?? '')) ?>" readonly>
                    <button type="button" class="btn btn-outline-secondary" data-copy-source="account-email"><?= icon('copy') ?><span class="button-label">Copiar</span></button>
                </div></div>
                <div class="col-md-6"><label class="form-label" for="account-password">Contraseña</label><div class="input-group">
                    <input type="password" class="form-control" id="account-password" value="<?= e((string) ($password ?? '')) ?>" autocomplete="off" readonly>
                    <button type="button" class="btn btn-outline-secondary" data-password-target="account-password" aria-pressed="false"><?= icon('eye') ?><span class="button-label">Mostrar</span></button>
                </div></div>
            </div>
            <?php if (!empty($cuenta['notas'])): ?><p class="small text-secondary mt-3 mb-0"><?= nl2br(e((string) $cuenta['notas'])) ?></p><?php endif; ?>
            <hr>
            <h3 class="h6">Renovación</h3>
            <p class="small text-secondary">Busca este correo en WhatsApp para localizar a quienes adquirieron acceso durante el mes anterior.</p>
            <textarea id="renewal-text" hidden><?= e($renewalText) ?></textarea>
            <button type="button" class="btn btn-outline-primary" data-copy-source="renewal-text" <?= $renewalText === '' ? 'disabled' : '' ?>><?= icon('copy') ?><span class="button-label">Copiar texto de renovación</span></button>
            <?php if ($renewalText === ''): ?><small class="d-block text-secondary mt-2">Texto de renovación pendiente.</small><?php endif; ?>
        </div></div>

        <div class="card shadow-sm" id="chatgpt-copy-box" hidden><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"><h2 class="h6 mb-0">Texto preparado</h2><button type="button" class="btn btn-outline-primary btn-sm" data-copy-source="chatgpt-copy-text"><?= icon('copy') ?><span class="button-label">Copiar de nuevo</span></button></div>
            <p id="chatgpt-copy-status" class="small mb-2" role="status" aria-live="polite"></p>
            <label class="visually-hidden" for="chatgpt-copy-text">Texto para copiar manualmente</label>
            <textarea class="form-control" id="chatgpt-copy-text" rows="6" readonly></textarea>
            <small class="text-secondary d-block mt-2">Volver a copiar conserva el contador de ventas.</small>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h5 mb-3">Nueva venta</h2>
            <?php if (!$canSell): ?>
                <div class="alert alert-warning mb-0"><?= e($saleBlockedMessage) ?></div>
            <?php else: ?>
                <form method="post" action="<?= e(url('/claude/' . $accountId . '/vender')) ?>" data-chatgpt-submit>
                    <?= csrf_field() ?><input type="hidden" name="token_operacion" value="<?= e((string) ($saleToken ?? '')) ?>">
                    <input type="hidden" name="dispositivos" value="1">
                    <p class="mb-3">1 cupo · <strong>Bs 85</strong></p>
                    <div class="mb-3"><label class="form-label" for="fecha_venta">Fecha de venta</label><input type="date" class="form-control" id="fecha_venta" name="fecha_venta" value="<?= e($today) ?>" max="<?= e($today) ?>" required></div>
                    <p class="small text-secondary">El acceso vence el <strong data-sale-expiry><?= e($claudeDate($saleEnd)) ?></strong>. Esta venta suma <strong data-sale-devices>1</strong> cupo al contador. Capacidad: <?= $recommended ?> cupos por cuenta.</p>
                    <?php if ($occupied + 1 > $recommended): ?><div class="alert alert-warning small">Con esta venta habrá <?= $occupied + 1 ?> cupos ocupados: <?= $occupied + 1 - $recommended ?> por encima de la capacidad de <?= $recommended ?>. Venta adicional autorizada para el administrador.</div><?php endif; ?>
                    <p class="small text-secondary mb-2" data-sale-template-status><?= trim((string) ($templates['entrega_1'] ?? '')) === '' ? 'Mensaje pendiente de configurar. La venta se registrará y podrás copiarlo después.' : 'Se preparará el texto de entrega al registrar la venta.' ?></p>
                    <button type="submit" class="btn btn-success w-100"><?= icon('plus') ?><span class="button-label"><?= trim((string) ($templates['entrega_1'] ?? '')) === '' ? 'Vender cupo · Bs 85' : 'Vender y copiar · Bs 85' ?></span></button>


                </form>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<?php if ($canAdmin): ?>
<div class="row g-3 mb-3">
    <div class="col-12"><div class="card shadow-sm"><div class="card-body">
        <h2 class="h5 mb-2">Registrar pago</h2>
        <p class="small text-secondary">El nuevo período inicia en la fecha que registres y dura un mes calendario. Ingresa el costo real en bolivianos. Los cupos ocupados se conservan.</p>
        <form method="post" action="<?= e(url('/claude/' . $accountId . '/pagar')) ?>" class="row g-2 align-items-end" data-chatgpt-submit>
            <?= csrf_field() ?><input type="hidden" name="token_operacion" value="<?= e((string) ($paymentToken ?? '')) ?>">
            <div class="col-sm-4"><label class="form-label" for="fecha_pago">Fecha de pago</label><input type="date" class="form-control" id="fecha_pago" name="fecha_pago" value="<?= e($today) ?>" max="<?= e($today) ?>" required></div>
            <div class="col-sm-4"><label class="form-label" for="monto_bs">Costo real en Bs</label><input type="number" class="form-control" id="monto_bs" name="monto_bs" min="0.01" step="0.01" placeholder="Monto cobrado" required></div>
            <div class="col-sm-4"><button type="submit" class="btn btn-primary w-100"><?= icon('plus') ?><span class="button-label">Registrar pago</span></button></div>
        </form>
    </div></div></div>

</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header"><ul class="nav nav-tabs card-header-tabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales-history" type="button" role="tab" aria-controls="sales-history" aria-selected="true">Ventas (<?= count($ventas ?? []) ?>)</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-history" type="button" role="tab" aria-controls="payments-history" aria-selected="false">Pagos (<?= count($pagos ?? []) ?>)</button></li>
    </ul></div>
    <div class="card-body p-0 tab-content">
        <div class="tab-pane fade show active" id="sales-history" role="tabpanel" aria-labelledby="sales-tab" tabindex="0"><div class="table-responsive">
            <table class="table table-striped align-middle mb-0"><thead><tr><th>Venta</th><th>Cantidad</th><th>Monto</th><th>Vigencia del acceso</th><th>Cupos</th><th>Entrega</th></tr></thead><tbody>
                <?php if (empty($ventas)): ?><tr><td colspan="6" class="text-center text-secondary py-4">Sin ventas registradas.</td></tr><?php endif; ?>
                <?php foreach (($ventas ?? []) as $sale): ?>
                    <?php $saleId = (int) $sale['id']; $saleText = (string) ($saleTexts[$saleId] ?? ''); ?>
                    <tr <?= $saleId === $lastSaleId ? 'class="table-success"' : '' ?>>
                        <td><?= e($claudeDate($sale['fecha_venta'] ?? null)) ?><small class="d-block text-secondary">#<?= $saleId ?></small></td>
                        <td><?= (int) $sale['dispositivos'] ?></td><td class="text-nowrap">Bs <?= e($claudeMoney($sale['monto_bs'])) ?></td>
                        <td><?= e($claudeDate($sale['fecha_vencimiento'] ?? null)) ?></td>
                        <td><?php if (!empty($sale['liberada_at'])): ?><span class="badge text-bg-secondary">Liberados</span><?php else: ?><span class="badge text-bg-primary">Ocupados</span><?php endif; ?></td>
                        <td>
                            <textarea id="sale-text-<?= $saleId ?>" hidden <?= $saleId === $lastSaleId && $saleText !== '' ? 'data-copy-on-load' : '' ?>><?= e($saleText) ?></textarea>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-copy-source="sale-text-<?= $saleId ?>" <?= $saleText === '' ? 'disabled' : '' ?>><?= icon('copy') ?><span class="button-label">Copiar entrega</span></button>
                            <?php if ($saleText === ''): ?><small class="d-block text-secondary"><?= !$credentialsReady ? 'Credenciales pendientes de completar' : 'Mensaje pendiente de configurar' ?></small><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table>
        </div></div>
        <div class="tab-pane fade" id="payments-history" role="tabpanel" aria-labelledby="payments-tab" tabindex="0"><div class="table-responsive">
            <table class="table table-striped align-middle mb-0"><thead><tr><th>Fecha de pago</th><th>Fin de vigencia</th><th>Costo real</th></tr></thead><tbody>
                <?php if (empty($pagos)): ?><tr><td colspan="3" class="text-center text-secondary py-4">Sin pagos registrados.</td></tr><?php endif; ?>
                <?php foreach (($pagos ?? []) as $payment): ?><tr>
                    <td><?= e($claudeDate($payment['fecha_pago'] ?? null)) ?></td><td><?= e($claudeDate($payment['fin_pagado'] ?? null)) ?></td>
                    <td class="text-nowrap">Bs <?= e($claudeMoney($payment['monto_bs'])) ?></td>
                </tr><?php endforeach; ?>
            </tbody></table>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/scripts.php'; ?>
