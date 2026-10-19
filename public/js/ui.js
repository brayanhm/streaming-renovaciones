(() => {
    'use strict';
    const root = document.documentElement;
    root.dataset.js = 'true';
    document.addEventListener('DOMContentLoaded', () => {
        const openPanel = (panel, focus = true) => {
            if (!panel) return;
            if (window.GhostModals?.openPanel(panel)) return;
            if (panel instanceof HTMLDetailsElement) panel.open = true;
            panel.querySelectorAll('details[data-form-panel]').forEach(child => { child.open = true; });
            if (focus) {
                panel.scrollIntoView({block:'start', behavior:'auto'});
                panel.querySelector('input:not([type=hidden]):not([disabled]),select:not([disabled]),textarea:not([disabled])')?.focus({preventScroll:true});
            }
        };
        document.querySelectorAll('[data-open-panel]').forEach(link => link.addEventListener('click', event => {
            const panel = document.getElementById(link.dataset.openPanel);
            if (!panel) return;
            event.preventDefault();
            openPanel(panel);
        }));
        if (location.hash && !window.GhostModals) openPanel(document.getElementById(location.hash.slice(1)), false);
        const dashboardFilters = document.querySelector('[data-dashboard-filters]');

        const desktop = window.matchMedia('(min-width:992px)');
        const adaptPanels = () => document.querySelectorAll('details[data-desktop-open]').forEach(panel => { panel.open = desktop.matches; });
        adaptPanels();
        desktop.addEventListener('change', adaptPanels);

        // Se conserva una sola tabla y un solo formulario por registro, también en móvil.
        let tableSequence = 0;
        const initializeTables = scope => scope.querySelectorAll('table').forEach(table => {
            const container = table.closest('.table-responsive');
            if (container) {
                container.classList.add('gs-table-scroll');
                if (!container.dataset.scrollKey) container.dataset.scrollKey = 'table-' + [...document.querySelectorAll('.table-responsive')].indexOf(container);
                if (table.hasAttribute('data-responsive-cards')) container.classList.add('gs-card-scroll');
            }
            if (!table.hasAttribute('data-responsive-cards')) return;
            if (table.dataset.cardsReady) return;
            table.dataset.cardsReady = '1';
            const tableIndex = tableSequence++;
            const headers = [...table.tHead?.rows[0]?.cells || []];
            headers.forEach(header => { if (!header.hasAttribute('scope')) header.scope = 'col'; });
            const primary = (table.dataset.mobilePrimary || '').split(',').filter(Boolean).map(Number);
            const desktopHidden = (table.dataset.desktopSecondary || '').split(',').filter(Boolean).map(Number);
            [...table.tBodies].forEach(body => [...body.rows].forEach((row, rowIndex) => {
                const cells = [...row.cells];
                if (cells.length !== headers.length || cells.some(cell => cell.colSpan > 1)) return;
                let hasDetails = false;
                cells.forEach((cell, index) => {
                    cell.dataset.label = headers[index].textContent.trim() || 'Acciones';
                    const content = document.createElement('div');
                    content.className = 'gs-cell-content';
                    while (cell.firstChild) content.append(cell.firstChild);
                    cell.append(content);
                    if (primary.length && !primary.includes(index)) { cell.classList.add('gs-mobile-secondary'); hasDetails = true; }
                    if (desktopHidden.includes(index)) { cell.classList.add('gs-desktop-secondary'); hasDetails = true; }
                });
                if (!hasDetails) return;
                const id = `row-details-${tableIndex}-${rowIndex}`;
                row.id = row.id || id;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-outline-secondary btn-sm gs-row-details';
                button.textContent = 'Ver detalles';
                button.setAttribute('aria-expanded','false');
                button.setAttribute('aria-controls', row.id);
                button.addEventListener('click', () => {
                    const expanded = row.classList.toggle('gs-row-expanded');
                    button.setAttribute('aria-expanded',String(expanded));
                    button.textContent = expanded ? 'Ocultar detalles' : 'Ver detalles';
                });
                cells[0].querySelector('.gs-cell-content').append(button);
            }));
            table.classList.add('gs-card-table');
        });
        window.GhostTables = {initialize:initializeTables};
        initializeTables(document);

        // El desplegable se posiciona en la ventana para no cortarse dentro de la tabla.
        const closeMenus = except => document.querySelectorAll('.gs-action-menu[open]').forEach(menu => { if (menu !== except) menu.open = false; });
        document.addEventListener('toggle', event => {
            const menu = event.target;
            if (!menu.matches?.('.gs-action-menu') || !menu.open) return;
            closeMenus(menu);
            const popup = menu.querySelector('.gs-action-popover');
            if (!popup) return;
            const rect = menu.querySelector('summary').getBoundingClientRect();
            const width = Math.min(240, window.innerWidth - 24);
            popup.style.width = width + 'px';
            popup.style.left = Math.max(12, Math.min(rect.right - width, window.innerWidth - width - 12)) + 'px';
            const height = popup.getBoundingClientRect().height;
            popup.style.top = (rect.bottom + height + 12 > window.innerHeight && rect.top > height + 12 ? rect.top - height - 4 : rect.bottom + 4) + 'px';
        },true);
        document.addEventListener('click', event => {
            if (!event.target.closest('.gs-action-menu')) closeMenus();
            else if (event.target.closest('.gs-action-popover a,.gs-action-popover button')) closeMenus();
        });
        document.addEventListener('keydown', event => {
            if (event.key !== 'Escape' || document.querySelector('.gs-form-modal.show')) return;
            const menu = document.querySelector('.gs-action-menu[open]');
            if (menu) { menu.open = false; menu.querySelector('summary').focus(); event.preventDefault(); }
        });
        window.addEventListener('resize', () => closeMenus());
        document.addEventListener('scroll', event => { if (!event.target.closest?.('.gs-action-popover')) closeMenus(); },true);

        const fieldName = field => field.labels?.[0]?.textContent.trim().replace(/\s+/g,' ') || field.getAttribute('aria-label') || 'Este campo';
        const clearError = field => {
            const errorId = field.dataset.fieldErrorId;
            if (!errorId) return;
            document.getElementById(errorId)?.remove();
            field.classList.remove('is-invalid'); field.removeAttribute('aria-invalid');
            const descriptions = (field.getAttribute('aria-describedby') || '').split(' ').filter(id => id && id !== errorId);
            if (descriptions.length) field.setAttribute('aria-describedby',descriptions.join(' ')); else field.removeAttribute('aria-describedby');
            delete field.dataset.fieldErrorId;
        };
        let errorIndex = 0;
        const showFieldError = (field, message) => {
            clearError(field);
            const error = document.createElement('div');
            error.id = `field-error-${++errorIndex}`;
            error.className = 'invalid-feedback d-block'; error.textContent = message;
            field.dataset.fieldErrorId = error.id; field.classList.add('is-invalid'); field.setAttribute('aria-invalid','true');
            field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'),error.id].filter(Boolean).join(' '));
            (field.closest('.input-group,.pw-wrapper') || field).after(error);
        };
        const showSummary = (form, message, fields) => {
            form.querySelector('.gs-form-errors')?.remove();
            const summary = document.createElement('div');
            summary.className = 'alert alert-danger gs-form-errors'; summary.setAttribute('role','alert');
            const title = document.createElement('strong'); title.textContent = message || 'Revisa los datos del formulario.'; summary.append(title);
            if (fields.length) {
                const list = document.createElement('ul'); list.className = 'mb-0 mt-2';
                fields.forEach(field => {
                    if (!field.id) field.id = `invalid-field-${++errorIndex}`;
                    const item = document.createElement('li'); const link = document.createElement('a');
                    link.href = '#' + field.id; link.textContent = fieldName(field);
                    link.addEventListener('click', event => { event.preventDefault(); field.focus(); });
                    item.append(link); list.append(item);
                }); summary.append(list);
            }
            form.prepend(summary);
        };
        const initializeForms = scope => scope.querySelectorAll('form').forEach(form => {
            if (form.dataset.validationReady) return;
            form.dataset.validationReady = '1';
            let scheduled = false;
            form.addEventListener('invalid', event => {
                const field = event.target;
                event.preventDefault();
                const validity = field.validity;
                const message = validity.valueMissing ? 'Completa este campo.'
                    : validity.typeMismatch ? 'Introduce un valor con el formato indicado.'
                    : validity.rangeUnderflow ? `El valor mínimo es ${field.min}.`
                    : validity.rangeOverflow ? `El valor máximo es ${field.max}.`
                    : validity.patternMismatch ? 'Revisa el formato indicado para este campo.'
                    : 'Revisa el valor de este campo.';
                showFieldError(field,message);
                if (scheduled) return;
                scheduled = true;
                queueMicrotask(() => {
                    scheduled = false;
                    const invalid = [...form.elements].filter(el => el.willValidate && !el.validity.valid);
                    showSummary(form,'Revisa los campos señalados.',invalid);
                    const panel = form.closest('details[data-form-panel]'); if (panel) panel.open = true;
                    invalid[0]?.focus();
                });
            }, true);
            form.addEventListener('input', event => {
                clearError(event.target);
                if (!form.querySelector('[aria-invalid=true]')) form.querySelector('.gs-form-errors')?.remove();
            });
        });
        const showFeedback = (form, feedback) => {
            const invalid = [];
            form.querySelectorAll('[data-field-error-id]').forEach(clearError);
            Object.entries(feedback.fields || {}).forEach(([name,message]) => {
                const field = form.elements.namedItem(name);
                if (field instanceof HTMLElement) { showFieldError(field,message); invalid.push(field); }
            });
            showSummary(form,feedback.message,invalid);
            const panel = form.closest('details[data-form-panel]'); if (panel) panel.open = true;
            (invalid[0] || form.querySelector('input:not([type=hidden]),select,textarea'))?.focus({preventScroll:true});
            (invalid[0] || form.querySelector('.gs-form-errors'))?.scrollIntoView({block:'nearest'});
        };
        window.GhostForms = {initialize:initializeForms, showFeedback};
        initializeForms(document);
        const feedbackElement = document.getElementById('form-feedback-data');
        if (feedbackElement) {
            const feedback = JSON.parse(feedbackElement.textContent);
            const action = new URL(feedback.action, location.href).pathname;
            const form = [...document.forms].find(f => new URL(f.action, location.href).pathname === action && f.method === 'post');
            if (form) {
                showFeedback(form,feedback);
                const panel = form.closest('details[data-form-panel]'); if (panel) panel.open = true;
                // Un error de formulario permanece junto a los campos, sin un diálogo que lo tape.
            }
        }
    });
})();
