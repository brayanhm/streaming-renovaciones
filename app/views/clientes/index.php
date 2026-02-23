<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Clientes</h1>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al dashboard</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" action="<?= e(url('/clientes')) ?>" class="row g-2 mb-3">
                    <div class="col-12 col-md-9">
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            name="q"
                            placeholder="Buscar por nombre o telefono"
                            value="<?= e($search ?? '') ?>"
                        >
                    </div>
                    <div class="col-12 col-md-3 d-grid">
                        <button class="btn btn-primary btn-lg" type="submit">Buscar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Telefono</th>
                                <th>Notas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="4" class="text-center text-secondary py-4">Sin registros.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $item['nombre']) ?></td>
                                    <td><?= e((string) $item['telefono']) ?></td>
                                    <td><?= e((string) ($item['notas'] ?? '')) ?></td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-end">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/clientes/editar/' . (int) $item['id'])) ?>">Editar</a>
                                            <form method="post" action="<?= e(url('/clientes/eliminar/' . (int) $item['id'])) ?>" onsubmit="return confirm('Eliminar cliente?')">
                                                <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
                                            </form>
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
                <h2 class="h5 mb-3">Nuevo cliente</h2>
                <form method="post" action="<?= e(url('/clientes')) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= e(old('nombre')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="telefono">Telefono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?= e(old('telefono')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notas">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"><?= e(old('notas')) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg">Guardar cliente</button>
                </form>
            </div>
        </div>
    </div>
</div>
