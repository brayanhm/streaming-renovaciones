<?php
declare(strict_types=1);
$selected = (bool) ($cuenta['distribucion_actual'] ?? false);
$ready = $isActive && $credentialsReady && in_array($period, ['PAGADO', 'GRACIA'], true);
$saleBlockedMessage = !$isActive ? 'La cuenta está inactiva. El administrador debe activarla.'
    : (!$credentialsReady ? 'Completa el correo y la contraseña antes de vender.'
    : (!in_array($period, ['PAGADO', 'GRACIA'], true) ? 'La cuenta está Gratis o con fechas pendientes. Revisa el plan antes de vender.'
    : (!$selected ? 'Esta cuenta no está seleccionada para distribución. El administrador debe elegirla.'
    : 'Cuenta vendida: todos sus cupos están ocupados. Solo el administrador puede registrar ventas adicionales.')));
$serviceModel = $service === 'chatgpt' ? \App\Models\ChatgptCuenta::class : \App\Models\ClaudeCuenta::class;
$restoreAllowed = in_array($serviceModel::period(array_replace($cuenta, ['plan_gratis' => 0]))['estado_periodo'], ['PAGADO', 'GRACIA'], true);
?>
<section class="card border-primary mb-3" aria-labelledby="distribution-title"><div class="card-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2 class="h5 mb-2" id="distribution-title">Distribución</h2>
            <span class="badge text-bg-<?= $occupied >= $recommended ? 'success' : ($selected ? 'primary' : 'secondary') ?>"><?= e((string) ($cuenta['estado_distribucion'] ?? 'Disponible')) ?></span>
            <?php if ($selected): ?><span class="badge text-bg-primary">Cuenta seleccionada</span><?php endif; ?>
            <p class="small text-secondary mb-0 mt-2"><?= $selected ? ($ready ? 'Las nuevas ventas de este servicio se registran en esta cuenta.' : 'Esta cuenta sigue seleccionada, pero las ventas están bloqueadas.') : 'Seleccionarla conserva los cupos y accesos vendidos en todas las cuentas.' ?></p>
        </div>
        <?php if ($canAdmin && !$selected): ?>
        <form method="post" action="<?= e(url('/' . $service . '/' . $accountId . '/distribuir')) ?>" data-chatgpt-submit>
            <?= csrf_field() ?><input type="hidden" name="token_operacion" value="<?= e((string) ($selectionToken ?? '')) ?>">
            <button class="btn btn-primary" type="submit" <?= !$ready ? 'disabled' : '' ?>><?= icon('circle-check') ?><span class="button-label">Seleccionar para distribución</span></button>
        </form>
        <?php endif; ?>
    </div>
    <?php if ($canAdmin): ?>
    <hr>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <p class="small text-secondary mb-0">Si la cuenta pierde su plan de pago, márcala Gratis para bloquear las ventas.</p>
        <form method="post" action="<?= e(url('/' . $service . '/' . $accountId . '/plan')) ?>" data-chatgpt-submit
            <?php if ($period !== 'GRATIS'): ?>data-confirm="<?= e('Se bloquearán las nuevas entregas de ' . $cuenta['etiqueta'] . '. Sus cupos e historial se conservan.') ?>" data-confirm-title="¿Marcar esta cuenta Gratis?" data-confirm-button="Marcar Gratis" data-confirm-danger="1"<?php endif; ?>>
            <?= csrf_field() ?><input type="hidden" name="token_operacion" value="<?= e((string) ($planToken ?? '')) ?>">
            <input type="hidden" name="plan" value="<?= $period === 'GRATIS' ? 'RESTAURAR' : 'GRATIS' ?>">
            <button class="btn btn-sm btn-outline-secondary" type="submit" <?= $period === 'GRATIS' && !$restoreAllowed ? 'disabled' : '' ?>><?= icon($period === 'GRATIS' ? 'refresh-cw' : 'circle-x') ?><span class="button-label"><?= $period === 'GRATIS' ? 'Restaurar plan vigente' : 'Marcar Gratis' ?></span></button>
        </form>
    </div>
    <?php if ($period === 'GRATIS' && !$restoreAllowed): ?><p class="small text-secondary mt-2 mb-0">Registra un nuevo pago para recuperar la vigencia.</p><?php endif; ?>
    <details class="mt-3" id="nuevo-ciclo">
        <summary class="fw-semibold">Iniciar nuevo ciclo</summary>
        <p class="small mt-2 mb-2">Reinicia los <?= $occupied ?> <?= e($unit) ?> ocupados y selecciona esta cuenta para distribución. Las ventas y los pagos se conservan en el historial.</p>
        <form method="post" action="<?= e(url('/' . $service . '/' . $accountId . '/ciclo')) ?>" data-chatgpt-submit
            data-confirm="<?= e('Se reiniciarán los ' . $occupied . ' ' . $unit . ' ocupados de ' . $cuenta['etiqueta'] . '. Las ventas y los pagos se conservan en el historial.') ?>"
            data-confirm-title="¿Iniciar un nuevo ciclo?" data-confirm-button="Iniciar ciclo" data-confirm-danger="1">
            <?= csrf_field() ?><input type="hidden" name="token_operacion" value="<?= e((string) ($cycleToken ?? '')) ?>">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="sesiones_cerradas" value="1" id="sessions-closed" required <?= !$ready ? 'disabled' : '' ?>>
                <label class="form-check-label" for="sessions-closed">Confirmo que cerré las sesiones en todos los dispositivos.</label>
            </div>
            <button class="btn btn-outline-danger" type="submit" <?= !$ready ? 'disabled' : '' ?>><?= icon('refresh-cw') ?><span class="button-label">Confirmar nuevo ciclo</span></button>
            <?php if (!$ready): ?><p class="small text-secondary mt-2 mb-0">Completa las credenciales y habilita una vigencia pagada o en gracia antes de iniciar el ciclo.</p><?php endif; ?>
        </form>
    </details>
    <?php if (!empty($ciclos)): ?>
    <details class="mt-3">
        <summary>Historial de ciclos (<?= count($ciclos) ?>)</summary>
        <div class="table-responsive mt-2"><table class="table table-sm mb-0">
            <thead><tr><th>Inicio</th><th>Cupos reiniciados</th><th>Administrador</th></tr></thead>
            <tbody><?php foreach ($ciclos as $cycle): ?><tr><td><?= e((new DateTimeImmutable($cycle['created_at']))->format('d/m/Y H:i')) ?></td><td><?= (int) $cycle['cupos_liberados'] ?></td><td><?= e((string) ($cycle['usuario'] ?? '')) ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
    </details>
    <?php endif; ?>
    <?php endif; ?>
</div></section>
