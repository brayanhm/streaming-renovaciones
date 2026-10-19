(() => {
    'use strict';
    const form = document.querySelector('[data-distribution-delivery]');
    if (!form) return;
    const buttons = [...form.querySelectorAll('button[name="dispositivos"]')];
    const labels = new Map(buttons.map(button => [button, button.querySelector('.button-label').textContent]));
    const status = document.querySelector('[data-distribution-status]');
    const outputBox = document.querySelector('[data-distribution-output]');
    const output = document.getElementById('distribution-client-text');
    const notice = document.querySelector('[data-distribution-notice]');
    let busy = false;
    let pendingDevices = null;

    const availability = () => {
        buttons.forEach(button => {
            const devices = Number(button.value);
            button.disabled = busy || form.dataset.eligible !== '1'
                || (pendingDevices !== null && devices !== pendingDevices);
            button.querySelector('.button-label').textContent = busy ? 'Registrando…' : labels.get(button);
        });
    };
    const showStatus = (message, success, feedback = null) => {
        status.textContent = message;
        status.className = `small mt-3 mb-0 ${feedback === 'warning' ? 'text-warning' : (success ? 'text-success' : 'text-danger')}`;
        if (feedback && window.GhostAlerts) {
            if (feedback === 'success') GhostAlerts.success(message);
            else if (feedback === 'warning') GhostAlerts.warning(message, 'Entrega registrada');
            else GhostAlerts.error(message);
        }
    };
    const reserveClipboard = () => {
        const unavailable = {resolve:() => {}, cancel:() => {}, result:Promise.resolve(false)};
        if (!window.isSecureContext || !navigator.clipboard || !navigator.clipboard.write || typeof ClipboardItem === 'undefined') return unavailable;
        let resolveText;
        let rejectText;
        const text = new Promise((resolve, reject) => { resolveText = resolve; rejectText = reject; });
        const blob = text.then(value => new Blob([value], {type:'text/plain'}));
        blob.catch(() => {});
        try {
            // Reservar la copia durante el clic conserva la activación mientras se confirma la venta.
            const result = navigator.clipboard.write([new ClipboardItem({'text/plain':blob})]).then(() => true, () => false);
            return {resolve:resolveText, cancel:() => rejectText(new Error('Entrega no confirmada')), result};
        } catch (_) {
            rejectText(new Error('Copia reservada no disponible'));
            return unavailable;
        }
    };
    // Algunos navegadores dejan la solicitud pendiente si no resuelven el permiso.
    // La venta confirmada debe terminar aunque el portapapeles no responda.
    const finishCopy = operation => new Promise(resolve => {
        const timeout = setTimeout(() => resolve(false), 3000);
        Promise.resolve(operation).then(result => {
            clearTimeout(timeout); resolve(result !== false);
        }, () => { clearTimeout(timeout); resolve(false); });
    });
    const copy = async () => {
        let copied = false;
        if (navigator.clipboard && window.isSecureContext) {
            try {
                copied = await finishCopy(navigator.clipboard.writeText(output.value));
            } catch (_) { /* El mensaje también queda disponible para copiar manualmente. */ }
        }
        if (!copied) {
            const previousFocus = document.activeElement;
            output.focus({preventScroll:true});
            output.select();
            try { copied = document.execCommand('copy'); } catch (_) { copied = false; }
            if (copied && previousFocus) previousFocus.focus({preventScroll:true});
        }
        if (!copied) {
            outputBox.scrollIntoView({block:'center'});
            output.focus({preventScroll:true});
            output.select();
        }
        return copied;
    };
    const updateAccount = payload => {
        form.dataset.occupied = String(payload.occupied);
        form.dataset.capacity = String(payload.capacity);
        form.dataset.eligible = payload.eligible ? '1' : '0';
        document.querySelector('[data-distribution-occupied]').textContent = payload.occupied;
        document.querySelector('[data-distribution-capacity]').textContent = payload.capacity;
        document.querySelector('[data-distribution-remaining]').textContent = payload.remaining;
        document.querySelector('[data-distribution-started]').textContent = payload.distributionStartedAt;
        document.querySelector('[data-distribution-completed]').textContent = payload.distributionCompletedAt;
        const state = document.querySelector('[data-distribution-state]');
        state.textContent = payload.state;
        state.className = 'badge text-bg-primary';
        notice.hidden = payload.eligible && payload.occupied < payload.capacity;
        notice.textContent = !payload.eligible ? 'La cuenta ya no está disponible. Recarga la pantalla de distribución.'
            : 'Capacidad de referencia alcanzada. Puedes seguir entregando en esta cuenta hasta que el administrador seleccione otra.';
    };
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy) return;
        const devices = Number(event.submitter ? event.submitter.value : '1');
        if (!buttons.some(button => Number(button.value) === devices && !button.disabled)) return;
        busy = true;
        pendingDevices = devices;
        availability();
        showStatus('Registrando la entrega…', true);
        const data = new FormData(form);
        data.set('dispositivos', String(devices));
        const reservedCopy = reserveClipboard();
        try {
            const response = await fetch(form.action, {method:'POST', body:data, credentials:'same-origin', headers:{Accept:'application/json'}});
            if (response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) {
                showStatus('La sesión o el formulario expiró. Recarga la página antes de continuar.', false, 'error');
                form.dataset.eligible = '0';
                return;
            }
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                if (!payload.retrySameQuantity && response.status < 500) pendingDevices = null;
                showStatus(payload.message || 'No se pudo confirmar la entrega. Reintenta con la misma cantidad.', false, 'error');
                return;
            }
            // La venta ya fue confirmada: copiar de nuevo nunca vuelve a enviar este formulario.
            form.querySelector('[name="token_operacion"]').value = payload.nextToken;
            pendingDevices = null;
            updateAccount(payload);
            output.value = payload.text;
            outputBox.hidden = false;
            document.querySelector('[data-new-delivery]').open = false;
            reservedCopy.resolve(payload.text);
            const copied = await finishCopy(reservedCopy.result) || await copy();
            showStatus(`Entrega registrada: ${payload.devices} ${payload.devices === 1 ? 'cupo ocupado' : 'cupos ocupados'}. `
                + (copied ? 'Texto copiado al portapapeles.' : 'Copia el texto manualmente o pulsa «Volver a copiar». Los cupos ya están registrados.'), copied, copied ? 'success' : 'warning');
            if (copied) {
                outputBox.scrollIntoView({block:'nearest'});
                document.querySelector('[data-distribution-copy-again]').focus({preventScroll:true});
            }
        } catch (_) {
            showStatus('No se pudo confirmar la entrega. Reintenta con la misma cantidad para recuperar el resultado sin duplicar cupos.', false, 'error');
        } finally {
            reservedCopy.cancel();
            busy = false;
            availability();
        }
    });
    document.querySelector('[data-distribution-copy-again]').addEventListener('click', async () => {
        if (!output.value) return;
        const copied = await copy();
        showStatus(copied ? 'Texto copiado. La ocupación se conserva.' : 'Selecciona el texto y cópialo manualmente. La ocupación se conserva.', copied, copied ? 'success' : 'warning');
    });
    availability();
})();
