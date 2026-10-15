<?php declare(strict_types=1); $settings = $avisosChatgpt['config']; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h1 class="h3 mb-1">Vencimientos de ChatGPT</h1><p class="text-secondary mb-0">Las cuentas más cercanas a cada fecha aparecen primero.</p></div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/chatgpt')) ?>"><?= icon('arrow-left') ?><span class="button-label">Volver a las cuentas</span></a>
</div>
<p class="small text-secondary gs-deadline-instructions">Al terminar el mes pagado: cerrar sesiones. Al terminar la gracia: cerrar sesiones y pagar. El cierre se realiza en la cuenta ChatGPT; después puedes confirmar el nuevo ciclo en este sistema.</p>
<?php if ($canAdmin): ?>
<details class="card gs-form-panel mb-3" id="configurar-avisos" data-form-panel <?= ($_SESSION['_old_action'] ?? '') === '/chatgpt/avisos' ? 'open' : '' ?>>
    <summary>Configurar anticipación de los avisos</summary><div class="card-body">
    <form method="post" action="<?= e(url('/chatgpt/avisos')) ?>" class="row g-3 align-items-end">
        <?= csrf_field() ?><input type="hidden" name="token_operacion" value="<?= e($alertsToken) ?>">
        <div class="col-md-4"><label class="form-label" for="aviso-fin-pagado">Días antes del fin del mes pagado</label><input class="form-control" id="aviso-fin-pagado" type="number" name="aviso_fin_pagado" min="0" max="60" step="1" value="<?= e(form_value('/chatgpt/avisos', 'aviso_fin_pagado', (string) $settings['aviso_fin_pagado'])) ?>" required></div>
        <div class="col-md-4"><label class="form-label" for="aviso-fin-gracia">Días antes del fin de la gracia</label><input class="form-control" id="aviso-fin-gracia" type="number" name="aviso_fin_gracia" min="0" max="60" step="1" value="<?= e(form_value('/chatgpt/avisos', 'aviso_fin_gracia', (string) $settings['aviso_fin_gracia'])) ?>" required></div>
        <div class="col-md-4"><button class="btn btn-primary w-100" type="submit"><?= icon('save') ?><span class="button-label">Guardar avisos</span></button></div>
        <p class="small text-secondary mb-0">De 0 a 60 días. Con 0, el aviso aparece el mismo día. Los avisos se muestran en el panel y en las cuentas ChatGPT.</p>
    </form></div>
</details>
<?php endif; ?>
<div class="row g-3">
<?php foreach (['pagado' => ['Fin del mes pagado', 'Cerrar sesiones al llegar esta fecha.', 'aviso_fin_pagado', 'urgentes_pagado'], 'gracia' => ['Fin del mes de gracia', 'Cerrar sesiones y registrar el pago al llegar esta fecha.', 'aviso_fin_gracia', 'urgentes_gracia']] as $kind => [$title, $instruction, $configKey, $countKey]): ?>
<section class="col-xl-6" aria-labelledby="titulo-<?= $kind ?>"><div class="card h-100">
    <div class="card-body border-bottom flex-grow-0"><h2 class="h5 mb-1" id="titulo-<?= $kind ?>"><?= e($title) ?></h2><p class="small mb-1"><?= e($instruction) ?></p><p class="small text-secondary mb-0"><?= (int) $avisosChatgpt[$countKey] ?> <?= (int) $avisosChatgpt[$countKey] === 1 ? 'aviso cercano' : 'avisos cercanos' ?> · Anticipación: <?= (int) $settings[$configKey] ?> días</p></div>
    <div class="table-responsive"><table class="table table-striped align-middle mb-0" data-responsive-cards>
        <thead><tr><th>Cuenta</th><th>Fecha</th><th>Faltan</th><th>Acciones</th></tr></thead><tbody>
        <?php if ($avisosChatgpt[$kind] === []): ?><tr><td colspan="4" class="text-secondary p-3">No hay próximas fechas confirmadas para este período.</td></tr><?php endif; ?>
        <?php foreach ($avisosChatgpt[$kind] as $account): ?>
        <tr>
            <td><a class="fw-semibold" href="<?= e(url('/chatgpt/' . (int) $account['id'])) ?>"><?= e($account['etiqueta']) ?></a><small class="text-secondary d-block text-break"><?= e($account['correo']) ?></small><?php if ((int) $account['plan_gratis'] === 1): ?><small class="d-block text-danger">Plan marcado Gratis</small><?php endif; ?></td>
            <td><span class="text-nowrap"><?= e(display_date($account['fecha_aviso'])) ?></span></td>
            <td><span class="badge <?= $account['urgente'] ? ($kind === 'gracia' && $account['dias'] === 0 ? 'text-bg-danger' : 'text-bg-warning') : 'text-bg-secondary' ?>"><?= $account['dias'] === 0 ? 'Hoy' : ((int) $account['dias'] . ($account['dias'] === 1 ? ' día' : ' días')) ?></span><?php if ($account['dias'] === 0): ?><small class="d-block text-secondary mt-1"><?= $kind === 'pagado' ? 'La gracia comienza hoy.' : 'El período ya pasó a Gratis.' ?></small><?php endif; ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/chatgpt/' . (int) $account['id']) . '#nuevo-ciclo') ?>">Revisar cierre</a><?php if ($kind === 'gracia' && $canAdmin): ?><a class="btn btn-sm btn-outline-primary mt-1" href="<?= e(url('/chatgpt/' . (int) $account['id']) . '#registrar-pago') ?>">Registrar pago</a><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody></table></div>
</div></section>
<?php endforeach; ?>
</div>
<details class="card mt-3 gs-form-panel" id="gracia-finalizada" <?= $avisosChatgpt['vencidas'] !== [] ? 'open' : '' ?>>
    <summary>Gracia finalizada (<?= count($avisosChatgpt['vencidas']) ?>)</summary><div class="card-body">
        <p class="small text-secondary">Estas cuentas requieren un pago para volver a vender. Revisa también el cierre de sesiones.</p>
        <?php if ($avisosChatgpt['vencidas'] === []): ?><p class="text-secondary mb-0">No hay cuentas activas con la gracia finalizada.</p><?php endif; ?>
        <?php foreach ($avisosChatgpt['vencidas'] as $account): ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 border-top">
            <div><a class="fw-semibold" href="<?= e(url('/chatgpt/' . (int) $account['id'])) ?>"><?= e($account['etiqueta']) ?></a><div class="small text-secondary">Finalizó el <?= e(display_date($account['fecha_aviso'])) ?> · Hace <?= abs((int) $account['dias']) ?> <?= abs((int) $account['dias']) === 1 ? 'día' : 'días' ?></div></div>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/chatgpt/' . (int) $account['id']) . ($canAdmin ? '#registrar-pago' : '')) ?>"><?= $canAdmin ? 'Revisar pago y cierre' : 'Revisar cuenta' ?></a>
        </div>
        <?php endforeach; ?>
    </div>
</details>
<p class="small text-secondary mt-3"><?= (int) $avisosChatgpt['inactivas'] ?> <?= (int) $avisosChatgpt['inactivas'] === 1 ? 'cuenta inactiva' : 'cuentas inactivas' ?> y <?= (int) $avisosChatgpt['fechas_pendientes'] ?> con fechas pendientes o incompletas quedan fuera de estas listas. Se conservan en <a href="<?= e(url('/chatgpt')) ?>">Cuentas ChatGPT</a>.</p>
