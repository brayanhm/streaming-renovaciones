<?php if (($section ?? '') === 'auth'): ?>
</main>
<?php else: ?>
        </div><!-- .app-content -->
    </main>
</div><!-- .app-layout -->
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (($section ?? '') !== 'auth'): ?>
<div class="modal gs-form-modal" id="gs-form-modal" tabindex="-1" aria-labelledby="gs-form-modal-title" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="gs-form-modal-title" tabindex="-1">Formulario</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar formulario"><?= icon('x') ?></button>
            </div>
            <div class="modal-body" data-modal-body></div>
            <div class="modal-footer justify-content-between">
                <span class="small text-secondary" data-modal-status role="status" aria-live="polite"></span>
                <div class="d-flex flex-wrap gap-2" data-modal-actions>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= e(url('/js/form-behaviors.js') . '?v=' . filemtime(PUBLIC_PATH . '/js/form-behaviors.js')) ?>"></script>
<script src="<?= e(url('/js/modals.js') . '?v=' . filemtime(PUBLIC_PATH . '/js/modals.js')) ?>"></script>
<?php endif; ?>
</body>
</html>
