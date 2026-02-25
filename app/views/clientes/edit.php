<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Editar cliente</h1>
    <a href="<?= e(url('/clientes')) ?>" class="btn btn-outline-secondary">Volver a clientes</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(url('/clientes/actualizar/' . (int) $item['id'])) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="contacto" class="form-label">Contacto</label>
                    <input type="text" class="form-control" id="contacto" name="contacto" value="<?= e((string) $item['nombre']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="numero" class="form-label">Numero</label>
                    <input type="text" class="form-control" id="numero" name="numero" value="<?= e((string) $item['telefono']) ?>" required>
                </div>
                <div class="col-12">
                    <label for="notas" class="form-label">Notas</label>
                    <textarea class="form-control" id="notas" name="notas" rows="4"><?= e((string) ($item['notas'] ?? '')) ?></textarea>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-lg w-100 w-sm-auto" type="submit">Guardar cambios</button>
                    <a href="<?= e(url('/clientes')) ?>" class="btn btn-outline-secondary btn-lg w-100 w-sm-auto">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>
