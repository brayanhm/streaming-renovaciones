<?php
declare(strict_types=1);

$accountId = (int) $cuenta['id'];
$service = 'chatgpt';
$unit = 'dispositivos';
$canAdmin = $canAdmin ?? false;
$occupied = (int) ($cuenta['ocupados'] ?? 0);
$recommended = (int) ($cuenta['recomendado'] ?? 20);
$remaining = max(0, $recommended - $occupied);
$exceeded = max(0, $occupied - $recommended);
$occupancyColor = $exceeded > 0 ? 'danger' : ($remaining === 0 ? 'warning' : 'success');
$today = date('Y-m-d');
$period = strtoupper((string) ($cuenta['estado_periodo'] ?? 'SIN_PAGO'));
$periodLabels = ['PAGADO' => 'Mes pagado', 'GRACIA' => 'Mes de gracia', 'GRATIS' => 'Gratis', 'SIN_PAGO' => 'Fechas pendientes de revisión'];
$periodColors = ['PAGADO' => 'success', 'GRACIA' => 'warning', 'GRATIS' => 'secondary', 'SIN_PAGO' => 'secondary'];
$isActive = (int) ($cuenta['activo'] ?? 1) === 1;
$credentialsReady = (bool) ($credentialsReady ?? true);
$canSell = $isActive && $credentialsReady && in_array($period, ['PAGADO', 'GRACIA'], true)
    && !empty($cuenta['distribucion_actual']) && ($canAdmin || $occupied < $recommended);
$credentialsNotice = in_array($period, ['PAGADO', 'GRACIA'], true)
    ? 'Completa la contraseña antes de vender y entregar esta cuenta.'
    : 'Completa la contraseña y confirma las fechas antes de vender y entregar esta cuenta.';
$paidUntil = (string) ($cuenta['fecha_vencimiento'] ?? '');
$graceUntil = (string) ($cuenta['fin_gracia'] ?? '');
$saleEnd = $period === 'GRACIA' ? $graceUntil : $paidUntil;
$chatgptDate = static fn (?string $value): string => $value ? (new DateTimeImmutable($value))->format('d/m/Y') : '—';
$chatgptMoney = static fn (mixed $value): string => number_format((float) $value, 2, ',', '.');
$saleTexts = $credentialsReady ? ($saleTexts ?? []) : [];
$templates = $templates ?? [];
$renewalText = (string) ($renewalText ?? '');
$lastSaleId = (int) ($lastSaleId ?? 0);
$lastSaleText = (string) ($saleTexts[$lastSaleId] ?? '');
$accountNotes = (string) ($cuenta['notas'] ?? '');
if ($accountNotes === 'Cuenta existente: fechas y credenciales pendientes de revisión'
    && $credentialsReady && $period !== 'SIN_PAGO') {
    $accountNotes = 'Cuenta existente precargada.';
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1"><?= e((string) $cuenta['etiqueta']) ?></h1>
        <p class="text-secondary mb-0">ChatGPT · <?= e((string) ($cuenta['correo'] ?? '')) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canAdmin): ?><a href="<?= e(url('/chatgpt/' . $accountId . '/editar')) ?>" class="btn btn-outline-primary"><?= icon('square-pen') ?><span class="button-label">Editar cuenta</span></a><?php endif; ?>
        <a href="<?= e(url('/chatgpt')) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver</span></a>
    </div>
</div>

<?php require __DIR__ . '/../cuentas_compartidas/distribucion.php'; ?>

<?php if ($lastSaleId > 0): ?>
    <div class="alert alert-success">
        <strong>Venta registrada.</strong> Los dispositivos ya están incluidos en el contador.
        <?php if ($lastSaleText === ''): ?><span class="d-block"><?= !$credentialsReady ? 'Credenciales pendientes de completar. Completa la contraseña y confirma las fechas antes de entregar esta cuenta.' : 'Mensaje pendiente de configurar. Podrás copiarlo desde el historial cuando guardes el texto de entrega.' ?></span><?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!$credentialsReady): ?>
    <div class="alert alert-warning">
        <?= e($credentialsNotice) ?>
        <?php if ($canAdmin): ?><a href="<?= e(url('/chatgpt/' . $accountId . '/editar')) ?>" class="alert-link">Completar credenciales</a><?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($occupied >= $recommended): ?>
    <div class="alert alert-warning">La cuenta está llena. Solo el administrador puede registrar ventas adicionales en la cuenta seleccionada.
        <?php if ($exceeded > 0): ?><span class="badge text-bg-warning ms-1">+<?= $exceeded ?> adicionales</span><?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6 text-secondary">Dispositivos</h2>
        <div class="h3 mb-1 text-<?= $occupancyColor ?>"><?= $occupied ?> <span class="h5 text-secondary">ocupados</span></div>
        <div class="small text-secondary">Capacidad: <?= $recommended ?></div>
        <div class="mb-2">Libres: <?= $remaining ?></div>
        <div class="progress mb-2" role="progressbar" aria-label="Dispositivos ocupados respecto a su capacidad" aria-valuenow="<?= min($recommended, max(0, $occupied)) ?>" aria-valuemin="0" aria-valuemax="<?= $recommended ?>"><div class="progress-bar bg-<?= $occupancyColor ?>" style="width: <?= min(100, max(0, $occupied * 100 / max(1, $recommended))) ?>%"></div></div>
        <small class="text-secondary">Los cupos se reinician al confirmar un nuevo ciclo.</small>
    </div></div></div>
    <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6 text-secondary">Pago y vigencia</h2>
        <span class="badge text-bg-<?= e($periodColors[$period] ?? 'secondary') ?> mb-2"><?= e($periodLabels[$period] ?? $period) ?></span>
        <div class="small mb-1"><?php if (!empty($cuenta['fecha_inicio'])): ?>Fecha de pago: <strong><?= e($chatgptDate($cuenta['fecha_inicio'])) ?></strong><?php else: ?><span class="text-secondary">Fecha de pago sin confirmar</span><?php endif; ?></div>
        <div class="small mb-1">Fin del mes pagado: <strong><?= e($chatgptDate($paidUntil)) ?></strong></div>
        <div class="small mb-2">Fin del mes de gracia: <strong><?= e($chatgptDate($graceUntil)) ?></strong></div>
        <?php if ($period === 'PAGADO'): ?><small class="text-success">La gracia comienza en <?= (int) ($cuenta['dias_pagado'] ?? 0) ?> días.</small><?php elseif ($period === 'GRACIA'): ?><small class="text-warning">Quedan <?= (int) ($cuenta['dias_gracia'] ?? 0) ?> días de gracia.</small><?php elseif ($period === 'GRATIS'): ?><small class="text-danger">La vigencia terminó. Registra un pago para habilitar nuevas ventas.</small><?php endif; ?>
    </div></div></div>
    <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6 text-secondary">Totales históricos</h2>
        <div class="mb-2">Ventas: <strong>Bs <?= e($chatgptMoney($cuenta['ventas_bs'] ?? 0)) ?></strong></div>
        <div class="mb-2">Pagos: <strong>Bs <?= e($chatgptMoney($cuenta['pagos_bs'] ?? 0)) ?></strong></div>
        <span class="badge text-bg-<?= $isActive ? 'success' : 'secondary' ?>"><?= $isActive ? 'Cuenta activa' : 'Cuenta inactiva' ?></span>
        <?php if ($canAdmin): ?>
            <form method="post" action="<?= e(url('/chatgpt/' . $accountId . '/estado')) ?>" class="mt-2" data-chatgpt-submit>
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
            <?php if ($accountNotes !== ''): ?><p class="small text-secondary mt-3 mb-0"><?= nl2br(e($accountNotes)) ?></p><?php endif; ?>
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
                <form method="post" action="<?= e(url('/chatgpt/' . $accountId . '/vender')) ?>" data-chatgpt-submit data-chatgpt-sale data-paid-until="<?= e($paidUntil) ?>" data-grace-until="<?= e($graceUntil) ?>" data-occupied="<?= $occupied ?>" data-recommended="<?= $recommended ?>" data-can-exceed="<?= $canAdmin ? '1' : '0' ?>">
                    <?= csrf_field() ?><input type="hidden" name="token_operacion" value="<?= e((string) ($saleToken ?? '')) ?>">
                    <fieldset class="mb-3">
                        <legend class="form-label fs-6">Dispositivos</legend>
                        <div class="form-check mb-2"><input class="form-check-input" type="radio" name="dispositivos" id="sale-one" value="1" checked><label class="form-check-label" for="sale-one">1 dispositivo · <strong>30 Bs</strong></label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="dispositivos" id="sale-two" value="2" <?= !$canAdmin && $remaining < 2 ? 'disabled' : '' ?>><label class="form-check-label" for="sale-two">2 dispositivos juntos · <strong>55 Bs</strong></label></div>
                    </fieldset>
                    <div class="mb-3"><label class="form-label" for="fecha_venta">Fecha de venta</label><input type="date" class="form-control" id="fecha_venta" name="fecha_venta" value="<?= e($today) ?>" max="<?= e($today) ?>" required></div>
                    <p class="small text-secondary">El acceso vence el <strong data-sale-expiry><?= e($chatgptDate($saleEnd)) ?></strong>. Esta venta suma <strong data-sale-devices>1</strong> <span data-sale-device-unit>dispositivo</span> al contador. Capacidad: <?= $recommended ?> dispositivos por cuenta.</p>
                    <div class="alert alert-warning small" data-sale-recommendation-warning role="status" aria-live="polite" <?= $occupied + 1 <= $recommended ? 'hidden' : '' ?>>Con esta venta habrá <?= $occupied + 1 ?> dispositivos ocupados: <?= max(0, $occupied + 1 - $recommended) ?> por encima de la capacidad de <?= $recommended ?>. Venta adicional autorizada para el administrador.</div>
                    <p class="small text-secondary mb-2" data-sale-template-status><?= trim((string) ($templates['entrega_1'] ?? '')) === '' ? 'Mensaje pendiente de configurar. La venta se registrará y podrás copiarlo después.' : 'Se preparará el texto de entrega al registrar la venta.' ?></p>
                    <button type="submit" class="btn btn-success w-100" data-sale-button><?= icon('plus') ?><span class="button-label"><?= trim((string) ($templates['entrega_1'] ?? '')) === '' ? 'Registrar venta' : 'Registrar venta y copiar' ?></span></button>
                    <input type="hidden" data-template-one value="<?= trim((string) ($templates['entrega_1'] ?? '')) !== '' ? '1' : '0' ?>">
                    <input type="hidden" data-template-two value="<?= trim((string) ($templates['entrega_2'] ?? '')) !== '' ? '1' : '0' ?>">
                </form>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<?php if ($canAdmin): ?>
<div class="card shadow-sm mb-3" id="review-existing-period"><div class="card-body">
    <h2 class="h5 mb-2">Revisar vigencia existente</h2>
    <p class="small text-secondary">Confirma una fecha que conozcas. La fecha del pago inicia un mes calendario pagado y luego un mes de gracia. Si conoces el inicio de la gracia, ese mes de gracia comienza en la fecha indicada; la fecha original del pago queda sin confirmar.</p>
    <p class="small text-secondary">Esta revisión actualiza la vigencia sin registrar pagos ni modificar los dispositivos ocupados.</p>
    <?php if (!$restoreAllowed && (int) ($cuenta['revision_vigencia_pendiente'] ?? 0) !== 1): ?>
    <p class="text-secondary mb-0">La vigencia terminó. Registra un nuevo pago para comenzar otro período.</p>
    <?php else: ?>
    <form method="post" action="<?= e(url('/chatgpt/' . $accountId . '/vigencia')) ?>" class="row g-2 align-items-end" data-chatgpt-submit>
        <?= csrf_field() ?><input type="hidden" name="token_operacion" value="<?= e((string) ($periodToken ?? '')) ?>">
        <div class="col-md-4">
            <label class="form-label" for="origen_fecha">Fecha que puedes confirmar</label>
            <select class="form-select" id="origen_fecha" name="origen_fecha" required>
                <option value="PAGO" <?= old('origen_fecha', 'PAGO') === 'PAGO' ? 'selected' : '' ?>>Fecha del pago</option>
                <option value="GRACIA" <?= old('origen_fecha', 'PAGO') === 'GRACIA' ? 'selected' : '' ?>>Inicio del mes de gracia</option>
            </select>
        </div>
        <div class="col-md-4"><label class="form-label" for="fecha_referencia">Fecha confirmada</label><input type="date" class="form-control" id="fecha_referencia" name="fecha_referencia" value="<?= e(old('fecha_referencia')) ?>" max="<?= e($today) ?>" required></div>
        <div class="col-md-4"><button type="submit" class="btn btn-primary w-100"><?= icon('save') ?><span class="button-label">Guardar vigencia revisada</span></button></div>
    </form>
    <?php endif; ?>
</div></div>
<div class="row g-3 mb-3">
    <div class="col-12"><div class="card shadow-sm"><div class="card-body">
        <h2 class="h5 mb-2">Registrar pago</h2>
        <p class="small text-secondary">USD 20 nominales. El nuevo período inicia en la fecha que registres e incluye un mes pagado y un mes de gracia. Los dispositivos ocupados se conservan.</p>
        <form method="post" action="<?= e(url('/chatgpt/' . $accountId . '/pagar')) ?>" class="row g-2 align-items-end" data-chatgpt-submit>
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
            <table class="table table-striped align-middle mb-0"><thead><tr><th>Venta</th><th>Dispositivos</th><th>Monto</th><th>Vigencia del acceso</th><th>Cupos</th><th>Entrega</th></tr></thead><tbody>
                <?php if (empty($ventas)): ?><tr><td colspan="6" class="text-center text-secondary py-4">Sin ventas registradas.</td></tr><?php endif; ?>
                <?php foreach (($ventas ?? []) as $sale): ?>
                    <?php $saleId = (int) $sale['id']; $saleText = (string) ($saleTexts[$saleId] ?? ''); ?>
                    <tr <?= $saleId === $lastSaleId ? 'class="table-success"' : '' ?>>
                        <td><?= e($chatgptDate($sale['fecha_venta'] ?? null)) ?><small class="d-block text-secondary">#<?= $saleId ?></small></td>
                        <td><?= (int) $sale['dispositivos'] ?></td><td class="text-nowrap">Bs <?= e($chatgptMoney($sale['monto_bs'])) ?></td>
                        <td><?= e($chatgptDate($sale['fecha_vencimiento'] ?? null)) ?></td>
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
            <table class="table table-striped align-middle mb-0"><thead><tr><th>Fecha de pago</th><th>Fin del mes pagado</th><th>Fin de gracia</th><th>USD nominal</th><th>Costo real</th></tr></thead><tbody>
                <?php if (empty($pagos)): ?><tr><td colspan="5" class="text-center text-secondary py-4">Sin pagos registrados.</td></tr><?php endif; ?>
                <?php foreach (($pagos ?? []) as $payment): ?><tr>
                    <td><?= e($chatgptDate($payment['fecha_pago'] ?? null)) ?></td><td><?= e($chatgptDate($payment['fin_pagado'] ?? null)) ?></td><td><?= e($chatgptDate($payment['fin_gracia'] ?? null)) ?></td>
                    <td><?= e(number_format((float) ($payment['monto_usd'] ?? 20), 2, ',', '.')) ?></td><td class="text-nowrap">Bs <?= e($chatgptMoney($payment['monto_bs'])) ?></td>
                </tr><?php endforeach; ?>
            </tbody></table>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/scripts.php'; ?>
