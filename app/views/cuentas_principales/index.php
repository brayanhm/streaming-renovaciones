<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Cuentas principales</h1>
        <small class="text-secondary">Cuentas compartidas (ChatGPT, Claude…) con usuarios asignados.</small>
    </div>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Cuenta</th>
                                <th>Plataforma</th>
                                <th>Cupos</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-4">No hay cuentas principales todavía.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $c): ?>
                                <?php
                                $ocup = (int) ($c['ocupados'] ?? 0);
                                $cap = (int) ($c['capacidad'] ?? 0);
                                $lleno = $cap > 0 && $ocup >= $cap;
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <a href="<?= e(url('/cuentas-principales/' . (int) $c['id'])) ?>" class="text-decoration-none"><?= e((string) $c['etiqueta']) ?></a>
                                        </div>
                                        <?php if (!empty($c['correo'])): ?><small class="text-secondary"><?= e((string) $c['correo']) ?></small><?php endif; ?>
                                    </td>
                                    <td><?= e((string) ($c['plataforma_nombre'] ?? '')) ?></td>
                                    <td>
                                        <span class="badge <?= $lleno ? 'text-bg-danger' : 'text-bg-success' ?>"><?= e((string) $ocup) ?> / <?= e((string) $cap) ?></span>
                                        <?php if ($lleno): ?><small class="text-danger d-block">Lleno</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) ($c['activo'] ?? 1) === 1): ?>
                                            <span class="badge text-bg-secondary">Activa</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-danger">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/cuentas-principales/' . (int) $c['id'])) ?>">Ver / Asignar</a>
                                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/cuentas-principales/editar/' . (int) $c['id'])) ?>">Editar</a>
                                        </div>
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
                <h2 class="h5 mb-3">Nueva cuenta principal</h2>
                <?php if (empty($plataformas)): ?>
                    <div class="alert alert-warning small">
                        Primero marca una plataforma como <strong>"usa cuentas principales"</strong> en el
                        <a href="<?= e(url('/plataformas')) ?>">Catálogo</a> (ej. ChatGPT Plus, Claude Pro).
                    </div>
                <?php else: ?>
                <form method="post" action="<?= e(url('/cuentas-principales')) ?>" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="plataforma_id">Plataforma</label>
                        <select class="form-select" id="plataforma_id" name="plataforma_id" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($plataformas as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"><?= e((string) $p['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="etiqueta">Etiqueta / nombre interno</label>
                        <input type="text" class="form-control" id="etiqueta" name="etiqueta" value="<?= e(old('etiqueta')) ?>" placeholder="Ej: ChatGPT Plus #1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="correo">Correo de la cuenta</label>
                        <input type="text" class="form-control" id="correo" name="correo" value="<?= e(old('correo')) ?>" placeholder="correo@dominio.com" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_cuenta">Contraseña</label>
                        <input type="text" class="form-control" id="password_cuenta" name="password_cuenta" placeholder="Se guarda cifrada" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="capacidad">Capacidad (máx. usuarios)</label>
                        <input type="number" class="form-control" id="capacidad" name="capacidad" min="1" value="<?= e(old('capacidad', '1')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notas">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="2"><?= e(old('notas')) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg">Crear cuenta principal</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
