<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Importar FlujoTV (Cuenta Completa)</h1>
        <small class="text-secondary">Actualiza los vencimientos desde el CSV del proveedor y crea las cuentas nuevas.</small>
    </div>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Subir archivo CSV</h2>
                <form method="post" action="<?= e(url('/importar/flujotv/preview')) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="archivo">Archivo CSV del proveedor</label>
                        <input class="form-control form-control-lg" type="file" id="archivo" name="archivo" accept=".csv" required>
                        <div class="form-text">Solo archivos <strong>.csv</strong> (máx. 2 MB). Si tienes un Excel, usa "Guardar como CSV".</div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Analizar archivo</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-secondary mb-3">Cómo funciona</h2>
                <ul class="small mb-3">
                    <li>Se compara cada usuario del CSV con las cuentas <strong>cuenta completa</strong> de FlujoTV.</li>
                    <li>Si el usuario ya existe y la fecha cambió, se <strong>actualiza el vencimiento</strong>.</li>
                    <li>Si no existe, se <strong>crea</strong> con plan de 1 mes (precio del plan), inicio hoy y teléfono vacío para completar luego.</li>
                    <li>Verás un <strong>resumen para confirmar</strong> antes de aplicar nada.</li>
                    <li>Las cuentas que están en el sistema pero no en el CSV <strong>no se tocan</strong>.</li>
                </ul>
                <div class="alert alert-info small mb-0">
                    Formato esperado: columnas <code>Nombre de usuario</code> y <code>Fecha de finalización</code> (separadas por <code>;</code> o <code>,</code>). Las filas de cabecera se ignoran automáticamente.
                </div>
            </div>
        </div>
    </div>
</div>
