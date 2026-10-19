<?php declare(strict_types=1); ?>
<div class="gs-pagination d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
    <span class="small text-secondary"><?= $totalRows ? ($page - 1) * $perPage + 1 : 0 ?>–<?= min($page * $perPage, $totalRows) ?> de <?= $totalRows ?> <?= $totalRows === 1 ? 'cuenta' : 'cuentas' ?></span>
    <?php if ($totalPages > 1): ?><nav class="d-flex align-items-center gap-2" aria-label="Páginas de cuentas">
        <?php if ($page > 1): ?><a class="btn btn-sm btn-outline-primary" href="<?= e($accountUrl(['page' => $page - 1])) ?>">Anterior</a><?php endif; ?>
        <span class="small">Página <?= $page ?> de <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?><a class="btn btn-sm btn-outline-primary" href="<?= e($accountUrl(['page' => $page + 1])) ?>">Siguiente</a><?php endif; ?>
    </nav><?php endif; ?>
</div>
