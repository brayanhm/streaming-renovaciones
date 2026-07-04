<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Auditoría</h1>
        <small class="text-secondary">Últimas 300 acciones sensibles (borrados, gestión de usuarios, importaciones, accesos).</small>
    </div>
    <a href="<?= e(url('/usuarios')) ?>" class="btn btn-outline-secondary">Volver a usuarios</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Detalle</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">Sin registros de auditoría.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><small><?= e((string) ($r['created_at'] ?? '')) ?></small></td>
                            <td><?= e((string) ($r['usuario'] ?? '')) ?></td>
                            <td><span class="badge text-bg-secondary"><?= e((string) ($r['accion'] ?? '')) ?></span></td>
                            <td><small><?= e((string) ($r['detalle'] ?? '')) ?></small></td>
                            <td><small class="text-secondary"><?= e((string) ($r['ip'] ?? '')) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
