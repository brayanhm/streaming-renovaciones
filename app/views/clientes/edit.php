<?php
declare(strict_types=1);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Editar cliente</h1>
    <a href="<?= e(url('/clientes')) ?>" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(url('/clientes/actualizar/' . (int) $item['id'])) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= e((string) $item['nombre']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="telefono" class="form-label">Telefono</label>
                    <input type="text" class="form-control" id="telefono" name="telefono" value="<?= e((string) $item['telefono']) ?>" required>
                </div>
                <div class="col-12">
                    <label for="notas" class="form-label">Notas</label>
                    <textarea class="form-control" id="notas" name="notas" rows="4"><?= e((string) ($item['notas'] ?? '')) ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary btn-lg" type="submit">Guardar cambios</button>
                    <a href="<?= e(url('/clientes')) ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>
