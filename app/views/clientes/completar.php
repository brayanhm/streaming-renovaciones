<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Completar contactos faltantes</h1>
        <small class="text-secondary">Actualiza contacto y numero para clientes incompletos.</small>
    </div>
    <a href="<?= e(url('/clientes')) ?>" class="btn btn-outline-secondary">Volver a clientes</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= e(url('/clientes/completar')) ?>" class="row g-2 mb-3">
            <div class="col-12 col-md-9">
                <input
                    type="text"
                    class="form-control form-control-lg"
                    name="q"
                    placeholder="Busca por contacto, numero o usuario"
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
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Plataforma</th>
                        <th>Vence</th>
                        <th>Contacto</th>
                        <th>Numero</th>
                        <th class="text-end">Guardar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                No hay clientes pendientes por completar.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $item): ?>
                        <tr>
                            <td class="fw-semibold"><?= (int) $item['id'] ?></td>
                            <td><?= e((string) ($item['usuario_proveedor'] ?? '')) ?></td>
                            <td><?= e((string) ($item['plataforma_nombre'] ?? '')) ?></td>
                            <td><?= e((string) ($item['fecha_vencimiento'] ?? '')) ?></td>
                            <td colspan="3">
                                <form method="post" action="<?= e(url('/clientes/completar/actualizar/' . (int) $item['id'])) ?>" class="row g-2">
                                    <div class="col-12 col-md-4">
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="contacto"
                                            placeholder="Contacto"
                                            value="<?= e((string) ($item['nombre'] ?? '')) ?>"
                                            required
                                        >
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="numero"
                                            placeholder="Numero"
                                            value="<?= e((string) ($item['telefono'] ?? '')) ?>"
                                            required
                                        >
                                    </div>
                                    <div class="col-12 col-md-4 d-grid d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-success">Guardar</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
