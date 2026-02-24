<?php
declare(strict_types=1);

use App\Models\Modalidad;
use App\Models\Plataforma;

$states = [
    'TODOS' => ['label' => 'Todos', 'badge' => 'secondary'],
    'CONTACTAR_2D' => ['label' => 'Contactar (-2 dias)', 'badge' => 'warning'],
    'REENVIAR_1D' => ['label' => 'Reenviar (-1 dia)', 'badge' => 'info'],
    'ESPERA' => ['label' => 'Espera', 'badge' => 'primary'],
    'ACTIVO' => ['label' => 'Al dia', 'badge' => 'success'],
    'VENCIDO' => ['label' => 'Vencidos', 'badge' => 'danger'],
    'RECUP' => ['label' => 'Recuperar', 'badge' => 'dark'],
];

if (!function_exists('status_badge_class')) {
    function status_badge_class(string $status): string
    {
        return match ($status) {
            'CONTACTAR_2D' => 'text-bg-warning',
            'REENVIAR_1D' => 'text-bg-info',
            'ESPERA' => 'text-bg-primary',
            'ACTIVO' => 'text-bg-success',
            'VENCIDO' => 'text-bg-danger',
            'RECUP' => 'text-bg-dark',
            default => 'text-bg-secondary',
        };
    }
}

if (!function_exists('tipo_suscripcion_dashboard')) {
    function tipo_suscripcion_dashboard(array $row): string
    {
        $tipoCuenta = (string) ($row['tipo_cuenta'] ?? 'CUENTA_COMPLETA');
        $dispositivos = isset($row['dispositivos']) ? (int) $row['dispositivos'] : null;
        $duracion = max(1, (int) ($row['duracion_meses'] ?? 1));

        return Modalidad::tipoCuentaLabel($tipoCuenta, $dispositivos) . ' - ' . $duracion . ' mes(es)';
    }
}

if (!function_exists('renewal_options')) {
    function renewal_options(array $row): array
    {
        $configured = Plataforma::parseDuracionesDisponibles((string) ($row['plataforma_duraciones_disponibles'] ?? ''));

        return $configured !== [] ? $configured : [1, 3, 6];
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Panel de renovaciones</h1>
        <p class="text-secondary mb-0">Seguimiento diario de clientes, vencimientos y renovaciones.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('/suscripciones')) ?>" class="btn btn-outline-primary btn-lg">Gestionar suscripciones</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get" action="<?= e(url('/dashboard')) ?>">
            <input type="hidden" name="estado" value="<?= e($selectedStatus ?? 'TODOS') ?>">
            <div class="col-12 col-md-8">
                <label class="form-label fw-semibold" for="q">Buscar por cliente o telefono</label>
                <input
                    class="form-control form-control-lg"
                    type="text"
                    id="q"
                    name="q"
                    placeholder="Ej: Juan Perez o 79625801"
                    value="<?= e($search ?? '') ?>"
                >
            </div>
            <div class="col-12 col-md-4 d-grid">
                <button class="btn btn-primary btn-lg" type="submit">Buscar</button>
            </div>
        </form>
    </div>
</div>

<ul class="nav nav-pills flex-nowrap overflow-auto pb-2 mb-3">
    <?php foreach ($states as $key => $meta): ?>
        <?php
        $isActive = ($selectedStatus ?? 'TODOS') === $key;
        $query = http_build_query([
            'estado' => $key,
            'q' => $search ?? '',
        ]);
        ?>
        <li class="nav-item me-2">
            <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?= e(url('/dashboard?' . $query)) ?>">
                <?= e($meta['label']) ?>
                <span class="badge text-bg-<?= e($meta['badge']) ?> ms-1"><?= e((string) ($counts[$key] ?? 0)) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Cliente</th>
                        <th>Telefono</th>
                        <th>Servicio</th>
                        <th>Plan</th>
                        <th>Vencimiento</th>
                        <th>Precio (Bs)</th>
                        <th>Estado</th>
                        <th>WhatsApp</th>
                        <th>Renovar</th>
                        <th>No renovado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-secondary">No hay suscripciones para mostrar.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $row): ?>
                        <?php
                        $vencimiento = (string) ($row['fecha_vencimiento'] ?? '');
                        $dias = (int) ($row['dias_para_vencer'] ?? 0);
                        $status = (string) ($row['estado'] ?? 'ACTIVO');
                        $whatsType = (string) ($row['contact_type_sugerido'] ?? 'MENOS_2');
                        $flagNoRenovo = (int) ($row['flag_no_renovo'] ?? 0) === 1;
                        $renewOptions = renewal_options($row);
                        if ($dias < 0) {
                            $diasLabel = 'Vencido hace ' . abs($dias) . ' dias';
                            $diasClass = 'text-danger';
                        } elseif ($dias === 0) {
                            $diasLabel = 'Vence hoy';
                            $diasClass = 'text-danger';
                        } elseif ($dias === 1) {
                            $diasLabel = 'Vence en 1 dia';
                            $diasClass = 'text-warning';
                        } else {
                            $diasLabel = 'Vence en ' . $dias . ' dias';
                            $diasClass = $dias <= 2 ? 'text-warning' : 'text-secondary';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($row['cliente_nombre'] ?? '')) ?></div>
                                <small class="text-secondary">#<?= e((string) ($row['id'] ?? '')) ?></small>
                            </td>
                            <td><?= e((string) ($row['cliente_telefono'] ?? '')) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($row['plataforma_nombre'] ?? '')) ?></div>
                                <small class="text-secondary"><?= e((string) ($row['nombre_modalidad'] ?? '')) ?></small>
                                <div>
                                    <span class="badge text-bg-light border"><?= e((string) ($row['plataforma_tipo_servicio'] ?? '')) ?></span>
                                    <?php if (!empty($row['usuario_proveedor'])): ?>
                                        <span class="badge text-bg-secondary">Cuenta: <?= e((string) $row['usuario_proveedor']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= e(tipo_suscripcion_dashboard($row)) ?></td>
                            <td>
                                <div><?= e($vencimiento) ?></div>
                                <small class="<?= e($diasClass) ?>">
                                    <?= e($diasLabel) ?>
                                </small>
                            </td>
                            <td><?= e(money((float) ($row['precio_final'] ?? $row['modalidad_precio'] ?? 0))) ?></td>
                            <td>
                                <span class="badge <?= e(status_badge_class($status)) ?>"><?= e($status) ?></span>
                                <?php if ($flagNoRenovo): ?>
                                    <div><small class="text-danger fw-semibold">Marcado como no renovado</small></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a
                                    class="btn btn-success btn-sm px-3"
                                    href="<?= e(url('/suscripciones/whatsapp/' . (int) $row['id'] . '?tipo=' . $whatsType)) ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    WhatsApp
                                </a>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($renewOptions as $months): ?>
                                        <form method="post" action="<?= e(url('/suscripciones/renovar/' . (int) $row['id'])) ?>">
                                            <input type="hidden" name="meses" value="<?= e((string) $months) ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">+<?= e((string) $months) ?>M</button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <form method="post" action="<?= e(url('/suscripciones/no-renovo/' . (int) $row['id'])) ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Marcar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">CONTACTAR_2D</h2>
                <p class="mb-0 text-secondary">Clientes que deben recibir recordatorio 2 dias antes del vencimiento.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">REENVIAR_1D</h2>
                <p class="mb-0 text-secondary">Clientes para seguimiento 1 dia antes del vencimiento.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">RECUP</h2>
                <p class="mb-0 text-secondary">Clientes vencidos para recuperar mediante nueva activacion.</p>
            </div>
        </div>
    </div>
</div>
