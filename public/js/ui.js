(() => {
    'use strict';
    const root = document.documentElement;
    root.dataset.js = 'true';
    let navMode = 'expanded';
    try { if (localStorage.getItem('ghost.navMode') === 'compact') navMode = 'compact'; } catch (_) {}
    root.dataset.navMode = navMode;

    document.addEventListener('DOMContentLoaded', () => {
        const pin = document.querySelector('[data-nav-pin]');
        const updatePin = () => {
            if (!pin) return;
            const expanded = root.dataset.navMode === 'expanded';
            pin.setAttribute('aria-pressed', String(expanded));
            pin.setAttribute('aria-label', expanded ? 'Contraer menú' : 'Expandir menú');
            pin.title = expanded ? 'Contraer menú' : 'Expandir menú';
            pin.querySelector('.lbl').textContent = expanded ? 'Contraer menú' : 'Expandir menú';
        };
        pin?.addEventListener('click', () => {
            root.dataset.navMode = root.dataset.navMode === 'expanded' ? 'compact' : 'expanded';
            try { localStorage.setItem('ghost.navMode', root.dataset.navMode); } catch (_) {}
            updatePin();
        });
        updatePin();

        const openPanel = (panel, focus = true) => {
            if (!panel) return;
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
            history.replaceState(null, '', '#' + panel.id);
        }));
        if (location.hash) openPanel(document.getElementById(location.hash.slice(1)), false);
        const dashboardFilters = document.querySelector('[data-dashboard-filters]');
        if (dashboardFilters && window.matchMedia('(min-width:992px)').matches) dashboardFilters.open = true;
        if (window.matchMedia('(min-width:992px)').matches) document.querySelectorAll('[data-account-overview]').forEach(panel => { panel.open = true; });

        // Se conserva una sola tabla y un solo formulario por registro, también en móvil.
        document.querySelectorAll('table[data-responsive-cards]').forEach((table, tableIndex) => {
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
        document.querySelectorAll('form').forEach(form => {
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
        const feedbackElement = document.getElementById('form-feedback-data');
        if (feedbackElement) {
            const feedback = JSON.parse(feedbackElement.textContent);
            const action = new URL(feedback.action, location.href).pathname;
            const form = [...document.forms].find(f => new URL(f.action, location.href).pathname === action && f.method === 'post');
            if (form) {
                const invalid = [];
                Object.entries(feedback.fields || {}).forEach(([name,message]) => {
                    const field = form.elements.namedItem(name);
                    if (field instanceof HTMLElement) { showFieldError(field,message); invalid.push(field); }
                });
                showSummary(form,feedback.message,invalid);
                const panel = form.closest('details[data-form-panel]'); if (panel) panel.open = true;
                const focus = () => {
                    form.scrollIntoView({block:'start'});
                    (invalid[0] || form.querySelector('input:not([type=hidden]),select,textarea'))?.focus({preventScroll:true});
                    if (invalid[0]) invalid[0].scrollIntoView({block:'center'});
                };
                // Un error de formulario permanece junto a los campos, sin un diálogo que lo tape.
                focus();
            }
        }
    });
})();
