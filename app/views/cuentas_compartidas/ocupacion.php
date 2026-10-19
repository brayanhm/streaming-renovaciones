<?php
declare(strict_types=1);
if (!($canAdmin ?? false)) return;
$occupationUnit = $service === 'chatgpt' ? 'dispositivos' : 'cupos';
$occupationCount = (int) ($cuenta['ocupados'] ?? 0);
?>
<details class="mt-3" id="ocupacion" <?= !empty($occupationExpanded) ? 'open' : '' ?> <?= !empty($occupationStandalone) ? 'data-standalone-occupation' : '' ?>>
    <summary class="fw-semibold">Editar <?= e($occupationUnit) ?> ocupados</summary>
    <form class="mt-3" method="post" action="<?= e(url('/' . $service . '/' . $accountId . '/ocupacion')) ?>"
        data-chatgpt-submit data-occupancy-adjust data-occupation-unit="<?= e($occupationUnit) ?>" data-account-label="<?= e((string) $cuenta['etiqueta']) ?>"
        data-confirm="Se actualizará la ocupación de esta cuenta. Las ventas y los pagos se conservan."
        data-confirm-title="¿Guardar el ajuste de ocupación?" data-confirm-button="Guardar ocupación">
        <?= csrf_field() ?>
        <input type="hidden" name="token_operacion" value="<?= e((string) ($occupationToken ?? '')) ?>">
        <input type="hidden" name="ocupados_antes" value="<?= $occupationCount ?>">
        <label class="form-label" for="occupation-count"><?= $service === 'chatgpt' ? 'Dispositivos ocupados' : 'Cupos ocupados' ?></label>
        <input class="form-control" id="occupation-count" name="ocupados" type="number" min="0" max="99999" step="1" value="<?= $occupationCount ?>" required aria-describedby="occupation-help">
        <p class="small text-secondary mt-2" id="occupation-help">Indica el total ocupado, incluso si supera la capacidad. Las próximas entregas se sumarán a este número. El nuevo ciclo lo reinicia a cero.</p>
        <label class="form-label" for="occupation-reason">Motivo <span class="text-secondary">(opcional)</span></label>
        <input class="form-control mb-3" id="occupation-reason" name="motivo" type="text" maxlength="255" placeholder="Ej.: corrección del contador">
        <button class="btn btn-primary" type="submit"><?= icon('save') ?><span class="button-label">Guardar ocupación</span></button>
        <p class="small text-secondary mt-2 mb-0">El ajuste queda registrado y conserva el historial de ventas y pagos.</p>
    </form>
</details>
<?php if (!empty($occupationAdjustments)): ?>
<details class="mt-3">
    <summary class="small text-secondary">Últimos ajustes de ocupación</summary>
    <ul class="list-unstyled small mb-0 mt-2">
        <?php foreach ($occupationAdjustments as $adjustment): ?>
        <li class="border-top py-2">
            <strong><?= (int) $adjustment['ocupados_antes'] ?> → <?= (int) $adjustment['ocupados_despues'] ?> <?= e($occupationUnit) ?></strong>
            <span class="d-block text-secondary"><?= e(date('d/m/Y H:i', strtotime((string) $adjustment['created_at']))) ?> · <?= e((string) ($adjustment['usuario'] ?? 'Administrador')) ?></span>
            <span class="d-block text-break"><?= e((string) $adjustment['motivo']) ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</details>
<?php endif; ?>
