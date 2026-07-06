<?php
declare(strict_types=1);

$ocup = (int) ($cuenta['ocupados'] ?? 0);
$cap = (int) ($cuenta['capacidad'] ?? 0);
$libres = max(0, $cap - $ocup);
$lleno = $libres <= 0;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1"><?= e((string) ($cuenta['etiqueta'] ?? '')) ?></h1>
        <p class="text-secondary mb-0"><?= e((string) ($cuenta['plataforma_nombre'] ?? '')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('/cuentas-principales/editar/' . (int) $cuenta['id'])) ?>" class="btn btn-outline-primary">Editar</a>
        <a href="<?= e(url('/cuentas-principales')) ?>" class="btn btn-outline-secondary">Volver</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-secondary mb-1">Cupos</h2>
            <div class="h4 mb-0 <?= $lleno ? 'text-danger' : 'text-success' ?>"><?= e((string) $ocup) ?> / <?= e((string) $cap) ?></div>
            <small class="text-secondary"><?= $lleno ? 'Sin cupos disponibles' : $libres . ' disponible(s)' ?></small>
        </div></div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-secondary mb-1">Acceso de la cuenta</h2>
            <div><strong>Correo:</strong> <?= e((string) ($cuenta['correo'] ?? '') ?: '—') ?></div>
            <div><strong>Contraseña:</strong> <?= e(($password ?? '') !== '' ? $password : '—') ?></div>
            <?php if (!empty($cuenta['notas'])): ?><small class="text-secondary d-block mt-1"><?= e((string) $cuenta['notas']) ?></small><?php endif; ?>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom"><h2 class="h6 mb-0">Usuarios asignados</h2></div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr><th>Usuario</th><th>Teléfono</th><th>Departamento</th><th>Vencimiento</th><th>WhatsApp</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($asignados)): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-4">Sin usuarios asignados.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($asignados as $a): ?>
                                <?php
                                $dias = (int) ($a['dias_para_vencer'] ?? 0);
                                $noRenovo = (int) ($a['flag_no_renovo'] ?? 0) === 1;
                                ?>
                                <tr class="<?= $noRenovo ? 'opacity-50' : '' ?>">
                                    <td>
                                        <div class="fw-semibold"><a href="<?= e(url('/clientes/' . (int) ($a['cliente_id'] ?? 0))) ?>" class="text-decoration-none"><?= e((string) ($a['cliente_nombre'] ?? '')) ?></a></div>
                                        <?php if ($noRenovo): ?><small class="text-danger">No renovó</small><?php endif; ?>
                                    </td>
                                    <td><?= e((string) ($a['cliente_telefono'] ?? '')) ?></td>
                                    <td><span class="badge text-bg-secondary"><?= e((string) ($a['departamento'] ?? '—')) ?></span></td>
                                    <td>
                                        <div><?= e((string) ($a['fecha_vencimiento'] ?? '')) ?></div>
                                        <small class="<?= $dias < 0 ? 'text-danger' : ($dias <= 3 ? 'text-warning' : 'text-secondary') ?>">
                                            <?= $dias < 0 ? 'Vencido hace ' . abs($dias) . 'd' : ($dias === 0 ? 'Vence hoy' : 'En ' . $dias . 'd') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= e(url('/suscripciones/whatsapp/' . (int) $a['id'])) ?>" target="_blank" rel="noopener">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-success btn-sm">WhatsApp</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Asignar usuario</h2>
                <?php if ($lleno): ?>
                    <div class="alert alert-warning small mb-0">Cupo lleno. Aumenta la capacidad o libera un cupo para asignar más usuarios.</div>
                <?php else: ?>
                <form method="post" action="<?= e(url('/cuentas-principales/' . (int) $cuenta['id'] . '/asignar')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="nombre">Nombre del usuario</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="numero">Número (celular)</label>
                        <input type="text" class="form-control" id="numero" name="numero" placeholder="Ej: 79625801" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="departamento">Departamento</label>
                        <select class="form-select" id="departamento" name="departamento" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach (($departamentos ?? []) as $d): ?>
                                <option value="<?= e($d) ?>"><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="fecha_inicio">Fecha de inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= e(date('Y-m-d')) ?>" required>
                        <small class="text-secondary">Duración: 1 mes (el vencimiento se calcula solo).</small>
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg">Asignar usuario</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
