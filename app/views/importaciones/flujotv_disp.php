<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Importar FlujoTV (Por dispositivos)</h1>
        <small class="text-secondary">Cuentas desechables: todas se crean como nuevas.</small>
    </div>
    <a href="<?= e(url('/importar')) ?>" class="btn btn-outline-secondary">Volver a importaciones</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Subir archivo CSV</h2>
                <form method="post" action="<?= e(url('/importar/flujotv-dispositivos/preview')) ?>" enctype="multipart/form-data">
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
                    <li>Al ser desechables, <strong>todas las filas se crean</strong> (no se actualizan fechas).</li>
                    <li>Se asume <strong>1 dispositivo</strong>; el plan (1/3/7 meses) se elige según el crédito.</li>
                    <li>Precio/costo <strong>explícitos del plan</strong>; inicio y vencimiento tomados del CSV.</li>
                    <li>Se <strong>omiten duplicados exactos</strong> (mismo usuario y mismas fechas) ya registrados o repetidos en el archivo.</li>
                    <li>Verás un <strong>resumen para confirmar</strong> antes de crear nada.</li>
                </ul>
                <div class="alert alert-info small mb-0">
                    Columnas esperadas: <code>usuario ; contraseña ; crédito ; fecha inicio ; fecha finalización ; …</code> La contraseña se ignora (no hay campo para guardarla).
                </div>
            </div>
        </div>
    </div>
</div>
