<?php
declare(strict_types=1);
$canAdmin = $canAdmin ?? false;
$templates = $templates ?? [];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h1 class="h3 mb-1">Textos de ChatGPT</h1><p class="text-secondary mb-0">Configura el texto que copiarás para entregar una cuenta o solicitar su renovación.</p></div>
    <a href="<?= e(url('/chatgpt')) ?>" class="btn btn-outline-secondary">Volver a ChatGPT</a>
</div>
<div class="card shadow-sm mb-3"><div class="card-body">
    <h2 class="h6">Variables disponibles</h2>
    <div class="d-flex flex-wrap gap-2 mb-2">
        <?php foreach (['{CORREO}', '{PASSWORD}', '{DISPOSITIVOS}', '{DISPOSITIVO_LABEL}', '{PRECIO}', '{FECHA_VENCE}', '{CUENTA}'] as $variable): ?><code class="border rounded px-2 py-1"><?= e($variable) ?></code><?php endforeach; ?>
    </div>
    <p class="small text-secondary mb-0">En las entregas, las variables se reemplazan con los datos de la venta: precio de 30 Bs o 55 Bs y fecha de vencimiento. Para el texto general de renovación usa {CORREO}, {CUENTA} y {FECHA_VENCE}; escribe los dos precios directamente si deseas incluirlos.</p>
</div></div>
<form method="post" action="<?= e(url('/chatgpt/plantillas')) ?>" data-chatgpt-submit>
    <?= csrf_field() ?>
    <div class="row g-3">
        <?php foreach (['entrega_1' => ['Entrega: 1 dispositivo', '30 Bs · ocupa 1 cupo'], 'entrega_2' => ['Entrega: 2 dispositivos', '55 Bs · ocupa 2 cupos'], 'renovacion' => ['Renovación', 'Para buscar a los compradores por el correo en WhatsApp y enviarles el texto.']] as $key => [$title, $description]): ?>
            <div class="<?= $key === 'renovacion' ? 'col-12' : 'col-lg-6' ?>">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <label class="form-label h5" for="<?= e($key) ?>"><?= e($title) ?></label>
                    <p class="small text-secondary"><?= e($description) ?></p>
                    <textarea class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" rows="7" maxlength="10000" placeholder="Mensaje pendiente de configurar" <?= $canAdmin ? '' : 'readonly' ?>><?= e((string) ($templates[$key] ?? '')) ?></textarea>
                </div></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($canAdmin): ?><button type="submit" class="btn btn-primary mt-3">Guardar textos</button><?php endif; ?>
</form>
<?php require __DIR__ . '/scripts.php'; ?>
