(() => {
    'use strict';
    const available = typeof window.Swal !== 'undefined';
    const noticeQueue = [];
    let noticeVisible = false;
    let modalCount = 0;
    let modalQueue = Promise.resolve();
    const colors = {success:'var(--gs-success)', error:'var(--gs-danger)', warning:'var(--gs-warning)', info:'var(--gs-primary)'};
    const dialog = available ? Swal.mixin({
        background:'var(--gs-surface)', color:'var(--gs-text)', heightAuto:false,
        buttonsStyling:false, returnFocus:true,
        customClass:{container:'gs-dialog-container', popup:'gs-dialog', title:'gs-dialog-title',
            htmlContainer:'gs-dialog-text', confirmButton:'btn btn-primary', cancelButton:'btn btn-outline-secondary'},
    }) : null;

    const drainNotices = () => {
        if (!available || noticeVisible || modalCount > 0 || noticeQueue.length === 0) return;
        const {message, type} = noticeQueue.shift();
        noticeVisible = true;
        Swal.fire({
            toast:true, titleText:message, icon:type, iconColor:colors[type],
            position:window.matchMedia('(max-width: 575.98px)').matches ? 'top' : 'top-end',
            background:'var(--gs-surface)', color:'var(--gs-text)', heightAuto:false,
            timer:Math.min(6500, Math.max(3000, message.length * 35)), timerProgressBar:true,
            showConfirmButton:false, showCloseButton:true, closeButtonAriaLabel:'Cerrar aviso', returnFocus:false,
            customClass:{container:'gs-toast-container', popup:`gs-toast gs-toast-${type}`},
            didOpen:popup => {
                popup.addEventListener('mouseenter', Swal.stopTimer);
                popup.addEventListener('mouseleave', Swal.resumeTimer);
                popup.addEventListener('focusin', Swal.stopTimer);
                popup.addEventListener('focusout', Swal.resumeTimer);
            },
        }).finally(() => { noticeVisible = false; drainNotices(); });
    };
    const showDialog = options => {
        if (!available) return Promise.resolve({isConfirmed:false});
        modalCount++;
        if (noticeVisible) Swal.close();
        const show = () => dialog.fire(options);
        const result = modalQueue.then(show, show);
        modalQueue = result.catch(() => {});
        return result.finally(() => { modalCount--; drainNotices(); });
    };
    const toast = (message, type = 'success') => {
        if (!available || !String(message).trim()) return;
        type = Object.prototype.hasOwnProperty.call(colors, type) ? type : 'info';
        noticeQueue.push({message:String(message), type});
        drainNotices();
    };
    const messageDialog = (message, type, title) => showDialog({
        titleText:title, text:String(message), icon:type, iconColor:colors[type],
        confirmButtonText:'Entendido', allowOutsideClick:false,
    });
    window.GhostAlerts = {
        toast,
        success:message => toast(message, 'success'),
        error:(message, title = 'No se pudo completar la operación') => messageDialog(message, 'error', title),
        warning:(message, title = 'Revisa esta información') => messageDialog(message, 'warning', title),
        async confirm({text, title = 'Confirmar acción', button = 'Confirmar', danger = false}) {
            if (!available) return window.confirm(text);
            const result = await showDialog({
                titleText:title, text, icon:'warning', iconColor:colors.warning,
                showCancelButton:true, confirmButtonText:button, cancelButtonText:'Cancelar',
                focusCancel:true, allowOutsideClick:false,
                customClass:{container:'gs-dialog-container', popup:'gs-dialog', title:'gs-dialog-title',
                    htmlContainer:'gs-dialog-text', confirmButton:`btn ${danger ? 'btn-danger' : 'btn-primary'}`,
                    cancelButton:'btn btn-outline-secondary'},
            });
            return result.isConfirmed;
        },
    };

    // Interceptar antes que los controladores locales; enviar el formulario original solo al confirmar.
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || event.defaultPrevented) return;
        if (form.dataset.confirmApproved === '1') {
            delete form.dataset.confirmApproved;
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        if (form.dataset.confirmPending === '1') return;
        if (!form.reportValidity()) return;
        form.dataset.confirmPending = '1';
        const submitter = event.submitter;
        try {
            const confirmed = await GhostAlerts.confirm({
                text:form.dataset.confirm, title:form.dataset.confirmTitle,
                button:form.dataset.confirmButton, danger:form.dataset.confirmDanger === '1',
            });
            if (confirmed && form.isConnected && form.reportValidity()) {
                form.dataset.confirmApproved = '1';
                if (submitter && submitter.form === form && !submitter.disabled) form.requestSubmit(submitter);
                else form.requestSubmit();
            }
        } finally {
            delete form.dataset.confirmPending;
            delete form.dataset.confirmApproved;
        }
    }, true);

    document.addEventListener('DOMContentLoaded', () => {
        if (!available) return;
        const messages = [...document.querySelectorAll('[data-flash-message]')];
        messages.sort((a, b) => Number(['danger','warning'].includes(b.dataset.flashType)) - Number(['danger','warning'].includes(a.dataset.flashType)));
        messages.forEach(element => {
            const message = element.querySelector('[data-flash-text]').textContent.trim();
            const type = element.dataset.flashType;
            if (type === 'danger' && [...document.querySelectorAll('.gs-form-errors')].some(summary => summary.textContent.includes(message))) {
                element.remove();
                return;
            }
            if (message) {
                if (type === 'danger') GhostAlerts.error(message);
                else if (type === 'warning') GhostAlerts.warning(message);
                else toast(message, type === 'success' ? 'success' : 'info');
            }
            element.remove();
        });
    });
})();
