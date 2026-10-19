<?php
declare(strict_types=1);
$filter = $filter ?? '';
$perPage = $perPage ?? 25;
$accountUrl = static function (array $changes = []) use ($service, $q, $filter, $perPage): string {
    $params = array_replace(['q' => $q, 'filtro' => $filter, 'por_pagina' => $perPage], $changes);
    $params = array_filter($params, static fn ($value): bool => (string) $value !== '');
    return url('/' . $service) . ($params ? '?' . http_build_query($params) : '');
};
?>
<form method="get" action="<?= e(url('/' . $service)) ?>" class="gs-search-form mb-3" role="search">
    <div class="gs-search-field"><label class="form-label" for="<?= e($service) ?>-search">Buscar cuenta</label>
        <input type="search" class="form-control" id="<?= e($service) ?>-search" name="q" value="<?= e((string) $q) ?>" placeholder="Correo o nombre de cuenta"></div>
    <input type="hidden" name="filtro" value="<?= e($filter) ?>">
    <div><label class="form-label" for="<?= e($service) ?>-per-page">Por página</label>
        <select class="form-select" name="por_pagina" id="<?= e($service) ?>-per-page"><?php foreach ([10, 25, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select></div>
    <button type="submit" class="btn btn-outline-primary"><?= icon('search') ?><span class="button-label">Buscar</span></button>
</form>
<nav class="gs-quick-filters mb-2" aria-label="Situación de las cuentas">
    <?php foreach (($filters ?? ['' => 'Todas']) as $key => $label): ?>
        <a class="gs-filter <?= $key === $filter ? 'active' : '' ?>" href="<?= e($accountUrl(['filtro' => $key])) ?>" <?= $key === $filter ? 'aria-current="page"' : '' ?>><?= e($label) ?> <span><?= (int) ($filterCounts[$key] ?? 0) ?></span></a>
    <?php endforeach; ?>
</nav>
<div class="d-flex flex-wrap align-items-center gap-2 small text-secondary mb-2">
    <span role="status"><?= (int) ($totalRows ?? count($rows)) ?> <?= (int) ($totalRows ?? count($rows)) === 1 ? 'cuenta' : 'cuentas' ?></span>
    <?php if ($q !== ''): ?><a class="gs-filter-clear" href="<?= e($accountUrl(['q' => ''])) ?>">Búsqueda: <?= e($q) ?> · Quitar</a><?php endif; ?>
    <?php if ($filter !== ''): ?><a class="gs-filter-clear" href="<?= e($accountUrl(['filtro' => ''])) ?>"><?= e($filters[$filter]) ?> · Quitar filtro</a><?php endif; ?>
</div>
