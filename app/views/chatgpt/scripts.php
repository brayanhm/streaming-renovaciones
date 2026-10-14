<script>
(() => {
    'use strict';
    document.querySelectorAll('[data-password-target]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordTarget);
            if (!input) return;
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            GhostIcons.setLabel(button, visible ? 'Ocultar' : 'Mostrar');
            GhostIcons.set(button, visible ? 'eye-off' : 'eye');
            button.setAttribute('aria-pressed', String(visible));
        });
    });

    document.querySelectorAll('[data-chatgpt-submit]').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = '1';
            form.querySelectorAll('button[type="submit"]').forEach(button => {
                button.disabled = true;
                GhostIcons.setLabel(button, 'Guardando…');
            });
        });
    });

    const copyBox = document.getElementById('chatgpt-copy-box');
    const copyOutput = document.getElementById('chatgpt-copy-text');
    const copyStatus = document.getElementById('chatgpt-copy-status');
    const copyText = async text => {
        if (!text || !copyBox || !copyOutput || !copyStatus) return;
        copyOutput.value = text;
        copyBox.hidden = false;
        let copied = false;
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                copied = true;
            } catch (_) { /* El texto queda disponible para la copia manual. */ }
        }
        if (!copied) {
            const previousFocus = document.activeElement;
            const scratch = document.createElement('textarea');
            scratch.value = text;
            scratch.setAttribute('readonly', '');
            scratch.style.cssText = 'position:fixed;left:-9999px;top:0;';
            document.body.appendChild(scratch);
            scratch.select();
            try { copied = document.execCommand('copy'); } catch (_) { copied = false; }
            scratch.remove();
            if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus({preventScroll: true});
        }
        copyStatus.textContent = copied ? 'Texto copiado. Ya puedes pegarlo en WhatsApp.' : 'Selecciona el texto y usa Ctrl+C para copiarlo. En celular, mantén presionado y elige Copiar.';
        copyStatus.className = copied ? 'small text-success mb-2' : 'small text-warning mb-2';
        if (!copied) {
            copyBox.scrollIntoView({behavior: 'smooth', block: 'center'});
            copyOutput.focus({preventScroll: true});
            copyOutput.select();
        }
    };
    document.querySelectorAll('[data-copy-source]').forEach(button => {
        button.addEventListener('click', () => {
            const source = document.getElementById(button.dataset.copySource);
            if (source) void copyText(source.value || source.textContent || '');
        });
    });
    const deliveredSale = document.querySelector('[data-copy-on-load]');
    if (deliveredSale) void copyText(deliveredSale.value);

    const saleForm = document.querySelector('[data-chatgpt-sale]');
    if (saleForm) {
        const updateSale = () => {
            const selected = saleForm.querySelector('[name="dispositivos"]:checked');
            const devices = selected ? selected.value : '1';
            const configured = saleForm.querySelector(devices === '2' ? '[data-template-two]' : '[data-template-one]').value === '1';
            saleForm.querySelector('[data-sale-devices]').textContent = devices;
            saleForm.querySelector('[data-sale-device-unit]').textContent = devices === '1' ? 'dispositivo' : 'dispositivos';
            const occupied = Number(saleForm.dataset.occupied);
            const recommended = Number(saleForm.dataset.recommended);
            const projected = occupied + Number(devices);
            const recommendationWarning = saleForm.querySelector('[data-sale-recommendation-warning]');
            recommendationWarning.hidden = projected <= recommended;
            const canExceed = saleForm.dataset.canExceed === '1';
            recommendationWarning.textContent = canExceed
                ? `Venta adicional del administrador: quedarán ${projected} dispositivos ocupados, ${projected - recommended} por encima de la capacidad de ${recommended}.`
                : 'No hay suficientes cupos para esta venta. Selecciona menos dispositivos o consulta al administrador.';
            saleForm.querySelector('[data-sale-button]').disabled = !canExceed && projected > recommended;
            GhostIcons.setLabel(saleForm.querySelector('[data-sale-button]'), configured ? 'Registrar venta y copiar' : 'Registrar venta');
            saleForm.querySelector('[data-sale-template-status]').textContent = configured ? 'Se preparará el texto de entrega al registrar la venta.' : 'Mensaje pendiente de configurar. La venta se registrará y podrás copiarlo después.';
            const date = saleForm.querySelector('[name="fecha_venta"]').value;
            const expiry = date < saleForm.dataset.paidUntil ? saleForm.dataset.paidUntil : saleForm.dataset.graceUntil;
            const parts = (expiry || '').split('-');
            saleForm.querySelector('[data-sale-expiry]').textContent = parts.length === 3 ? [parts[2], parts[1], parts[0]].join('/') : '—';
        };
        saleForm.addEventListener('change', updateSale);
        updateSale();
    }
})();
</script>
