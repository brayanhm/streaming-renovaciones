<?php declare(strict_types=1); ?>
<?php if (!empty($avisosChatgpt)): ?>
<section class="card mb-3" aria-label="Próximos cierres y pagos de ChatGPT"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h2 class="h6 mb-0">Cierres y pagos de ChatGPT</h2>
        <a href="<?= e(url('/chatgpt/vencimientos')) ?>" class="btn btn-sm btn-outline-primary"><?= icon('calendar-clock') ?><span class="button-label">Ver próximas fechas</span></a>
    </div>
    <div class="row g-2 small">
        <?php foreach (['pagado' => ['Fin del mes pagado', 'urgentes_pagado', 'aviso_fin_pagado'], 'gracia' => ['Fin de la gracia', 'urgentes_gracia', 'aviso_fin_gracia']] as $kind => [$title, $countKey, $configKey]): ?>
        <?php $next = $avisosChatgpt[$kind][0] ?? null; $nearCount = (int) $avisosChatgpt[$countKey]; ?>
        <div class="col-sm-6">
            <strong><?= e($title) ?></strong>
            <span class="badge <?= $nearCount > 0 ? 'text-bg-warning' : 'text-bg-secondary' ?>"><?= $nearCount ?> <?= $nearCount === 1 ? 'aviso' : 'avisos' ?></span>
            <span class="text-secondary"><?= (int) $avisosChatgpt['config'][$configKey] === 0 ? 'hoy' : ('en los próximos ' . (int) $avisosChatgpt['config'][$configKey] . ' días') ?></span>
            <div class="text-secondary"><?php if ($next): ?>Próxima: <?= e($next['etiqueta']) ?> · <?= e(display_date($next['fecha_aviso'])) ?><?php else: ?>Sin fechas próximas confirmadas.<?php endif; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($avisosChatgpt['vencidas'] !== []): ?><p class="small text-danger mt-2 mb-0"><a class="text-danger" href="<?= e(url('/chatgpt/vencimientos') . '#gracia-finalizada') ?>"><?= count($avisosChatgpt['vencidas']) ?> <?= count($avisosChatgpt['vencidas']) === 1 ? 'cuenta con la gracia finalizada' : 'cuentas con la gracia finalizada' ?></a>: revisar pago y cierre de sesiones.</p><?php endif; ?>
</div></section>
<?php endif; ?>
