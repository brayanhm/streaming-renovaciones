<?php
declare(strict_types=1);

use App\Models\Modalidad;

$meta = [
    'REENVIAR_1D' => ['label' => 'Vence hoy', 'badge' => 'text-bg-info'],
    'CONTACTAR_2D' => ['label' => 'Por vencer', 'badge' => 'text-bg-warning'],
    'RECUP' => ['label' => 'Recuperación', 'badge' => 'text-bg-dark'],
];

if (!function_exists('plan_label_contactar')) {
    function plan_label_contactar(array $row): string
    {
        $tipoCuenta = (string) ($row['tipo_cuenta'] ?? 'CUENTA_COMPLETA');
        $dispositivos = isset($row['dispositivos']) ? (int) $row['dispositivos'] : null;
        $duracion = max(1, (int) ($row['duracion_meses'] ?? 1));
        return Modalidad::tipoCuentaLabel($tipoCuenta, $dispositivos) . ' · ' . $duracion . ' mes(es)';
    }
}

$total = count($rows ?? []);
$porVencerHoy = 0;
foreach (($rows ?? []) as $r) {
    if ((string) ($r['estado'] ?? '') === 'REENVIAR_1D') {
        $porVencerHoy++;
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Contactar hoy</h1>
        <p class="text-secondary mb-0">
            <strong><?= e((string) $total) ?></strong> cuenta(s) para contactar, ordenadas por urgencia
            (<?= e((string) $porVencerHoy) ?> vencen hoy).
        </p>
    </div>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Ir al panel</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Servicio</th>
                        <th>Plan</th>
                        <th>Vencimiento</th>
                        <th>Precio (Bs)</th>
                        <th>Urgencia</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-secondary">¡Nada pendiente por contactar hoy!</td></tr>
                    <?php endif; ?>
                    <?php foreach (($rows ?? []) as $row): ?>
                        <?php
                        $estado = (string) ($row['estado'] ?? '');
                        $m = $meta[$estado] ?? ['label' => $estado, 'badge' => 'text-bg-secondary'];
                        $dias = (int) ($row['dias_para_vencer'] ?? 0);
                        $whatsType = (string) ($row['contact_type_sugerido'] ?? 'MENOS_2');
                        if ($dias < 0) {
                            $diasLabel = 'Vencido hace ' . abs($dias) . ' días';
                        } elseif ($dias === 0) {
                            $diasLabel = 'Vence hoy';
                        } else {
                            $diasLabel = 'Vence en ' . $dias . ' días';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    <a href="<?= e(url('/clientes/' . (int) ($row['cliente_id'] ?? 0))) ?>" class="text-decoration-none"><?= e((string) ($row['cliente_nombre'] ?? '')) ?></a>
                                </div>
                                <small class="text-secondary">#<?= e((string) ($row['id'] ?? '')) ?></small>
                            </td>
                            <td><?= e((string) ($row['cliente_telefono'] ?? '')) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($row['plataforma_nombre'] ?? '')) ?></div>
                                <small class="text-secondary"><?= e((string) ($row['nombre_modalidad'] ?? '')) ?></small>
                            </td>
                            <td><small><?= e(plan_label_contactar($row)) ?></small></td>
                            <td>
                                <div><?= e((string) ($row['fecha_vencimiento'] ?? '')) ?></div>
                                <small class="text-secondary"><?= e($diasLabel) ?></small>
                            </td>
                            <td><?= e(money((float) ($row['precio_final'] ?? $row['modalidad_precio'] ?? 0))) ?></td>
                            <td><span class="badge <?= e($m['badge']) ?>"><?= e($m['label']) ?></span></td>
                            <td>
                                <form method="post" action="<?= e(url('/suscripciones/whatsapp/' . (int) $row['id'])) ?>" target="_blank" rel="noopener">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tipo" value="<?= e($whatsType) ?>">
                                    <button type="submit" class="btn btn-success btn-sm px-3">WhatsApp</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
