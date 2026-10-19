<?php
declare(strict_types=1);

use App\Models\Modalidad;
use App\Models\Plataforma;

$hasSubscriptionCatalog = !empty($plataformas ?? []) && !empty($tiposSuscripcion ?? []);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Clientes</h1>
    <div class="gs-page-actions"><a href="<?= e(url('/clientes?nuevo=1')) ?>#nuevo-cliente" class="btn btn-primary" data-open-panel="nuevo-cliente"><?= icon('plus') ?><span class="button-label">Nuevo cliente</span></a>
        <a href="<?= e(url('/clientes?nuevo=antiguo')) ?>#cliente-antiguo" class="btn btn-outline-primary" data-open-panel="cliente-antiguo">Cliente existente</a>
        <?php if ((int) ($missingContactCount ?? 0) > 0): ?>
            <a href="<?= e(url('/clientes/completar')) ?>" class="btn btn-warning"><?= icon('users') ?><span class="button-label">Completar faltantes (<?= (int) $missingContactCount ?>)</span></a>
        <?php endif; ?>
        <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver al panel</span></a>
    </div>
</div>

<div class="row g-3 gs-page-layout">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" action="<?= e(url('/clientes')) ?>" class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-9">
                        <label class="form-label" for="buscar-clientes">Buscar clientes</label>
                        <input id="buscar-clientes"
                            type="text"
                            class="form-control form-control-lg"
                            name="q"
                            placeholder="Busca por contacto, usuario o número"
                            value="<?= e($search ?? '') ?>"
                        >
                    </div>
                    <div class="col-12 col-md-3 d-grid">
                        <button class="btn btn-primary btn-lg" type="submit"><?= icon('search') ?><span class="button-label">Buscar</span></button>
                    </div>
                </form>
                <?php if (($search ?? '') !== ''): ?><p class="small text-secondary">Filtros activos. <a href="<?= e(url('/clientes')) ?>">Limpiar filtros</a></p><?php endif; ?>

                <div class="table-responsive">
                    <table data-responsive-cards data-mobile-primary="0,1,2,3,5" class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Contacto</th>
                                <th>Número</th>
                                <th>Plataforma</th>
                                <th>Usuario</th>
                                <th>Notas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="6" class="text-center text-secondary py-4"><?= ($search ?? '') !== '' ? 'No encontramos clientes para esta búsqueda.' : 'Todavía no hay clientes. Usa «Nuevo cliente» para comenzar.' ?></td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <tr data-record-key="cliente-<?= (int) $item['id'] ?>">
                                    <td class="fw-semibold">
                                        <a href="<?= e(url('/clientes/' . (int) $item['id'])) ?>" class="text-decoration-none"><?= e((string) $item['nombre']) ?></a>
                                    </td>
                                    <td><?= e((string) $item['telefono']) ?></td>
                                    <td><?= e((string) ($item['plataforma_nombre'] ?? '—')) ?></td>
                                    <td><?= e((string) ($item['usuario_proveedor'] ?? '')) ?></td>
                                    <td><?= e(mb_strimwidth((string) ($item['notas'] ?? ''), 0, 50, '…')) ?></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/suscripciones?cliente_id=' . (int) $item['id'] . '#nueva-vigencia')) ?>"><?= icon('plus') ?><span class="button-label">Asignar vigencia</span></a>
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/clientes/editar/' . (int) $item['id'])) ?>"><?= icon('square-pen') ?><span class="button-label">Editar</span></a>
                                            <details class="gs-action-menu"><summary class="btn btn-outline-secondary btn-sm">Más</summary><div class="gs-action-popover"><form method="post" action="<?= e(url('/clientes/eliminar/' . (int) $item['id'])) ?>" class="gs-mobile-detail-action" data-confirm="<?= e('Eliminar a «' . $item['nombre'] . '» (' . $item['telefono'] . '). También se eliminarán sus suscripciones asociadas. Esta acción no se puede deshacer desde el sistema.') ?>" data-confirm-title="Confirmar eliminación" data-confirm-button="Eliminar cliente" data-confirm-danger="1">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-outline-danger btn-sm" type="submit"><?= icon('trash') ?><span class="button-label">Eliminar</span></button>
                                            </form></div></details>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (($totalPages ?? 1) > 1): ?>
                <?php $pq = http_build_query(array_filter(['q' => $search ?? ''])); ?>
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                    <small class="text-secondary"><?= e((string) ($totalRows ?? 0)) ?> clientes en total</small>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(url('/clientes?' . $pq . '&page=' . (($page ?? 1) - 1))) ?>" aria-label="Página anterior">‹</a>
                        </li>
                        <li class="page-item disabled"><span class="page-link"><?= e((string) ($page ?? 1)) ?> / <?= e((string) ($totalPages ?? 1)) ?></span></li>
                        <li class="page-item <?= ($page ?? 1) >= ($totalPages ?? 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(url('/clientes?' . $pq . '&page=' . (($page ?? 1) + 1))) ?>" aria-label="Página siguiente">›</a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4 gs-create-column">
        <details class="card gs-form-panel shadow-sm" id="nuevo-cliente" data-form-panel <?= ($_GET['nuevo'] ?? '') === '1' || ($_SESSION['_old_action'] ?? '') === '/clientes' ? 'open' : '' ?>>
            <summary>Nuevo cliente</summary>
            <div class="card-body">
                <form method="post" action="<?= e(url('/clientes')) ?>" id="create-client-form" data-form-behavior="create-client">
                    <?= csrf_field() ?>
                    <?php if (!$hasSubscriptionCatalog): ?>
                        <div class="alert alert-warning">
                            Antes de crear clientes, registra al menos una plataforma y un plan de suscripción.
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="contacto">Contacto <span class="text-secondary fw-normal">(opcional)</span></label>
                        <input
                            type="text"
                            class="form-control"
                            id="contacto"
                            name="contacto"
                            value="<?= e(old('contacto', old('nombre'))) ?>"
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="numero">Número (celular)</label>
                        <input
                            type="text"
                            class="form-control"
                            id="numero"
                            name="numero"
                            value="<?= e(old('numero', old('telefono'))) ?>"
                            placeholder="Ej: 71234567"
                            inputmode="numeric"
                            maxlength="8"
                            required
                        >
                        <small class="text-secondary">Solo los 8 dígitos, sin +591 (se agrega automáticamente).</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notas">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"><?= e(old('notas')) ?></textarea>
                    </div>
                    <hr>
                    <h3 class="h6 mb-3">Suscripción inicial del cliente</h3>
                    <div class="mb-3">
                        <label class="form-label" for="plataforma_id">Plataforma</label>
                        <select class="form-select js-plataforma" id="plataforma_id" name="plataforma_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldPlat = old('plataforma_id'); ?>
                            <?php foreach (($plataformas ?? []) as $plataforma): ?>
                                <option
                                    value="<?= e((string) $plataforma['id']) ?>"
                                    data-tipo="<?= e(service_type_label((string) $plataforma['tipo_servicio'])) ?>"
                                    data-dato-renovacion="<?= e((string) Plataforma::normalizeDatoRenovacion((string) ($plataforma['dato_renovacion'] ?? ''), (string) ($plataforma['tipo_servicio'] ?? ''))) ?>"
                                    <?= $oldPlat === (string) $plataforma['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $plataforma['nombre']) ?> (<?= e(service_type_label((string) $plataforma['tipo_servicio'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="modalidad_id">Plan y duración</label>
                        <select class="form-select js-modalidad" id="modalidad_id" name="modalidad_id" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldMod = old('modalidad_id'); ?>
                            <?php foreach (($tiposSuscripcion ?? []) as $modalidad): ?>
                                <option
                                    value="<?= e((string) $modalidad['id']) ?>"
                                    data-plataforma-id="<?= e((string) $modalidad['plataforma_id']) ?>"
                                    <?= $oldMod === (string) $modalidad['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $modalidad['nombre_modalidad']) ?> -
                                    <?= e(Modalidad::tipoCuentaLabel((string) ($modalidad['tipo_cuenta'] ?? 'CUENTA_COMPLETA'), isset($modalidad['dispositivos']) ? (int) $modalidad['dispositivos'] : null)) ?> -
                                    <?= e(duration_label(max(1, (int) ($modalidad['duracion_meses'] ?? 1)))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-secondary">La suscripción inicia hoy y el vencimiento se calcula según la duración elegida.</small>
                    </div>
                    <div class="mb-3 js-usuario-wrap">
                        <label class="form-label js-usuario-label" for="usuario_proveedor">Dato de la cuenta para renovar</label>
                        <input
                            type="text"
                            class="form-control js-usuario-input"
                            id="usuario_proveedor"
                            name="usuario_proveedor"
                            value="<?= e(old('usuario_proveedor')) ?>"
                            placeholder="Ej: usuario123"
                        >
                        <small class="text-secondary js-usuario-help">Se pedirá según la configuración de la plataforma.</small>
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg" <?= !$hasSubscriptionCatalog ? 'disabled' : '' ?>><?= icon('save') ?><span class="button-label">Guardar cliente</span></button>
                </form>
            </div>
        </details>

        <details class="card gs-form-panel shadow-sm mt-3" id="cliente-antiguo" data-form-panel <?= ($_GET['nuevo'] ?? '') === 'antiguo' || ($_SESSION['_old_action'] ?? '') === '/clientes/antiguo' ? 'open' : '' ?>>
            <summary>Agregar cliente antiguo</summary>
            <div class="card-body">
                <form method="post" action="<?= e(url('/clientes/antiguo')) ?>">
                    <?= csrf_field() ?>
                    <?php if (!$hasSubscriptionCatalog): ?>
                        <div class="alert alert-warning">
                            Debes tener plataformas y planes creados para registrar clientes antiguos.
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="contacto_antiguo">Contacto <span class="text-secondary fw-normal">(opcional)</span></label>
                        <input
                            type="text"
                            class="form-control"
                            id="contacto_antiguo"
                            name="contacto_antiguo"
                            value="<?= e(old('contacto_antiguo')) ?>"
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="numero_antiguo">Numero</label>
                        <input
                            type="text"
                            class="form-control"
                            id="numero_antiguo"
                            name="numero_antiguo"
                            value="<?= e(old('numero_antiguo')) ?>"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="plataforma_id_antiguo">Plataforma</label>
                        <select class="form-select" id="plataforma_id_antiguo" name="plataforma_id_antiguo" required>
                            <option value="">Seleccionar...</option>
                            <?php $oldPlatAntiguo = old('plataforma_id_antiguo'); ?>
                            <?php foreach (($plataformas ?? []) as $plataforma): ?>
                                <option
                                    value="<?= e((string) $plataforma['id']) ?>"
                                    <?= $oldPlatAntiguo === (string) $plataforma['id'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $plataforma['nombre']) ?> (<?= e(service_type_label((string) $plataforma['tipo_servicio'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-secondary">Se usara automaticamente el primer plan disponible de la plataforma.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="usuario_proveedor_antiguo">Usuario o correo de renovacion</label>
                        <input
                            type="text"
                            class="form-control"
                            id="usuario_proveedor_antiguo"
                            name="usuario_proveedor_antiguo"
                            value="<?= e(old('usuario_proveedor_antiguo')) ?>"
                        >
                        <small class="text-secondary">Obligatorio si la plataforma antigua es renovable.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="fecha_finalizacion">Fecha de finalizacion</label>
                        <input
                            type="date"
                            class="form-control"
                            id="fecha_finalizacion"
                            name="fecha_finalizacion"
                            value="<?= e(old('fecha_finalizacion')) ?>"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notas_antiguo">Notas</label>
                        <textarea class="form-control" id="notas_antiguo" name="notas_antiguo" rows="2"><?= e(old('notas_antiguo')) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100" <?= !$hasSubscriptionCatalog ? 'disabled' : '' ?>><?= icon('save') ?><span class="button-label">Guardar cliente antiguo</span></button>
                </form>
            </div>
        </details>
    </div>
</div>
