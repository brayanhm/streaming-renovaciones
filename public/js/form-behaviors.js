(() => {
    'use strict';
    const initialize = scope => {
        scope.querySelectorAll('[data-form-behavior="renewal"]').forEach(form => {
            if (form.dataset.behaviorReady) return;
            form.dataset.behaviorReady = '1';
            const select = form.elements.meses;
            const update = () => {
                const option = select.selectedOptions[0];
                form.querySelector('[data-renewal-price]').textContent = option?.dataset.price || '—';
                form.querySelector('[data-renewal-due]').textContent = option?.dataset.due || '—';
            };
            select.addEventListener('change',update);
            update();
        });
        scope.querySelectorAll('[data-form-behavior="due-date"]').forEach(form => {
            if (form.dataset.behaviorReady) return;
            form.dataset.behaviorReady = '1';
            const input = form.elements.fecha_vencimiento;
            const update = () => {
                const parts = input.value.split('-');
                const today = new Date(); today.setHours(0,0,0,0);
                const due = new Date(input.value + 'T00:00:00');
                form.querySelector('[data-due-date-status]').textContent = parts.length !== 3 ? 'Selecciona la nueva fecha.'
                    : `Nuevo vencimiento: ${parts.reverse().join('/')}. ` + (due < today ? 'La fecha seguirá en el pasado.' : 'Se actualizará el estado según esta fecha.');
            };
            input.addEventListener('input',update);
            update();
        });
        scope.querySelectorAll('[data-form-behavior="platform"]').forEach(form => {
            if (form.dataset.behaviorReady) return;
            form.dataset.behaviorReady = '1';

            const tipoServicio = form.querySelector('#tipo_servicio');
            const datoWrap = form.querySelector('.js-dato-renovacion-wrap');
            const datoSelect = form.querySelector('.js-dato-renovacion');
            const datoHelp = form.querySelector('.js-dato-renovacion-help');

            const apply = () => {
                const tipo = tipoServicio.value;
                if (tipo === 'RENOVABLE') {
                    datoWrap.classList.remove('d-none');
                    if (datoSelect.value === 'NO_APLICA') {
                        datoSelect.value = 'USUARIO';
                    }
                    datoHelp.textContent = 'Define si la renovación se realiza usando usuario o correo.';
                    return;
                }

                datoSelect.value = 'NO_APLICA';
                datoWrap.classList.add('d-none');
                datoHelp.textContent = 'No aplica para servicios desechables.';
            };

            tipoServicio.addEventListener('change', apply);
            apply();
        });
        scope.querySelectorAll('[data-form-behavior="plan"]').forEach(form => {
            if (form.dataset.behaviorReady) return;
            form.dataset.behaviorReady = '1';

            const tipoCuenta = form.querySelector('.js-tipo-cuenta');
            const plataforma = form.querySelector('.js-plataforma');
            const duracion = form.querySelector('.js-duracion');
            const duracionesList = form.querySelector('#duraciones-list');
            const duracionHelp = form.querySelector('.js-duracion-help');
            const dispositivosWrap = form.querySelector('.js-dispositivos-wrap');
            const dispositivos = form.querySelector('#dispositivos');
            const costoInput = form.querySelector('.js-costo');
            const precioInput = form.querySelector('.js-precio');
            const gananciaEl = form.querySelector('.js-ganancia');

            const parseDuraciones = (csv) => {
                if (!csv) return [];
                const values = [];
                for (const raw of csv.split(',')) {
                    const parsed = Number.parseInt(raw.trim(), 10);
                    if (Number.isNaN(parsed) || parsed <= 0 || values.includes(parsed)) {
                        continue;
                    }
                    values.push(parsed);
                }
                values.sort((a, b) => a - b);
                return values;
            };

            const applyDuraciones = () => {
                const selected = plataforma.options[plataforma.selectedIndex];
                const values = parseDuraciones(selected ? selected.dataset.duraciones : '');

                duracionesList.innerHTML = '';
                for (const value of values) {
                    const option = document.createElement('option');
                    option.value = String(value);
                    duracionesList.appendChild(option);
                }

                if (values.length > 0) {
                    duracionHelp.textContent = 'Duraciones configuradas: ' + values.join(', ') + ' meses.';
                } else {
                    duracionHelp.textContent = 'Sin duraciones fijas para esta plataforma.';
                }
            };

            const apply = () => {
                const value = tipoCuenta.value;
                const needs = value === 'POR_DISPOSITIVOS' || value === 'AMBOS';
                dispositivosWrap.classList.toggle('d-none', !needs);
                dispositivos.required = value === 'POR_DISPOSITIVOS';
                if (!needs) {
                    dispositivos.value = '';
                }
            };

            const applyGanancia = () => {
                const costo = Number.parseFloat(costoInput.value || '0');
                const precio = Number.parseFloat(precioInput.value || '0');
                const ganancia = (Number.isNaN(precio) ? 0 : precio) - (Number.isNaN(costo) ? 0 : costo);
                const sign = ganancia < 0 ? '-' : '';
                const abs = Math.abs(ganancia).toLocaleString('es-BO', {
                    minimumFractionDigits: Number.isInteger(Math.abs(ganancia)) ? 0 : 2,
                    maximumFractionDigits: 2,
                });
                gananciaEl.textContent = 'Bs ' + sign + abs;
                gananciaEl.classList.toggle('text-danger', ganancia < 0);
                gananciaEl.classList.toggle('text-success', ganancia >= 0);
            };

            plataforma.addEventListener('change', applyDuraciones);
            tipoCuenta.addEventListener('change', apply);
            costoInput.addEventListener('input', applyGanancia);
            precioInput.addEventListener('input', applyGanancia);
            applyDuraciones();
            apply();
            applyGanancia();
        });
        scope.querySelectorAll('[data-form-behavior="subscription"]').forEach(form => {
            if (form.dataset.behaviorReady) return;
            form.dataset.behaviorReady = '1';

            const plataformaSelect = form.querySelector('.js-plataforma');
            const modalidadSelect = form.querySelector('.js-modalidad');
            const usuarioWrap = form.querySelector('.js-usuario-wrap');
            const usuarioLabel = form.querySelector('.js-usuario-label');
            const usuarioInput = form.querySelector('.js-usuario-input');
            const usuarioHelp = form.querySelector('.js-usuario-help');
            const costoBaseInput = form.querySelector('.js-costo-base');
            const precioVentaInput = form.querySelector('.js-precio-venta');
            const gananciaEl = form.querySelector('.js-ganancia');
            const originalModalidadId = modalidadSelect.value;
            const originalCosto = costoBaseInput.value;
            const originalPrecio = precioVentaInput.value;

            const applyFilters = () => {
                const plataformaId = plataformaSelect.value;
                const selectedPlatformOption = plataformaSelect.options[plataformaSelect.selectedIndex];
                const tipoServicio = selectedPlatformOption ? selectedPlatformOption.dataset.tipo : '';
                const datoRenovacion = selectedPlatformOption ? selectedPlatformOption.dataset.datoRenovacion : 'NO_APLICA';

                for (const option of modalidadSelect.options) {
                    if (!option.value) {
                        option.hidden = false;
                        continue;
                    }
                    option.hidden = option.dataset.plataformaId !== plataformaId;
                }

                if (modalidadSelect.selectedOptions.length > 0) {
                    const selected = modalidadSelect.selectedOptions[0];
                    if (selected && selected.hidden) {
                        modalidadSelect.value = '';
                    }
                }

                if (tipoServicio === 'DESECHABLE') {
                    usuarioWrap.classList.add('d-none');
                    usuarioInput.required = false;
                    usuarioInput.type = 'text';
                    usuarioInput.value = '';
                } else {
                    usuarioWrap.classList.remove('d-none');
                    usuarioInput.required = true;
                    if (datoRenovacion === 'CORREO') {
                        usuarioLabel.textContent = 'Correo de la cuenta para renovar';
                        usuarioInput.type = 'email';
                        usuarioInput.placeholder = 'correo@dominio.com';
                        usuarioHelp.textContent = 'Ingresa el correo exacto de la cuenta que se usará para renovar.';
                    } else {
                        usuarioLabel.textContent = 'Usuario de la cuenta para renovar';
                        usuarioInput.type = 'text';
                        usuarioInput.placeholder = 'Ej: usuario123';
                        usuarioHelp.textContent = 'Ingresa el usuario exacto de la cuenta que se usará para renovar.';
                    }
                }

                applyGanancia();
            };

            const syncPricingFromSelected = () => {
                const selectedModalidad = modalidadSelect.options[modalidadSelect.selectedIndex];
                if (
                    selectedModalidad &&
                    selectedModalidad.value === originalModalidadId &&
                    originalCosto !== '' &&
                    originalPrecio !== ''
                ) {
                    costoBaseInput.value = originalCosto;
                    precioVentaInput.value = originalPrecio;
                    applyGanancia();
                    return;
                }
                if (selectedModalidad && selectedModalidad.dataset.costo) {
                    costoBaseInput.value = selectedModalidad.dataset.costo;
                }
                if (selectedModalidad && selectedModalidad.dataset.precio) {
                    precioVentaInput.value = selectedModalidad.dataset.precio;
                }
                applyGanancia();
            };

            const applyGanancia = () => {
                const costo = Number.parseFloat(costoBaseInput.value || '0');
                const precio = Number.parseFloat(precioVentaInput.value || '0');
                const ganancia = (Number.isNaN(precio) ? 0 : precio) - (Number.isNaN(costo) ? 0 : costo);
                const sign = ganancia < 0 ? '-' : '';
                const abs = Math.abs(ganancia).toLocaleString('es-BO', {
                    minimumFractionDigits: Number.isInteger(Math.abs(ganancia)) ? 0 : 2,
                    maximumFractionDigits: 2,
                });
                gananciaEl.textContent = 'Bs ' + sign + abs;
                gananciaEl.classList.toggle('text-danger', ganancia < 0);
                gananciaEl.classList.toggle('text-success', ganancia >= 0);
            };

            plataformaSelect.addEventListener('change', applyFilters);
            modalidadSelect.addEventListener('change', () => {
                applyFilters();
                syncPricingFromSelected();
            });
            costoBaseInput.addEventListener('input', applyGanancia);
            precioVentaInput.addEventListener('input', applyGanancia);
            applyFilters();
            if (costoBaseInput.value === '' || precioVentaInput.value === '') {
                syncPricingFromSelected();
            }
            applyGanancia();
        });
        scope.querySelectorAll('[data-form-behavior="create-platform"]').forEach(form => {
            if (form.dataset.behaviorReady) return;
            form.dataset.behaviorReady = '1';

            const tipoServicio = form.querySelector('#tipo_servicio');
            const datoWrap = form.querySelector('.js-dato-renovacion-wrap');
            const datoSelect = form.querySelector('.js-dato-renovacion');
            const datoHelp = form.querySelector('.js-dato-renovacion-help');

            const apply = () => {
                const tipo = tipoServicio.value;
                if (tipo === 'RENOVABLE') {
                    datoWrap.classList.remove('d-none');
                    if (datoSelect.value === 'NO_APLICA') {
                        datoSelect.value = 'USUARIO';
                    }
                    datoHelp.textContent = 'Define si la renovación se realiza usando usuario o correo.';
                    return;
                }

                datoSelect.value = 'NO_APLICA';
                datoWrap.classList.add('d-none');
                datoHelp.textContent = 'No aplica para servicios desechables.';
            };

            tipoServicio.addEventListener('change', apply);
            apply();
        });
        scope.querySelectorAll('[data-form-behavior="create-plan"]').forEach(form => {
            if (form.dataset.behaviorReady) return;
            form.dataset.behaviorReady = '1';

            const tipoCuenta = form.querySelector('.js-tipo-cuenta');
            const plataforma = form.querySelector('.js-plataforma');
            const duracion = form.querySelector('.js-duracion');
            const duracionesList = form.querySelector('#duraciones-list');
            const duracionHelp = form.querySelector('.js-duracion-help');
            const dispositivosWrap = form.querySelector('.js-dispositivos-wrap');
            const dispositivos = form.querySelector('#dispositivos');
            const costoInput = form.querySelector('.js-costo');
            const precioInput = form.querySelector('.js-precio');
            const gananciaEl = form.querySelector('.js-ganancia');

            const parseDuraciones = (csv) => {
                if (!csv) return [];
                const values = [];
                for (const raw of csv.split(',')) {
                    const parsed = Number.parseInt(raw.trim(), 10);
                    if (Number.isNaN(parsed) || parsed <= 0 || values.includes(parsed)) {
                        continue;
                    }
                    values.push(parsed);
                }
                values.sort((a, b) => a - b);
                return values;
            };

            const applyDuraciones = () => {
                const selected = plataforma.options[plataforma.selectedIndex];
                const values = parseDuraciones(selected ? selected.dataset.duraciones : '');

                duracionesList.innerHTML = '';
                for (const value of values) {
                    const option = document.createElement('option');
                    option.value = String(value);
                    duracionesList.appendChild(option);
                }

                if (values.length > 0) {
                    duracionHelp.textContent = 'Duraciones configuradas para esta plataforma: ' + values.join(', ') + ' meses.';
                } else {
                    duracionHelp.textContent = 'Esta plataforma no tiene duraciones fijas. Puedes indicar cualquier mes positivo.';
                }
            };

            const apply = () => {
                const value = tipoCuenta.value;
                const needs = value === 'POR_DISPOSITIVOS' || value === 'AMBOS';
                dispositivosWrap.classList.toggle('d-none', !needs);
                dispositivos.required = value === 'POR_DISPOSITIVOS';
                if (!needs) {
                    dispositivos.value = '';
                }
            };

            const applyGanancia = () => {
                const costo = Number.parseFloat(costoInput.value || '0');
                const precio = Number.parseFloat(precioInput.value || '0');
                const ganancia = (Number.isNaN(precio) ? 0 : precio) - (Number.isNaN(costo) ? 0 : costo);
                const sign = ganancia < 0 ? '-' : '';
                const abs = Math.abs(ganancia).toLocaleString('es-BO', {
                    minimumFractionDigits: Number.isInteger(Math.abs(ganancia)) ? 0 : 2,
                    maximumFractionDigits: 2,
                });
                gananciaEl.textContent = 'Bs ' + sign + abs;
                gananciaEl.classList.toggle('text-danger', ganancia < 0);
                gananciaEl.classList.toggle('text-success', ganancia >= 0);
            };

            plataforma.addEventListener('change', applyDuraciones);
            tipoCuenta.addEventListener('change', apply);
            costoInput.addEventListener('input', applyGanancia);
            precioInput.addEventListener('input', applyGanancia);
            applyDuraciones();
            apply();
            applyGanancia();
        });
        scope.querySelectorAll('[data-form-behavior="create-subscription"]').forEach(form => {
            if (form.dataset.behaviorReady) return;
            form.dataset.behaviorReady = '1';

            const plataformaSelect = form.querySelector('.js-plataforma');
            const modalidadSelect = form.querySelector('.js-modalidad');
            const usuarioWrap = form.querySelector('.js-usuario-wrap');
            const usuarioLabel = form.querySelector('.js-usuario-label');
            const usuarioInput = form.querySelector('.js-usuario-input');
            const usuarioHelp = form.querySelector('.js-usuario-help');
            const costoBaseInput = form.querySelector('.js-costo-base');
            const precioVentaInput = form.querySelector('.js-precio-venta');
            const gananciaEl = form.querySelector('.js-ganancia');

            const applyFilters = () => {
                const plataformaId = plataformaSelect.value;
                const selectedPlatformOption = plataformaSelect.options[plataformaSelect.selectedIndex];
                const tipoServicio = selectedPlatformOption ? selectedPlatformOption.dataset.tipo : '';
                const datoRenovacion = selectedPlatformOption ? selectedPlatformOption.dataset.datoRenovacion : 'NO_APLICA';
                let hasVisible = false;

                for (const option of modalidadSelect.options) {
                    if (!option.value) {
                        option.hidden = false;
                        continue;
                    }
                    const belongs = option.dataset.plataformaId === plataformaId;
                    option.hidden = !belongs;
                    if (belongs) hasVisible = true;
                }

                if (modalidadSelect.selectedOptions.length > 0) {
                    const selected = modalidadSelect.selectedOptions[0];
                    if (selected && selected.value && selected.hidden) {
                        modalidadSelect.value = '';
                    }
                }

                if (!hasVisible && plataformaId !== '') {
                    modalidadSelect.value = '';
                }

                if (tipoServicio === 'DESECHABLE') {
                    usuarioWrap.classList.add('d-none');
                    usuarioInput.required = false;
                    usuarioInput.type = 'text';
                    usuarioInput.value = '';
                } else {
                    usuarioWrap.classList.remove('d-none');
                    usuarioInput.required = true;
                    if (datoRenovacion === 'CORREO') {
                        usuarioLabel.textContent = 'Correo de la cuenta para renovar';
                        usuarioInput.type = 'email';
                        usuarioInput.placeholder = 'correo@dominio.com';
                        usuarioHelp.textContent = 'Ingresa el correo exacto de la cuenta que se usará para renovar.';
                    } else {
                        usuarioLabel.textContent = 'Usuario de la cuenta para renovar';
                        usuarioInput.type = 'text';
                        usuarioInput.placeholder = 'Ej: usuario123';
                        usuarioHelp.textContent = 'Ingresa el usuario exacto de la cuenta que se usará para renovar.';
                    }
                }

                const selectedModalidad = modalidadSelect.options[modalidadSelect.selectedIndex];
                if (selectedModalidad && selectedModalidad.dataset.costo) {
                    costoBaseInput.value = selectedModalidad.dataset.costo;
                }
                if (selectedModalidad && selectedModalidad.dataset.precio) {
                    precioVentaInput.value = selectedModalidad.dataset.precio;
                }
                if (!selectedModalidad || !selectedModalidad.value) {
                    costoBaseInput.value = '';
                    precioVentaInput.value = '';
                }
                applyGanancia();
            };

            const applyGanancia = () => {
                const costo = Number.parseFloat(costoBaseInput.value || '0');
                const precio = Number.parseFloat(precioVentaInput.value || '0');
                const ganancia = (Number.isNaN(precio) ? 0 : precio) - (Number.isNaN(costo) ? 0 : costo);
                const sign = ganancia < 0 ? '-' : '';
                const abs = Math.abs(ganancia).toLocaleString('es-BO', {
                    minimumFractionDigits: Number.isInteger(Math.abs(ganancia)) ? 0 : 2,
                    maximumFractionDigits: 2,
                });
                gananciaEl.textContent = 'Bs ' + sign + abs;
                gananciaEl.classList.toggle('text-danger', ganancia < 0);
                gananciaEl.classList.toggle('text-success', ganancia >= 0);
            };

            plataformaSelect.addEventListener('change', applyFilters);
            modalidadSelect.addEventListener('change', applyFilters);
            costoBaseInput.addEventListener('input', applyGanancia);
            precioVentaInput.addEventListener('input', applyGanancia);
            applyFilters();
            applyGanancia();
        });
        scope.querySelectorAll('[data-form-behavior="create-client"]').forEach(form => {
            if (form.dataset.behaviorReady) return;
            form.dataset.behaviorReady = '1';

            const plataformaSelect = form.querySelector('.js-plataforma');
            const modalidadSelect = form.querySelector('.js-modalidad');
            const usuarioWrap = form.querySelector('.js-usuario-wrap');
            const usuarioLabel = form.querySelector('.js-usuario-label');
            const usuarioInput = form.querySelector('.js-usuario-input');
            const usuarioHelp = form.querySelector('.js-usuario-help');

            const applyFilters = () => {
                const plataformaId = plataformaSelect.value;
                const selectedPlatformOption = plataformaSelect.options[plataformaSelect.selectedIndex];
                const tipoServicio = selectedPlatformOption ? selectedPlatformOption.dataset.tipo : '';
                const datoRenovacion = selectedPlatformOption ? selectedPlatformOption.dataset.datoRenovacion : 'NO_APLICA';
                let hasVisible = false;

                for (const option of modalidadSelect.options) {
                    if (option.value === '') continue;
                    const visible = plataformaId !== '' && option.dataset.plataformaId === plataformaId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                    hasVisible = hasVisible || visible;
                }

                if (modalidadSelect.selectedOptions.length > 0) {
                    const selected = modalidadSelect.selectedOptions[0];
                    if (selected.value !== '' && selected.dataset.plataformaId !== plataformaId) {
                        modalidadSelect.value = '';
                    }
                }

                if (!hasVisible) {
                    modalidadSelect.value = '';
                }

                if (tipoServicio === 'DESECHABLE' || plataformaId === '') {
                    usuarioWrap.classList.add('d-none');
                    usuarioInput.required = false;
                    usuarioInput.type = 'text';
                    usuarioInput.value = '';
                    return;
                }

                usuarioWrap.classList.remove('d-none');
                usuarioInput.required = true;
                if (datoRenovacion === 'CORREO') {
                    usuarioLabel.textContent = 'Correo de la cuenta para renovar';
                    usuarioInput.type = 'email';
                    usuarioInput.placeholder = 'correo@dominio.com';
                    usuarioHelp.textContent = 'Ingresa el correo de la cuenta para la primera renovación.';
                } else {
                    usuarioLabel.textContent = 'Usuario de la cuenta para renovar';
                    usuarioInput.type = 'text';
                    usuarioInput.placeholder = 'Ej: usuario123';
                    usuarioHelp.textContent = 'Ingresa el usuario de la cuenta para la primera renovación.';
                }
            };

            plataformaSelect.addEventListener('change', applyFilters);
            applyFilters();
        });
        scope.querySelectorAll('[data-form-behavior="bulk-pricing"] ').forEach(form => {
            if (form.dataset.behaviorReady) return;
            form.dataset.behaviorReady = '1';

            const money = (value) => {
                const sign = value < 0 ? '-' : '';
                const abs = Math.abs(value).toLocaleString('es-BO', {
                    minimumFractionDigits: Number.isInteger(Math.abs(value)) ? 0 : 2,
                    maximumFractionDigits: 2,
                });
                return 'Bs ' + sign + abs;
            };

            const recalc = () => {
                const rows = form.querySelectorAll('tbody tr');
                rows.forEach((row) => {
                    const costInput = row.querySelector('.js-costo');
                    const priceInput = row.querySelector('.js-precio');
                    const gainEl = row.querySelector('.js-ganancia');
                    if (!costInput || !priceInput || !gainEl) return;

                    const cost = Number.parseFloat(costInput.value || '0');
                    const price = Number.parseFloat(priceInput.value || '0');
                    const gain = (Number.isNaN(price) ? 0 : price) - (Number.isNaN(cost) ? 0 : cost);
                    gainEl.textContent = money(gain);
                    gainEl.classList.toggle('text-danger', gain < 0);
                    gainEl.classList.toggle('text-success', gain >= 0);
                });
            };

            form.addEventListener('input', (event) => {
                if (event.target instanceof HTMLInputElement) {
                    if (event.target.classList.contains('js-costo') || event.target.classList.contains('js-precio')) {
                        recalc();
                    }
                }
            });
        });
    };
    window.GhostFormBehaviors = {initialize};
    document.addEventListener('DOMContentLoaded', () => initialize(document));
})();
