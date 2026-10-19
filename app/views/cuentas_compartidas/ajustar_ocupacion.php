<?php
declare(strict_types=1);
$accountId = (int) $cuenta['id'];
$occupationExpanded = true;
$occupationStandalone = true;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Ajustar cupos</h1>
    <a class="btn btn-outline-secondary" href="<?= e(url('/' . $service)) ?>">Volver a cuentas</a>
</div>
<div class="gs-operation-context mb-3">
    <strong><?= e($cuenta['etiqueta']) ?></strong>
    <span><?= e($cuenta['correo']) ?></span>
    <span>Ocupación actual: <strong><?= (int) $cuenta['ocupados'] ?></strong> · Capacidad de referencia: <?= (int) $cuenta['capacidad'] ?></span>
</div>
<?php require __DIR__ . '/ocupacion.php'; ?>
<?php require __DIR__ . '/../chatgpt/scripts.php'; ?>
