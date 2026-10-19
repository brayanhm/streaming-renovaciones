<?php
declare(strict_types=1);
$canAdmin = $canAdmin ?? false;
$period = (string) ($cuenta['estado_periodo'] ?? 'SIN_PAGO');
$ready = $cuenta !== null && $credentialsReady && (int) $cuenta['activo'] === 1
    && !empty($cuenta['distribucion_actual']) && in_array($period, ['PAGADO', 'GRACIA'], true);
$occupied = (int) ($cuenta['ocupados'] ?? 0);
$capacity = (int) ($cuenta['capacidad'] ?? 0);
$periodLabels = ['PAGADO' => 'Pagada', 'GRACIA' => 'En gracia', 'GRATIS' => 'Gratis', 'SIN_PAGO' => 'Vigencia pendiente'];
$blockedMessage = $cuenta === null ? 'El administrador debe seleccionar una cuenta preparada para distribución.'
    : ((int) $cuenta['activo'] !== 1 ? 'La cuenta seleccionada está inactiva. El administrador debe activarla o elegir otra.'
    : (!$credentialsReady ? 'Completa el correo y la contraseña de la cuenta antes de entregar accesos.'
    : (!in_array($period, ['PAGADO', 'GRACIA'], true) ? 'La cuenta está Gratis o sin vigencia confirmada. Las entregas están bloqueadas.'
    : (empty($cuenta['distribucion_actual']) ? 'La cuenta de distribución cambió. Recarga esta pantalla.'
    : 'Capacidad de referencia alcanzada. Puedes seguir entregando en esta cuenta hasta que el administrador seleccione otra.'))));
$lastDelivery = $lastDelivery ?? null;
$scriptVersion = (string) filemtime(PUBLIC_PATH . '/js/distribucion.js');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 gs-delivery-heading">
    <div>
        <h1 class="h3 mb-1">Distribución de <?= e($serviceLabel) ?></h1>
        <p class="text-secondary mb-0 d-none d-lg-block">Entrega accesos desde la cuenta seleccionada.</p>
    </div>
    <a href="<?= e(url('/' . $service)) ?>" class="btn btn-outline-secondary" aria-label="Ver cuentas" title="Ver cuentas"><?= icon('layers') ?><span class="button-label d-none d-sm-inline">Ver cuentas</span></a>
</div>

<?php if ($cuenta === null): ?>
<div class="card"><div class="card-body py-4">
    <h2 class="h5">Sin cuenta en distribución</h2>
    <p class="text-secondary mb-0"><?= e($blockedMessage) ?></p>
</div></div>
<?php else: ?>
<section class="card mb-3 gs-delivery-card" aria-labelledby="delivery-account-title"><div class="card-body">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4 gs-delivery-account">
        <div>
            <h2 class="h4 mb-1" id="delivery-account-title"><?= e((string) $cuenta['etiqueta']) ?></h2>
            <p class="text-secondary text-break mb-2"><?= e((string) $cuenta['correo']) ?></p>
            <span class="badge text-bg-primary" data-distribution-state><?= e((string) $cuenta['estado_distribucion']) ?></span>
            <span class="badge text-bg-<?= $period === 'PAGADO' ? 'success' : ($period === 'GRACIA' ? 'warning' : 'secondary') ?>"><?= e($periodLabels[$period] ?? $period) ?></span>
        </div>
    </div>
    <div class="row g-3 mb-4 gs-delivery-stats">
        <div class="col-4"><div class="small text-secondary">Referencia</div><strong class="h3" data-distribution-capacity><?= $capacity ?></strong></div>
        <div class="col-4"><div class="small text-secondary">Ocupados</div><strong class="h3" data-distribution-occupied><?= $occupied ?></strong></div>
        <div class="col-4"><div class="small text-secondary">Hasta referencia</div><strong class="h3 text-primary" data-distribution-remaining><?= max(0, $capacity - $occupied) ?></strong></div>
    </div>
    <div class="alert alert-warning small" data-distribution-notice <?= $ready && $occupied < $capacity ? 'hidden' : '' ?>><?= e($blockedMessage) ?></div>
    <details class="gs-new-delivery" data-new-delivery <?= $lastDelivery === null ? 'open' : '' ?>>
    <summary class="fw-semibold mb-3">Nueva entrega</summary>
    <form method="post" action="<?= e(url('/' . $service . '/' . (int) $cuenta['id'] . '/entregar')) ?>" data-distribution-delivery
        data-unit="<?= e($unit) ?>" data-occupied="<?= $occupied ?>" data-capacity="<?= $capacity ?>"
        data-eligible="<?= $ready ? '1' : '0' ?>">
        <?= csrf_field() ?><input type="hidden" name="token_operacion" value="<?= e($deliveryToken) ?>">
        <div class="row g-3">
        <?php foreach ($prices as $devices => $price): ?>
            <div class="<?= count($prices) > 1 ? 'col-sm-6' : 'col-sm-8 col-lg-6' ?>">
                <button type="submit" name="dispositivos" value="<?= (int) $devices ?>" class="btn <?= $devices === 1 ? 'btn-primary' : 'btn-outline-primary' ?> w-100"
                    <?= !$ready ? 'disabled' : '' ?>>
                    <?= icon('copy') ?><span class="button-label">Registrar y copiar · <?= (int) $devices ?> <?= $devices === 1 ? 'dispositivo' : 'dispositivos' ?></span>
                </button>
                <small class="d-block text-secondary mt-2"><?= (int) $devices ?> <?= $devices === 1 ? 'cupo ocupado' : 'cupos ocupados' ?> · <?= e(display_money($price)) ?></small>
            </div>
        <?php endforeach; ?>
        </div>
    </form>
    <p class="small text-secondary mt-3 mb-0">Cada entrega registra la venta y ocupa los <?= e($unit) ?> indicados. «Volver a copiar» conserva la ocupación.</p>
    </details>
    <details class="gs-delivery-details mt-3" data-desktop-open open>
        <summary class="small text-secondary">Fechas<?= $canAdmin ? ' y administración' : ' de distribución' ?></summary>
        <?php $fechasDistribucion = $cuenta; require __DIR__ . '/fechas_distribucion.php'; ?>
        <?php if ($canAdmin): ?><a href="<?= e(url('/' . $service . '/' . (int) $cuenta['id'])) ?>" class="btn btn-outline-primary btn-sm mt-2"><?= icon('square-pen') ?><span class="button-label">Administrar cuenta</span></a><?php endif; ?>
    </details>
    <noscript><p class="text-danger mt-3 mb-0">Activa JavaScript para registrar y copiar las entregas desde esta pantalla.</p></noscript>
    <p class="small mt-3 mb-0" role="status" aria-live="polite" data-distribution-status></p>
</div></section>
<section class="card gs-delivery-result" data-distribution-output <?= $lastDelivery === null ? 'hidden' : '' ?> aria-labelledby="delivery-text-title"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 mb-0" id="delivery-text-title">Última entrega registrada</h2>
        <button type="button" class="btn btn-primary" data-distribution-copy-again><?= icon('copy') ?><span class="button-label">Volver a copiar esta entrega</span></button>
    </div>
    <label for="distribution-client-text" class="form-label small text-secondary">Texto para enviar al cliente</label>
    <textarea id="distribution-client-text" class="form-control" rows="8" readonly><?= e((string) ($lastDelivery['text'] ?? '')) ?></textarea>
    <p class="small text-secondary mt-2 mb-0">Volver a copiar esta entrega conserva la ocupación actual.</p>
</div></section>
<?php endif; ?>
<script src="<?= e(url('/js/distribucion.js') . '?v=' . $scriptVersion) ?>" defer></script>
