(() => {
    'use strict';
    const element = document.getElementById('gs-form-modal');
    if (!element || !window.bootstrap?.Modal) return;
    const modal = new bootstrap.Modal(element);
    const body = element.querySelector('[data-modal-body]');
    const title = element.querySelector('.modal-title');
    const status = element.querySelector('[data-modal-status]');
    const actions = element.querySelector('[data-modal-actions]');
    const dismiss = actions.querySelector('[data-bs-dismiss]');
    const dialog = element.querySelector('.modal-dialog');
    const panels = new WeakMap();
    let activePanel = null;
    let opener = null;
    let loading = null;
    let busy = false;
    let sequence = 0;
    let pinnedSubmit = null;
    let baseline = null;
    let baselineFields = [];
    let discardPrompt = null;
    let allowClose = false;
    let recordKey = null;
    // Las contraseñas y demás valores del formulario se comparan solo en memoria.
    const snapshot = () => JSON.stringify([...body.querySelectorAll('form[method=post]')].map(form => [...form.elements]
        .filter(field => field.name && !field.disabled && !['hidden','submit','button','reset'].includes(field.type))
        .map(field => [field.name, field.type === 'checkbox' || field.type === 'radio' ? field.checked
            : field.type === 'file' ? [...field.files].map(file => [file.name,file.size,file.lastModified])
            : field instanceof HTMLSelectElement && field.multiple ? [...field.selectedOptions].map(option => option.value) : field.value])));
    const isDirty = () => baseline !== null && baseline !== snapshot();
    const captureBaseline = () => {
        baseline = snapshot();
        baselineFields = [...body.querySelectorAll('input,select,textarea')].map(field => ({field,
            value:field.value, checked:field.checked,
            selected:field instanceof HTMLSelectElement ? [...field.options].map(option => option.selected) : null}));
    };
    const restoreBaseline = () => baselineFields.forEach(({field,value,checked,selected}) => {
        if (field.type === 'file') { if (!value) field.value = ''; }
        else field.value = value;
        if (typeof checked === 'boolean') field.checked = checked;
        if (selected) [...field.options].forEach((option,index) => { option.selected = selected[index]; });
        field.dispatchEvent(new Event('input',{bubbles:true}));
        field.dispatchEvent(new Event('change',{bubbles:true}));
    });
    const confirmDiscard = () => {
        if (!isDirty()) return Promise.resolve(true);
        if (!discardPrompt) discardPrompt = Promise.resolve(window.GhostAlerts?.confirm({
            title:'¿Descartar los cambios?', text:'Hay cambios sin guardar en este formulario.',
            button:'Descartar cambios', cancel:'Seguir editando', danger:true
        }) ?? window.confirm('Hay cambios sin guardar. ¿Quieres descartarlos?')).finally(() => { discardPrompt = null; });
        return discardPrompt;
    };
    const rememberOpener = trigger => {
        recordKey = trigger?.closest?.('[data-record-key]')?.dataset.recordKey || null;
        opener = trigger?.closest?.('.gs-action-menu')?.querySelector('summary') || trigger;
    };
    const focusField = () => (body.querySelector('[aria-invalid=true],input:not([type=hidden]):not([disabled]),select:not([disabled]),textarea:not([disabled])') || title).focus({preventScroll:true});
    const configure = content => {
        const wide = content?.dataset.modalSize === 'wide';
        dialog.classList.toggle('modal-xl',wide);
        dialog.classList.toggle('modal-lg',!wide);
        dismiss.textContent = body.querySelector('form[method=post]') ? 'Cancelar' : 'Cerrar';
        element.querySelector('.btn-close').setAttribute('aria-label', 'Cerrar ' + title.textContent);
    };

    const preparePanel = panel => {
        if (panels.has(panel)) return;
        const summary = panel.querySelector(':scope > summary');
        const heading = panel.querySelector('h2,h3');
        const label = panel.dataset.modalTitle || summary?.textContent.trim() || heading?.textContent.trim() || 'Formulario';
        const placeholder = document.createElement('button');
        placeholder.type = 'button';
        placeholder.className = 'btn btn-outline-primary gs-modal-trigger';
        placeholder.textContent = label;
        placeholder.setAttribute('aria-haspopup','dialog');
        placeholder.setAttribute('aria-controls',element.id);
        placeholder.addEventListener('click', () => openPanel(panel, placeholder));
        // El botón principal de creación ya existe en la cabecera del listado.
        if (panel.id && [...document.querySelectorAll('[data-open-panel]')].some(link => link.dataset.openPanel === panel.id)) placeholder.hidden = true;
        panel.before(placeholder);
        panels.set(panel, {placeholder, label, wasOpen:panel.open});
        panel.hidden = true;
    };
    const restorePanel = () => {
        if (!activePanel) return;
        const panel = activePanel;
        const {placeholder, wasOpen} = panels.get(panel);
        panel.hidden = true;
        if (panel instanceof HTMLDetailsElement) panel.open = wasOpen;
        placeholder.after(panel);
        activePanel = null;
    };
    const pinSubmit = () => {
        const forms = [...body.querySelectorAll('form')].filter(form => form.method === 'post');
        if (forms.length !== 1) return;
        const form = forms[0];
        const buttons = [...form.querySelectorAll('button[type=submit]')];
        if (!buttons.length) return;
        pinnedSubmit = {form,formId:form.id,buttons:[]};
        if (!form.id) form.id = `modal-form-${++sequence}`;
        buttons.forEach(button => {
            const placeholder = document.createComment('submit');
            pinnedSubmit.buttons.push({button,placeholder,formAttribute:button.getAttribute('form'),className:button.className});
            button.before(placeholder);
            button.setAttribute('form',form.id);
            button.classList.remove('w-100','w-sm-auto','btn-lg');
            actions.append(button);
        });
    };
    const restoreSubmit = () => {
        if (!pinnedSubmit) return;
        const {form,formId,buttons} = pinnedSubmit;
        buttons.forEach(({button,placeholder,formAttribute,className}) => {
            placeholder.replaceWith(button);
            button.className = className;
            if (formAttribute === null) button.removeAttribute('form'); else button.setAttribute('form',formAttribute);
        });
        if (!formId) form.removeAttribute('id');
        pinnedSubmit = null;
    };
    const openPanel = (panel, trigger = document.activeElement) => {
        if (!panel?.matches('details[data-form-panel],[data-modal-panel]')) return false;
        if (busy || element.classList.contains('show')) return true;
        preparePanel(panel);
        rememberOpener(trigger);
        activePanel = panel;
        title.textContent = panels.get(panel).label;
        status.textContent = '';
        body.replaceChildren(panel);
        panel.hidden = false;
        if (panel instanceof HTMLDetailsElement) panel.open = true;
        configure(panel);
        pinSubmit();
        captureBaseline();
        modal.show();
        return true;
    };
    window.GhostModals = {openPanel};
    element.addEventListener('shown.bs.modal', focusField);
    element.addEventListener('hide.bs.modal', event => {
        if (busy) { event.preventDefault(); return; }
        if (allowClose) { allowClose = false; return; }
        if (!isDirty()) return;
        event.preventDefault();
        if (discardPrompt) return;
        void confirmDiscard().then(confirmed => { if (confirmed) { restoreBaseline(); allowClose = true; modal.hide(); } });
    });
    element.addEventListener('hidden.bs.modal', () => {
        loading?.abort();
        loading = null;
        baseline = null;
        baselineFields = [];
        restoreSubmit();
        restorePanel();
        body.replaceChildren();
        if (opener?.isConnected) opener.focus({preventScroll:true});
    });
    window.addEventListener('beforeunload', event => {
        if (element.classList.contains('show') && isDirty()) { event.preventDefault(); event.returnValue = ''; }
    });

    // IDs distintos de los formularios del listado; etiquetas y referencias siguen vinculadas.
    const uniqueIds = scope => {
        const ids = new Map();
        scope.querySelectorAll('[id]').forEach(node => {
            const previous = node.id;
            node.id = `modal-${++sequence}-${previous}`;
            ids.set(previous,node.id);
        });
        scope.querySelectorAll('*').forEach(node => {
            ['for','list','aria-describedby','aria-labelledby','aria-controls','data-password-target','data-copy-source'].forEach(attribute => {
                if (!node.hasAttribute(attribute)) return;
                node.setAttribute(attribute,node.getAttribute(attribute).split(' ').map(id => ids.get(id) || id).join(' '));
            });
            ['href','data-bs-target'].forEach(attribute => {
                const value = node.getAttribute(attribute);
                if (value?.startsWith('#') && ids.has(value.slice(1))) node.setAttribute(attribute,'#' + ids.get(value.slice(1)));
            });
        });
    };
    const accountFields = scope => {
        scope.querySelectorAll('[data-password-target]').forEach(button => button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordTarget);
            if (!input) return;
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            GhostIcons.setLabel(button,visible ? 'Ocultar' : 'Mostrar');
            GhostIcons.set(button,visible ? 'eye-off' : 'eye');
            button.setAttribute('aria-pressed',String(visible));
        }));
        scope.querySelectorAll('[data-occupancy-adjust]').forEach(form => {
            const update = () => {
                form.dataset.confirm = `La ocupación de ${form.dataset.accountLabel} cambiará de ${form.elements.ocupados_antes.value} a ${form.elements.ocupados.value} ${form.dataset.occupationUnit}. Las ventas y los pagos se conservan.`;
            };
            form.elements.ocupados.addEventListener('input',update);
            update();
        });
    };
    const openRemote = async (link, replace = false) => {
        if (busy || (element.classList.contains('show') && !replace)) return;
        if (replace && !(await confirmDiscard())) return;
        if (replace) restoreBaseline();
        if (!replace) rememberOpener(link);
        baseline = null;
        baselineFields = [];
        restoreSubmit();
        restorePanel();
        loading?.abort();
        title.textContent = 'Cargando formulario…';
        status.textContent = '';
        const progress = document.createElement('p');
        progress.className = 'text-secondary my-3';
        progress.textContent = 'Cargando los datos…';
        progress.setAttribute('role','status');
        body.replaceChildren(progress);
        loading = new AbortController();
        const controller = loading;
        modal.show();
        try {
            const response = await fetch(link.href,{credentials:'same-origin', signal:controller.signal, headers:{'X-GS-Modal':'1'}});
            if (new URL(response.url).pathname.endsWith('/login')) { location.assign(response.url); return; }
            if (!response.ok) throw new Error('No se pudo abrir el formulario.');
            const doc = new DOMParser().parseFromString(await response.text(),'text/html');
            if (controller.signal.aborted) return;
            const panelId = new URL(link.href).hash.slice(1);
            const content = doc.querySelector('[data-modal-page]') || (panelId ? doc.getElementById(panelId) : null);
            if (!content) throw new Error('El formulario no está disponible. Recarga la pantalla para comprobar tus permisos.');
            title.textContent = content.dataset.modalTitle || content.querySelector('h1,summary')?.textContent || 'Formulario';
            if (content instanceof HTMLDetailsElement) content.open = true;
            content.querySelectorAll('script').forEach(script => script.remove());
            const pageHeading = content.querySelector('h1');
            if (pageHeading) {
                const header = [...content.children].find(child => child.contains(pageHeading));
                header?.remove();
            }
            content.querySelectorAll('a').forEach(anchor => {
                if (anchor.textContent.trim() === 'Cancelar') anchor.remove();
            });
            body.replaceChildren(content);
            configure(content);
            // Solo inicializadores conocidos; no se ejecutan scripts del HTML recibido.
            window.GhostFormBehaviors?.initialize(body);
            uniqueIds(body);
            accountFields(body);
            window.GhostTables?.initialize(body);
            window.GhostForms?.initialize(body);
            pinSubmit();
            captureBaseline();
            const hash = new URL(link.href).hash.slice(1);
            const destination = hash ? [...body.querySelectorAll('[id]')].find(panel => panel.id.endsWith('-' + hash)) : null;
            if (destination instanceof HTMLDetailsElement) destination.open = true;
            if (destination) {
                destination.querySelector('input:not([type=hidden]),select,textarea')?.focus({preventScroll:true});
                destination.scrollIntoView({block:'start'});
            } else focusField();
        } catch (error) {
            if (error.name === 'AbortError') return;
            const message = document.createElement('p');
            message.className = 'alert alert-danger';
            message.setAttribute('role','alert');
            message.textContent = error.message || 'No se pudo abrir el formulario. Intenta de nuevo.';
            const retry = document.createElement('button');
            retry.type = 'button'; retry.className = 'btn btn-outline-primary'; retry.textContent = 'Reintentar';
            retry.addEventListener('click', () => openRemote(link,true));
            body.replaceChildren(message,retry);
        }
    };
    document.addEventListener('click', event => {
        const close = event.target.closest('[data-modal-close]');
        if (close && element.contains(close)) { event.preventDefault(); modal.hide(); return; }
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
        const url = new URL(link.href);
        const edit = /\/(?:editar\/\d+|\d+\/editar)\/?$/.test(url.pathname)
            || /\/(?:tipos-suscripcion|modalidades)\/precios$/.test(url.pathname);
        const create = /\/(?:clientes|suscripciones|plataformas|tipos-suscripcion|cuentas-principales|chatgpt|claude)$/.test(url.pathname)
            && /^#(?:nuevo-cliente|cliente-antiguo|nueva-vigencia|nueva-plataforma|nuevo-plan|nueva-cuenta|importar-cuentas)$/.test(url.hash);
        const contextual = link.hasAttribute('data-modal-link') || /\/suscripciones\/historial\/\d+$/.test(url.pathname);
        if (url.origin === location.origin && (edit || create || contextual)) {
            event.preventDefault();
            void openRemote(link,element.contains(link));
        }
    });

    // La confirmación compartida se procesa antes de este listener. El resto conserva su formulario original.
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !element.contains(form) || event.defaultPrevented) return;
        if (form.method === 'get') {
            event.preventDefault();
            const target = new URL(form.action);
            for (const [name,value] of new FormData(form)) target.searchParams.append(name,value);
            void openRemote({href:target.href},true);
            return;
        }
        if (form.method !== 'post') return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (busy || !form.reportValidity()) return;
        busy = true;
        const data = new FormData(form);
        if (event.submitter?.name) data.append(event.submitter.name,event.submitter.value);
        const buttons = [...element.querySelectorAll('button')].map(button => ({button,disabled:button.disabled}));
        buttons.forEach(({button}) => { button.disabled = true; });
        element.setAttribute('aria-busy','true');
        status.textContent = 'Guardando…';
        try {
            const response = await fetch(form.action,{method:'POST', body:data, credentials:'same-origin', headers:{'X-GS-Modal':'1','Accept':'application/json'}});
            if (!response.headers.get('Content-Type')?.includes('application/json')) throw new Error('No se pudo confirmar el resultado. Recarga la pantalla antes de volver a guardar.');
            const result = await response.json();
            if (result.login) { baseline = null; location.assign(result.redirect); return; }
            if (response.ok && result.ok) {
                baseline = null;
                status.textContent = 'Guardado. Actualizando…';
                if (/\/eliminar\//.test(new URL(form.action).pathname) && /\/\d+$/.test(location.pathname)) {
                    location.assign(result.redirect);
                    return;
                }
                const origin = new URL(location.href);
                origin.searchParams.delete('nuevo');
                if (activePanel?.id && origin.hash === '#' + activePanel.id) origin.hash = '';
                history.replaceState(history.state,'',origin.href);
                const continueContacts = form.hasAttribute('data-contact-form') && event.submitter?.value === '1';
                const tableScrolls = [...document.querySelectorAll('main [data-scroll-key]')].map(table => ({key:table.dataset.scrollKey,x:table.scrollLeft,y:table.scrollTop}));
                try { sessionStorage.setItem('ghost.modalReturn',JSON.stringify({url:location.href,y:window.scrollY,continueContacts,recordKey,tableScrolls})); } catch (_) {}
                location.reload();
                return;
            }
            window.GhostForms?.showFeedback(form,{fields:result.fields || {},message:result.message || 'No se pudo guardar. Revisa los datos.'});
            status.textContent = 'Revisa los campos del formulario.';
        } catch (error) {
            window.GhostForms?.showFeedback(form,{message:error.message || 'No se pudo guardar. Intenta de nuevo.'});
            status.textContent = 'No se pudo confirmar el guardado.';
        } finally {
            busy = false;
            element.removeAttribute('aria-busy');
            buttons.forEach(({button,disabled}) => { button.disabled = disabled; });
        }
    },true);

    document.addEventListener('DOMContentLoaded', () => {
        let saved = null;
        try {
            saved = JSON.parse(sessionStorage.getItem('ghost.modalReturn') || 'null');
            sessionStorage.removeItem('ghost.modalReturn');
        } catch (_) {}
        const localPanels = [...document.querySelectorAll('details[data-form-panel],[data-modal-panel]')];
        const initiallyOpen = saved?.url === location.href ? null : localPanels.find(panel => panel.open || panel.querySelector('.gs-form-errors') || (panel.id && location.hash === '#' + panel.id));
        localPanels.forEach(preparePanel);
        document.querySelectorAll('[data-open-panel]').forEach(button => { button.setAttribute('aria-haspopup','dialog'); button.setAttribute('aria-controls',element.id); });
        if (initiallyOpen) openPanel(initiallyOpen);
        if (saved?.url === location.href) requestAnimationFrame(() => {
            for (const scroll of saved.tableScrolls || []) {
                const table = [...document.querySelectorAll('main [data-scroll-key]')].find(table => table.dataset.scrollKey === scroll.key);
                if (table) { table.scrollLeft = scroll.x; table.scrollTop = scroll.y; }
            }
            window.scrollTo(0,saved.y);
            const row = [...document.querySelectorAll('[data-record-key]')].find(row => row.dataset.recordKey === saved.recordKey);
            if (row) { row.classList.add('gs-row-saved'); setTimeout(() => row.classList.remove('gs-row-saved'),4000); }
        });
        if (saved?.url === location.href && saved.continueContacts) {
            const next = document.querySelector('[data-contact-complete]');
            if (next) void openRemote(next);
            else window.GhostAlerts?.toast('No quedan contactos pendientes con estos filtros.','info');
        }
    });
})();
