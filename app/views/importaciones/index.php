<?php
declare(strict_types=1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Importaciones</h1>
        <small class="text-secondary">Carga los CSV del proveedor para actualizar y crear cuentas.</small>
    </div>
    <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <h2 class="h5 mb-2">FlujoTV · Cuenta Completa</h2>
                <p class="text-secondary small flex-grow-1">
                    Actualiza los vencimientos de las cuentas existentes y crea las nuevas.
                    El CSV trae usuario y fecha de finalización.
                </p>
                <a href="<?= e(url('/importar/flujotv')) ?>" class="btn btn-primary">Abrir importador</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <h2 class="h5 mb-2">FlujoTV · Por dispositivos</h2>
                <p class="text-secondary small flex-grow-1">
                    Cuentas desechables (no renovables): todas se crean como nuevas.
                    Omite duplicados exactos ya registrados.
                </p>
                <a href="<?= e(url('/importar/flujotv-dispositivos')) ?>" class="btn btn-primary">Abrir importador</a>
            </div>
        </div>
    </div>
</div>
