<?php
declare(strict_types=1);

use App\Models\Modalidad;
use App\Models\Plataforma;

$states = [
    'TODOS' => ['label' => 'Todos', 'badge' => 'secondary'],
    'CONTACTAR_2D' => ['label' => 'Contactar (-3 días)', 'badge' => 'warning'],
    'REENVIAR_1D' => ['label' => 'Contactar (día de vencimiento)', 'badge' => 'info'],
    'ESPERA' => ['label' => 'Espera', 'badge' => 'primary'],
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

        return Modalidad::tipoCuentaLabel($tipoCuenta, $dispositivos) . ' - ' . $duracion . ' mes(es)';
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
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Panel operativo Ghost Store</h1>
        <p class="text-secondary mb-0">Control diario de clientes, vencimientos y renovaciones de la tienda virtual.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('/suscripciones')) ?>" class="btn btn-outline-primary btn-lg">Gestionar membresías</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get" action="<?= e(url('/dashboard')) ?>">
            <input type="hidden" name="estado" value="<?= e($selectedStatus ?? 'TODOS') ?>">
            <?php
            $clearFiltersQuery = http_build_query([
                'estado' => $selectedStatus ?? 'TODOS',
            ]);
            ?>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold" for="contacto">Contacto</label>
                <input
                    class="form-control form-control-lg"
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
                    class="form-control form-control-lg"
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
                    class="form-control form-control-lg"
                    type="text"
                    id="telefono"
                    name="telefono"
                    placeholder="Ej: 79625801"
                    value="<?= e($telefono ?? '') ?>"
                >
            </div>
            <div class="col-12 col-md-3">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg" type="submit">Buscar</button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/dashboard?' . $clearFiltersQuery)) ?>">Limpiar filtros</a>
                </div>
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
            'contacto' => $contacto ?? '',
            'usuario' => $usuario ?? '',
            'telefono' => $telefono ?? '',
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

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Costo total</h2>
                <div class="h4 mb-0"><?= e(money((float) ($totals['costo'] ?? 0))) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Venta total</h2>
                <div class="h4 mb-0"><?= e(money((float) ($totals['venta'] ?? 0))) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-1">Ganancia estimada</h2>
                <?php $gananciaTotal = (float) ($totals['ganancia'] ?? 0); ?>
                <div class="h4 mb-0 <?= $gananciaTotal < 0 ? 'text-danger' : 'text-success' ?>">
                    <?= e(money($gananciaTotal)) ?>
                </div>
            </div>
        </div>
    </div>
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
                        <th>Costo (Bs)</th>
                        <th>Precio (Bs)</th>
                        <th>Ganancia (Bs)</th>
                        <th>Estado</th>
                        <th>WhatsApp</th>
                        <th>Renovar</th>
                        <th>No renovado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="12" class="text-center py-4 text-secondary">No hay suscripciones para mostrar.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $row): ?>
                        <?php
                        $vencimiento = (string) ($row['fecha_vencimiento'] ?? '');
                        $dias = (int) ($row['dias_para_vencer'] ?? 0);
                        $status = (string) ($row['estado'] ?? 'ACTIVO');
                        $whatsType = (string) ($row['contact_type_sugerido'] ?? 'MENOS_2');
                        $recupType = $dias <= -15 ? 'REC_15' : 'REC_7';
                        $showRecoveryOption = ($selectedStatus ?? 'TODOS') === 'VENCIDO';
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
                                <div>
                                    <?php
                                    $renovacionLabel = Plataforma::datoRenovacionLabel((string) ($row['plataforma_dato_renovacion'] ?? 'USUARIO'));
                                    $renewalValue = trim((string) ($row['usuario_proveedor'] ?? ''));
                                    ?>
                                    <span class="badge text-bg-light border"><?= e((string) ($row['plataforma_tipo_servicio'] ?? '')) ?></span>
                                    <?php if ($renewalValue !== ''): ?>
                                        <span class="badge text-bg-secondary"><?= e($renovacionLabel) ?>: <?= e($renewalValue) ?></span>
                                    <?php else: ?>
                                        <span class="badge text-bg-light border text-secondary"><?= e($renovacionLabel) ?>: sin dato</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= e(tipo_suscripcion_dashboard($row)) ?></td>
                            <td>
                                <div><?= e($vencimiento) ?></div>
                                <small class="<?= e($diasClass) ?>">
                                    <?= e($diasLabel) ?>
                                </small>
                                <details class="mt-2">
                                    <summary class="small text-primary" role="button">Editar finalizacion</summary>
                                    <form method="post" action="<?= e(url('/suscripciones/finalizacion/' . (int) $row['id'])) ?>" class="mt-2">
                                        <input
                                            type="date"
                                            class="form-control form-control-sm mb-2"
                                            name="fecha_vencimiento"
                                            value="<?= e($vencimiento) ?>"
                                            min="<?= e((string) ($row['fecha_inicio'] ?? '')) ?>"
                                            required
                                        >
                                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Guardar</button>
                                    </form>
                                </details>
                            </td>
                            <td><?= e(money((float) ($row['costo_final'] ?? $row['modalidad_costo'] ?? 0))) ?></td>
                            <td><?= e(money((float) ($row['precio_final'] ?? $row['modalidad_precio'] ?? 0))) ?></td>
                            <td class="<?= (float) (($row['ganancia_final'] ?? 0)) < 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' ?>">
                                <?= e(money((float) ($row['ganancia_final'] ?? 0))) ?>
                            </td>
                            <td>
                                <span class="badge <?= e(status_badge_class($status)) ?>"><?= e($status) ?></span>
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
                                <?php if ($showRecoveryOption): ?>
                                    <a
                                        class="btn btn-outline-dark btn-sm px-2 mt-1"
                                        href="<?= e(url('/suscripciones/whatsapp/' . (int) $row['id'] . '?tipo=' . $recupType)) ?>"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        Recuperacion
                                    </a>
                                <?php endif; ?>
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
            <a class="page-link" href="<?= e(url('/dashboard?' . $paginationQuery . '&page=' . (($page ?? 1) - 1))) ?>">‹ Anterior</a>
        </li>
        <li class="page-item disabled"><span class="page-link"><?= e((string) ($page ?? 1)) ?> / <?= e((string) ($totalPages ?? 1)) ?></span></li>
        <li class="page-item <?= ($page ?? 1) >= ($totalPages ?? 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e(url('/dashboard?' . $paginationQuery . '&page=' . (($page ?? 1) + 1))) ?>">Siguiente ›</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<div class="card shadow-sm mt-3">
    <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <h2 class="h6 mb-0">No renovados</h2>
            <span class="badge text-bg-danger"><?= e((string) ($noRenewCount ?? 0)) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Servicio</th>
                        <th>Plan</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th>Renovar</th>
                        <th>WhatsApp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($noRenewRows)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-secondary">No hay suscripciones marcadas como no renovadas.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach (($noRenewRows ?? []) as $row): ?>
                        <?php
                        $vencimiento = (string) ($row['fecha_vencimiento'] ?? '');
                        $status = (int) ($row['flag_no_renovo'] ?? 0) === 1
                            ? 'VENCIDO'
                            : (string) ($row['estado'] ?? 'VENCIDO');
                        $whatsType = (string) ($row['contact_type_sugerido'] ?? 'REC_7');
                        $renewOptions = renewal_options($row);
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) ($row['cliente_nombre'] ?? '')) ?></td>
                            <td><?= e((string) ($row['cliente_telefono'] ?? '')) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($row['plataforma_nombre'] ?? '')) ?></div>
                                <small class="text-secondary"><?= e((string) ($row['nombre_modalidad'] ?? '')) ?></small>
                                <div>
                                    <?php
                                    $renovacionLabel = Plataforma::datoRenovacionLabel((string) ($row['plataforma_dato_renovacion'] ?? 'USUARIO'));
                                    $renewalValue = trim((string) ($row['usuario_proveedor'] ?? ''));
                                    ?>
                                    <span class="badge text-bg-light border"><?= e((string) ($row['plataforma_tipo_servicio'] ?? '')) ?></span>
                                    <?php if ($renewalValue !== ''): ?>
                                        <span class="badge text-bg-secondary"><?= e($renovacionLabel) ?>: <?= e($renewalValue) ?></span>
                                    <?php else: ?>
                                        <span class="badge text-bg-light border text-secondary"><?= e($renovacionLabel) ?>: sin dato</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= e(tipo_suscripcion_dashboard($row)) ?></td>
                            <td>
                                <div><?= e($vencimiento) ?></div>
                                <details class="mt-2">
                                    <summary class="small text-primary" role="button">Editar finalizacion</summary>
                                    <form method="post" action="<?= e(url('/suscripciones/finalizacion/' . (int) $row['id'])) ?>" class="mt-2">
                                        <input
                                            type="date"
                                            class="form-control form-control-sm mb-2"
                                            name="fecha_vencimiento"
                                            value="<?= e($vencimiento) ?>"
                                            min="<?= e((string) ($row['fecha_inicio'] ?? '')) ?>"
                                            required
                                        >
                                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Guardar</button>
                                    </form>
                                </details>
                            </td>
                            <td>
                                <span class="badge <?= e(status_badge_class($status)) ?>"><?= e($status) ?></span>
                                <div><small class="text-danger fw-semibold">Marcado como no renovado</small></div>
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
                                <a
                                    class="btn btn-success btn-sm px-3"
                                    href="<?= e(url('/suscripciones/whatsapp/' . (int) $row['id'] . '?tipo=' . $whatsType)) ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    WhatsApp
                                </a>
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
                <p class="mb-0 text-secondary">Clientes que deben recibir mensaje de renovación 3 días antes del vencimiento.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">REENVIAR_1D</h2>
                <p class="mb-0 text-secondary">Clientes para mensaje el mismo día de vencimiento si aún no renovaron.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">VENCIDO (con recuperacion)</h2>
                <p class="mb-0 text-secondary">En VENCIDOS tambien se incluyen casos de recuperacion para enviar su mensaje por WhatsApp.</p>
            </div>
        </div>
    </div>
</div>

