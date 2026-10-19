<?php
declare(strict_types=1);

use App\Models\Modalidad;
use App\Models\Plataforma;

$states = [
    'PENDIENTES' => ['label' => 'Pendientes de hoy', 'badge' => 'warning'],
    'TODOS' => ['label' => 'Vigentes', 'badge' => 'secondary'],
    'CONTACTAR_2D' => ['label' => 'Por vencer', 'badge' => 'warning'],
    'REENVIAR_1D' => ['label' => 'Vence hoy', 'badge' => 'info'],
    'ESPERA' => ['label' => 'En espera', 'badge' => 'primary'],
    'ACTIVO' => ['label' => 'Al día', 'badge' => 'success'],
    'VENCIDO' => ['label' => 'Vencidos', 'badge' => 'danger'],
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

        return Modalidad::tipoCuentaLabel($tipoCuenta, $dispositivos) . ' - ' . duration_label($duracion);
    }
}

if (!function_exists('renewal_options')) {
    function renewal_options(array $row): array
    {
        return Plataforma::resolveRenewalMonths(
            isset($row['modalidad_duraciones_disponibles']) && (string) $row['modalidad_duraciones_disponibles'] !== ''
                ? (string) $row['modalidad_duraciones_disponibles']
                : (isset($row['plataforma_duraciones_disponibles']) ? (string) $row['plataforma_duraciones_disponibles'] : null)
        );
    }
}
$panelBase = $panelBase ?? '/dashboard';
$panelTitulo = $panelTitulo ?? 'Panel operativo Ghost Store';
$panelSubtitulo = $panelSubtitulo ?? 'Control diario de clientes, vencimientos y renovaciones de la tienda virtual.';
$panelTipo = $panelTipo ?? 'streaming';
$contactarBase = '/contactar';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1"><?= e($panelTitulo) ?></h1>
        <p class="text-secondary mb-0"><?= e($panelSubtitulo) ?></p>
    </div>
    <div class="gs-page-actions">
        <a href="<?= e(url($contactarBase)) ?>" class="btn btn-success "><?= icon('message-circle') ?><span class="button-label">Contactar hoy</span></a>
        <a href="<?= e(url('/suscripciones')) ?>" class="btn btn-outline-primary "><?= icon('calendar-clock') ?><span class="button-label">Gestionar suscripciones</span></a>
    </div>
</div>

<nav class="gs-delivery-shortcuts mb-3" aria-label="Entregar accesos"><a class="btn btn-outline-primary btn-sm" href="<?= e(url('/chatgpt/distribucion')) ?>">Entregar ChatGPT</a><a class="btn btn-outline-primary btn-sm" href="<?= e(url('/claude/distribucion')) ?>">Entregar Claude</a></nav>
<?php require __DIR__ . '/../chatgpt/resumen_avisos.php'; ?>
<details class="card shadow-sm mb-3 gs-dashboard-filters" data-dashboard-filters <?= ($contacto ?? '') !== '' || ($usuario ?? '') !== '' || ($telefono ?? '') !== '' ? 'open' : '' ?>>
    <summary>Buscar por contacto, usuario o teléfono</summary>
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get" action="<?= e(url($panelBase)) ?>">
            <input type="hidden" name="estado" value="<?= e($selectedStatus ?? 'TODOS') ?>">
            <?php
            $clearFiltersQuery = http_build_query([
                'estado' => $selectedStatus ?? 'TODOS',
            ]);
            ?>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold" for="contacto">Contacto</label>
                <input
                    class="form-control "
                    type="text"
                    id="contacto"
                    name="contacto"
                    placeholder="Ej: Juan Pérez"
                    value="<?= e($contacto ?? '') ?>"
                >
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold" for="usuario">Usuario</label>
                <input
                    class="form-control "
                    type="text"
                    id="usuario"
                    name="usuario"
                    placeholder="Ej: usuario123"
                    value="<?= e($usuario ?? '') ?>"
                >
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold" for="telefono">Teléfono</label>
                <input
                    class="form-control "
                    type="text"
                    id="telefono"
                    name="telefono"
                    placeholder="Ej: 79625801"
                    value="<?= e($telefono ?? '') ?>"
                >
            </div>
            <div class="col-12 col-md-3">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary " type="submit"><?= icon('search') ?><span class="button-label">Buscar</span></button>
                    <a class="btn btn-outline-secondary" href="<?= e(url($panelBase . '?' . $clearFiltersQuery)) ?>"><?= icon('funnel-x') ?><span class="button-label">Limpiar filtros</span></a>
                </div>
            </div>
        </form>
    </div>
</details>

<ul class="nav nav-pills flex-wrap pb-2 mb-3 gs-status-filters">
    <?php foreach ($states as $key => $meta): ?>
        <?php
        $isActive = ($selectedStatus ?? 'TODOS') === $key;
        $query = http_build_query(array_filter([
            'estado' => $key,
            'contacto' => $contacto ?? '',
            'usuario' => $usuario ?? '',
            'telefono' => $telefono ?? '',
        ], static fn (string $value): bool => $value !== ''));
        ?>
        <li class="nav-item me-2">
            <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?= e(url($panelBase . '?' . $query)) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                <?= e($meta['label']) ?>
                <span class="badge text-bg-<?= e($meta['badge']) ?> ms-1"><?= e((string) ($counts[$key] ?? 0)) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table data-responsive-cards data-mobile-primary="0,1,2,3,4,5" class="table table-striped table-hover align-middle mb-0 gs-dashboard-table">
                <thead><tr><th>Cliente</th><th>Servicio y plan</th><th>Vencimiento</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-secondary"><?php if (($selectedStatus ?? '') === 'PENDIENTES'): ?>No hay contactos pendientes para hoy. <a href="<?= e(url($panelBase . '?estado=TODOS')) ?>">Ver suscripciones vigentes</a><?php else: ?>No hay suscripciones con estos filtros.<?php endif; ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $row): ?>
                        <?php
                        $vencimiento = (string) ($row['fecha_vencimiento'] ?? '');
                        $dias = (int) ($row['dias_para_vencer'] ?? 0);
                        $status = (string) ($row['estado'] ?? 'ACTIVO');
                        $whatsType = (string) ($row['contact_type_sugerido'] ?? 'MENOS_2');
                        $recupType = $dias <= -15 ? 'REC_15' : 'REC_7';
                        $showRecoveryOption = ($selectedStatus ?? 'TODOS') === 'VENCIDO' || $status === 'RECUP';
                        $renewOptions = renewal_options($row);
                        if ($dias < 0) {
                            $diasLabel = 'Vencido hace ' . abs($dias) . ' días';
                            $diasClass = 'text-danger';
                        } elseif ($dias === 0) {
                            $diasLabel = 'Vence hoy';
                            $diasClass = 'text-danger';
                        } elseif ($dias === 1) {
                            $diasLabel = 'Vence en 1 día';
                            $diasClass = 'text-warning';
                        } else {
                            $diasLabel = 'Vence en ' . $dias . ' días';
                            $diasClass = $dias <= 3 ? 'text-warning' : 'text-secondary';
                        }
                        ?>
                        <tr data-record-key="suscripcion-<?= (int) $row['id'] ?>">
<td>
                                <div class="fw-semibold">
                                    <a href="<?= e(url('/clientes/' . (int) ($row['cliente_id'] ?? 0))) ?>" class="text-decoration-none"><?= e((string) ($row['cliente_nombre'] ?? '')) ?></a>
                                </div>
                                <small class="text-secondary">#<?= e((string) ($row['id'] ?? '')) ?></small>
                            <small class="d-block text-secondary"><?= e((string) ($row['cliente_telefono'] ?? '')) ?></small></td>
<td>
                                <div class="fw-semibold"><?= e((string) ($row['plataforma_nombre'] ?? '')) ?></div>
                                <small class="text-secondary"><?= e((string) ($row['nombre_modalidad'] ?? '')) ?></small>
                                <div>
                                    <?php
                                    $renovacionLabel = Plataforma::datoRenovacionLabel((string) ($row['plataforma_dato_renovacion'] ?? 'USUARIO'));
                                    $renewalValue = trim((string) ($row['usuario_proveedor'] ?? ''));
                                    ?>
                                    <span class="badge text-bg-light border"><?= e(service_type_label((string) ($row['plataforma_tipo_servicio'] ?? ''))) ?></span>
                                    <?php if ($renewalValue !== ''): ?>
                                        <span class="badge text-bg-secondary"><?= e($renovacionLabel) ?>: <?= e($renewalValue) ?></span>
                                    <?php else: ?>
                                        <span class="badge text-bg-light border text-secondary"><?= e($renovacionLabel) ?>: sin dato</span>
                                    <?php endif; ?>
                                </div>
                            <details class="small mt-2"><summary>Plan e importes</summary>
                                    <p class="mb-1"><?= e(tipo_suscripcion_dashboard($row)) ?></p>
                                    <div>Costo: <?= e(display_money((float) ($row['costo_final'] ?? 0))) ?></div>
                                    <div>Ganancia estimada: <?= e(display_money((float) ($row['ganancia_final'] ?? 0))) ?></div>
                                </details></td>
<td>
                                <div><?= e(display_date($vencimiento)) ?></div>
                                <small class="<?= e($diasClass) ?>">
                                    <?= e($diasLabel) ?>
                                </small>
                                <?php
                                $esVencidoTab = $showRecoveryOption;
                                ?>
                                <a class="btn btn-sm <?= $esVencidoTab ? 'btn-outline-success' : 'btn-outline-primary' ?> mt-2" data-modal-link href="<?= e(url('/suscripciones/finalizacion/' . (int) $row['id'] . ($esVencidoTab ? '?reactivar=1' : ''))) ?>"><?= icon($esVencidoTab ? 'refresh-cw' : 'square-pen') ?><span class="button-label"><?= $esVencidoTab ? 'Reactivar' : 'Editar vencimiento' ?></span></a>
                            </td>
<td><?= e(display_money((float) ($row['precio_final'] ?? $row['modalidad_precio'] ?? 0))) ?></td>
<td>
                                <span class="badge <?= e(status_badge_class($status)) ?>"><?= e(status_label((string) $status)) ?></span>
                                <?php if ((int) ($row['flag_no_renovo'] ?? 0) === 1): ?>
                                    <div><small class="text-danger fw-semibold">No renovó</small></div>
                                <?php endif; ?>
                            </td>
<td class="gs-table-actions">
                                <form method="post" action="<?= e(url('/suscripciones/whatsapp/' . (int) $row['id'])) ?>" target="_blank" rel="noopener">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tipo" value="<?= e($whatsType) ?>">
                                    <button type="submit" class="btn btn-success btn-sm px-3"><?= icon('message-circle') ?><span class="button-label">WhatsApp</span></button>
                                </form>
                                <?php if ($showRecoveryOption): ?>
                                    <form method="post" action="<?= e(url('/suscripciones/whatsapp/' . (int) $row['id'])) ?>" target="_blank" rel="noopener" class="mt-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="tipo" value="<?= e($recupType) ?>">
                                        <button type="submit" class="btn btn-outline-dark btn-sm px-2"><?= icon('refresh-cw') ?><span class="button-label">Recuperacion</span></button>
                                    </form>
                                <?php endif; ?>
                            <a class="btn btn-primary btn-sm" data-modal-link href="<?= e(url('/suscripciones/renovar/' . (int) $row['id'])) ?>"><?= icon('refresh-cw') ?><span class="button-label">Renovar</span></a><details class="gs-other-actions"><summary>Otras acciones</summary>
                                <a class="btn btn-outline-secondary btn-sm" data-modal-link href="<?= e(url('/suscripciones/historial/' . (int) $row['id'])) ?>"><?= icon('scroll-text') ?><span class="button-label">Historial de renovaciones</span></a>
                                <form method="post" action="<?= e(url('/suscripciones/no-renovo/' . (int) $row['id'])) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><?= icon('circle-x') ?><span class="button-label">Marcar como no renovado</span></button>
                                </form>
                            </details></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<?php
$paginationQuery = http_build_query(array_filter([
    'estado' => $selectedStatus ?? 'TODOS',
    'contacto' => $contacto ?? '',
    'usuario' => $usuario ?? '',
    'telefono' => $telefono ?? '',
]));
?>
<nav class="d-flex justify-content-between align-items-center mt-2 mb-1">
    <small class="text-secondary">
        Mostrando <?= e((string) (min($perPage, $totalRows - ($page - 1) * $perPage))) ?> de <?= e((string) $totalRows) ?> suscripciones
    </small>
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e(url($panelBase . '?' . $paginationQuery . '&page=' . max(1, ($page ?? 1) - 1))) ?>">‹ Anterior</a>
        </li>
        <li class="page-item disabled"><span class="page-link"><?= e((string) ($page ?? 1)) ?> / <?= e((string) ($totalPages ?? 1)) ?></span></li>
        <li class="page-item <?= ($page ?? 1) >= ($totalPages ?? 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e(url($panelBase . '?' . $paginationQuery . '&page=' . min(($totalPages ?? 1), ($page ?? 1) + 1))) ?>">Siguiente ›</a>
        </li>
    </ul>
</nav>
<?php endif; ?>


<details class="mt-3"><summary>Cómo se organizan los estados</summary><div class="row g-3 mt-1">
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Por vencer</h2>
                <p class="mb-0 text-secondary">Clientes que deben recibir mensaje de renovación 3 días antes del vencimiento.</p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Vence hoy</h2>
                <p class="mb-0 text-secondary">Clientes para mensaje el mismo día de vencimiento si aún no renovaron.</p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Vencidas y recuperación</h2>
                <p class="mb-0 text-secondary">En VENCIDOS tambien se incluyen casos de recuperacion para enviar su mensaje por WhatsApp.</p>
            </div>
        </div>
    </div>
</div>


</details>
<details class="mt-3 gs-financial-summary"><summary>Resumen financiero de los resultados</summary>
<div class="row g-3 mb-3 gs-dashboard-stats">
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Costo total</h2>
                <div class="h4 mb-0"><?= e(display_money((float) ($totals['costo'] ?? 0))) ?></div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Venta total</h2>
                <div class="h4 mb-0"><?= e(display_money((float) ($totals['venta'] ?? 0))) ?></div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Ganancia estimada</h2>
                <?php $gananciaTotal = (float) ($totals['ganancia'] ?? 0); ?>
                <div class="h4 mb-0 <?= $gananciaTotal < 0 ? 'text-danger' : 'text-success' ?>">
                    <?= e(display_money($gananciaTotal)) ?>
                </div>
            </div>
        </div>
    </div>
</div>

</details>
