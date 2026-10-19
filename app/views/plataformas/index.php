<?php
declare(strict_types=1);

use App\Models\Plataforma;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Plataformas</h1>
    <a href="<?= e(url('/plataformas?nuevo=1')) ?>#nueva-plataforma" class="btn btn-primary" data-open-panel="nueva-plataforma"><?= icon('plus') ?><span class="button-label">Nueva plataforma</span></a>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary"><?= icon('arrow-left') ?><span class="button-label">Volver al panel</span></a>
</div>

<div class="row g-3 gs-page-layout">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" action="<?= e(url('/plataformas')) ?>" class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-9">
                        <label class="form-label" for="buscar-plataformas">Buscar plataformas</label>
                        <input id="buscar-plataformas"
                            type="text"
                            class="form-control form-control-lg"
                            name="q"
                            placeholder="Busca por nombre o tipo de servicio"
                            value="<?= e($search ?? '') ?>"
                        >
                    </div>
                    <div class="col-12 col-md-3 d-grid">
                        <button class="btn btn-primary btn-lg" type="submit"><?= icon('search') ?><span class="button-label">Buscar</span></button>
                    </div>
                </form>
                <?php if (($search ?? '') !== ''): ?><p class="small text-secondary">Filtros activos. <a href="<?= e(url('/plataformas')) ?>">Limpiar filtros</a></p><?php endif; ?>

                <div class="table-responsive">
                    <table data-responsive-cards data-mobile-primary="0,1,5" class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo de servicio</th>
                                <th>Dato renovación</th>
                                <th>Duraciones</th>
                                <th>Plantillas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="6" class="text-center text-secondary py-4"><?= ($search ?? '') !== '' ? 'No encontramos plataformas para esta búsqueda.' : 'Todavía no hay plataformas. Usa «Nueva plataforma» para comenzar.' ?></td></tr>
                            <?php endif; ?>
                            <?php
                            $truncatePreview = static function (string $value, int $limit = 40): string {
                                if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                                    if (mb_strlen($value, 'UTF-8') <= $limit) {
                                        return $value;
                                    }

                                    return mb_substr($value, 0, max(0, $limit - 3), 'UTF-8') . '...';
                                }

                                if (strlen($value) <= $limit) {
                                    return $value;
                                }

                                return substr($value, 0, max(0, $limit - 3)) . '...';
                            };
                            ?>
                            <?php
                            // Separar plataformas de streaming de las de IA (cuentas principales).
                            $grupos = ['Streaming' => [], 'Inteligencia Artificial' => []];
                            foreach ($rows as $item) {
                                $esIa = (int) ($item['usa_cuentas_principales'] ?? 0) === 1;
                                $grupos[$esIa ? 'Inteligencia Artificial' : 'Streaming'][] = $item;
                            }
                            ?>
                            <?php foreach ($grupos as $titulo => $items): ?>
                                <?php if (empty($items)) { continue; } ?>
                                <tr class="table-secondary">
                                    <td colspan="6" class="fw-semibold text-uppercase" style="font-size:.78rem; letter-spacing:.06em;">
                                        <?= e($titulo) ?> · <?= e((string) count($items)) ?>
                                    </td>
                                </tr>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                    $esIa = (int) ($item['usa_cuentas_principales'] ?? 0) === 1;
                                    $m2 = $truncatePreview((string) ($item['mensaje_menos_2'] ?? ''));
                                    $m1 = $truncatePreview((string) ($item['mensaje_menos_1'] ?? ''));
                                    $r3 = $truncatePreview((string) ($item['mensaje_rec_7'] ?? ''));
                                    $r15 = $truncatePreview((string) ($item['mensaje_rec_15'] ?? ''));
                                    ?>
                                    <tr data-record-key="plataforma-<?= (int) $item['id'] ?>">
                                        <td class="fw-semibold">
                                            <?= e((string) $item['nombre']) ?>
                                            <?php if ($esIa): ?><span class="badge text-bg-success ms-1">IA</span><?php endif; ?>
                                        </td>
                                        <td><span class="badge text-bg-dark"><?= e((string) $item['tipo_servicio']) ?></span></td>
                                        <td>
                                            <span class="badge text-bg-secondary">
                                                <?= e(Plataforma::datoRenovacionLabel((string) ($item['dato_renovacion'] ?? 'NO_APLICA'))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-light border">
                                                <?= e((string) (($item['duraciones_disponibles'] ?? '') !== '' ? $item['duraciones_disponibles'] : 'Libre')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($esIa): ?>
                                                <small class="text-secondary">Cuentas principales con usuarios asignados.</small>
                                            <?php else: ?>
                                                <small class="d-block text-secondary">-3: <?= e($m2) ?></small>
                                                <small class="d-block text-secondary">0: <?= e($m1) ?></small>
                                                <small class="d-block text-secondary">+3: <?= e($r3) ?></small>
                                                <small class="d-block text-secondary">+15: <?= e($r15) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1 justify-content-end">
                                                <?php if ($esIa): ?>
                                                    <a class="btn btn-outline-success btn-sm" href="<?= e(url('/cuentas-principales')) ?>"><?= icon('key-round') ?><span class="button-label">Cuentas</span></a>
                                                <?php else: ?>
                                                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/tipos-suscripcion?plataforma_id=' . (int) $item['id'])) ?>"><?= icon('arrow-right') ?><span class="button-label">Ver tipos</span></a>
                                                <?php endif; ?>
                                                <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/plataformas/editar/' . (int) $item['id'])) ?>"><?= icon('square-pen') ?><span class="button-label">Editar</span></a>
                                                <form method="post" action="<?= e(url('/plataformas/eliminar/' . (int) $item['id'])) ?>" class="gs-mobile-detail-action" data-confirm="<?= e('Eliminar la plataforma «' . $item['nombre'] . '». Solo se permitirá si no tiene planes ni suscripciones asociados.') ?>" data-confirm-title="Confirmar eliminación" data-confirm-button="Eliminar plataforma" data-confirm-danger="1">
                                                    <?= csrf_field() ?>
                                                    <button class="btn btn-outline-danger btn-sm" type="submit"><?= icon('trash') ?><span class="button-label">Eliminar</span></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 gs-create-column">
        <details class="card gs-form-panel shadow-sm" id="nueva-plataforma" data-form-panel <?= ($_GET['nuevo'] ?? '') === '1' || ($_SESSION['_old_action'] ?? '') === '/plataformas' ? 'open' : '' ?>>
            <summary>Nueva plataforma</summary>
            <div class="card-body">
                <form method="post" action="<?= e(url('/plataformas')) ?>" id="create-platform-form" data-form-behavior="create-platform" accept-charset="UTF-8">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= e(old('nombre')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="tipo_servicio">Tipo de servicio</label>
                        <select class="form-select" id="tipo_servicio" name="tipo_servicio" required>
                            <?php $oldType = old('tipo_servicio', 'RENOVABLE'); ?>
                            <option value="RENOVABLE" <?= $oldType === 'RENOVABLE' ? 'selected' : '' ?>>Renovable</option>
                            <option value="DESECHABLE" <?= $oldType === 'DESECHABLE' ? 'selected' : '' ?>>No renovable</option>
                        </select>
                    </div>
                    <div class="mb-3 js-dato-renovacion-wrap">
                        <label class="form-label" for="dato_renovacion">Dato requerido para renovar</label>
                        <?php $oldDato = old('dato_renovacion', 'USUARIO'); ?>
                        <select class="form-select js-dato-renovacion" id="dato_renovacion" name="dato_renovacion">
                            <option value="USUARIO" <?= $oldDato === 'USUARIO' ? 'selected' : '' ?>>Usuario</option>
                            <option value="CORREO" <?= $oldDato === 'CORREO' ? 'selected' : '' ?>>Correo</option>
                            <option value="NO_APLICA" <?= $oldDato === 'NO_APLICA' ? 'selected' : '' ?>>No aplica</option>
                        </select>
                        <small class="text-secondary js-dato-renovacion-help">Define el dato que se pedirá para renovar esta plataforma.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="duraciones_disponibles">Duraciones disponibles (meses)</label>
                        <input
                            type="text"
                            class="form-control"
                            id="duraciones_disponibles"
                            name="duraciones_disponibles"
                            value="<?= e(old('duraciones_disponibles')) ?>"
                            placeholder="Ej: 1,3,7"
                        >
                        <small class="text-secondary">Opcional. Si lo defines, solo estas duraciones estarán permitidas en los tipos de esta plataforma.</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="usa_cuentas_principales" name="usa_cuentas_principales" <?= old('usa_cuentas_principales') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="usa_cuentas_principales">Usa <strong>cuentas principales</strong> con usuarios asignados (ej. ChatGPT, Claude)</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_menos_2">Mensaje renovación (3 días antes)</label>
                        <textarea class="form-control" id="mensaje_menos_2" name="mensaje_menos_2" rows="3"><?= e(old('mensaje_menos_2')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_menos_1">Mensaje renovación (día de vencimiento)</label>
                        <textarea class="form-control" id="mensaje_menos_1" name="mensaje_menos_1" rows="3"><?= e(old('mensaje_menos_1')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_rec_7">Mensaje de recuperación (3 días después)</label>
                        <textarea class="form-control" id="mensaje_rec_7" name="mensaje_rec_7" rows="2"><?= e(old('mensaje_rec_7')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mensaje_rec_15">Mensaje de recuperación (15 días después)</label>
                        <textarea class="form-control" id="mensaje_rec_15" name="mensaje_rec_15" rows="2"><?= e(old('mensaje_rec_15')) ?></textarea>
                    </div>
                    <div class="alert alert-light border small">
                        Variables disponibles: <code>{NOMBRE}</code>, <code>{PLATAFORMA}</code>, <code>{PLAN}</code>, <code>{FECHA_VENCE}</code>, <code>{PRECIO}</code>.
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg"><?= icon('save') ?><span class="button-label">Guardar plataforma</span></button>
                </form>
            </div>
        </details>
    </div>
</div>
