<?php
declare(strict_types=1);

use App\Models\Modalidad;

$tiposCuentaOpciones = [
    'CUENTA_COMPLETA' => 'Cuenta completa',
    'POR_DISPOSITIVOS' => 'Por dispositivos',
    'AMBOS' => 'Ambos',
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Completar contactos faltantes</h1>
        <small class="text-secondary">
            Actualiza contacto y número para clientes incompletos.
            <span class="fw-semibold"><?= e((string) count($rows ?? [])) ?> pendiente(s)</span> con el filtro actual.
        </small>
    </div>
    <a href="<?= e(url('/clientes')) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver a clientes</span></a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= e(url('/clientes/completar')) ?>" class="row g-2 mb-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold" for="q">Buscar</label>
                <input
                    type="text"
                    class="form-control form-control-lg"
                    id="q"
                    name="q"
                    placeholder="Busca por contacto, número o usuario"
                    value="<?= e($search ?? '') ?>"
                >
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold" for="plataforma_id">Plataforma</label>
                <select class="form-select form-select-lg" id="plataforma_id" name="plataforma_id">
                    <option value="0">Todas</option>
                    <?php foreach (($plataformas ?? []) as $plataforma): ?>
                        <option
                            value="<?= (int) $plataforma['id'] ?>"
                            <?= (int) ($selectedPlataformaId ?? 0) === (int) $plataforma['id'] ? 'selected' : '' ?>
                        ><?= e((string) $plataforma['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold" for="tipo_cuenta">Tipo de cuenta</label>
                <select class="form-select form-select-lg" id="tipo_cuenta" name="tipo_cuenta">
                    <option value="">Todos</option>
                    <?php foreach ($tiposCuentaOpciones as $valor => $etiqueta): ?>
                        <option
                            value="<?= e($valor) ?>"
                            <?= ($selectedTipoCuenta ?? '') === $valor ? 'selected' : '' ?>
                        ><?= e($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button class="btn btn-primary btn-lg" type="submit"><?= icon('search') ?><span class="button-label">Filtrar</span></button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Plataforma</th>
                        <th>Plan</th>
                        <th>Vence</th>
                        <th>Contacto</th>
                        <th>Número</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">
                                No hay clientes pendientes por completar.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $item): ?>
                        <tr>
                            <td class="fw-semibold"><?= (int) $item['id'] ?></td>
                            <td><?= e((string) ($item['usuario_proveedor'] ?? '')) ?></td>
                            <td><?= e((string) ($item['plataforma_nombre'] ?? '')) ?></td>
                            <td>
                                <?php if (!empty($item['tipo_cuenta'])): ?>
                                    <div><?= e((string) ($item['nombre_modalidad'] ?? '')) ?></div>
                                    <small class="text-secondary">
                                        <?= e(Modalidad::tipoCuentaLabel(
                                            (string) $item['tipo_cuenta'],
                                            isset($item['dispositivos']) ? (int) $item['dispositivos'] : null
                                        )) ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-secondary">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(display_date($item['fecha_vencimiento'] ?? null)) ?></td>
                            <td><?= e((string) $item['nombre']) ?></td>
                            <td><span class="text-secondary">Pendiente</span></td>
                            <td class="text-end"><a class="btn btn-primary btn-sm" data-modal-link data-contact-complete href="<?= e(url('/clientes/completar/' . (int) $item['id'])) ?>"><?= icon('square-pen') ?><span class="button-label">Completar contacto</span></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

