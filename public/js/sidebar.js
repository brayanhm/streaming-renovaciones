(() => {
    'use strict';
    const root = document.documentElement;
    const preferenceKey = 'ghost.navPinned';
    let pinned = false;
    try { pinned = localStorage.getItem(preferenceKey) === 'true'; } catch (_) {}
    root.dataset.navPinned = String(pinned);
    root.dataset.navMode = pinned ? 'expanded' : 'compact';

    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('appSidebar');
        const pin = sidebar?.querySelector('[data-nav-pin]');
        if (!sidebar || !pin) return;
        const desktop = window.matchMedia('(min-width: 992px)');
        let pointerInside = false;
        let keyboardFocus = false;
        let keyboardInput = true;
        let closeTimer = null;

        const cancelClose = () => { clearTimeout(closeTimer); closeTimer = null; };
        const update = () => {
            const expanded = pinned || (desktop.matches && (pointerInside || keyboardFocus));
            root.dataset.navMode = expanded ? 'expanded' : 'compact';
            root.dataset.navPinned = String(pinned);
            pin.setAttribute('aria-pressed',String(pinned));
            pin.setAttribute('aria-expanded',String(expanded));
            pin.setAttribute('aria-label',pinned ? 'Desfijar menú y contraer automáticamente' : 'Fijar menú abierto');
            pin.title = pinned ? 'Desfijar menú y usar expansión automática' : 'Fijar menú abierto';
            pin.querySelector('.lbl').textContent = pinned ? 'Desfijar menú' : 'Fijar menú';
        };
        const scheduleClose = () => {
            cancelClose();
            if (pinned || pointerInside || keyboardFocus) return;
            closeTimer = setTimeout(update,240);
        };
        sidebar.addEventListener('pointerenter', event => {
            if (!desktop.matches || event.pointerType === 'touch') return;
            pointerInside = true;
            cancelClose();
            update();
        });
        sidebar.addEventListener('pointerleave', () => {
            pointerInside = false;
            scheduleClose();
        });
        document.addEventListener('pointerdown', event => {
            keyboardInput = false;
            keyboardFocus = false;
            if (!sidebar.contains(event.target)) scheduleClose();
        },true);
        document.addEventListener('keydown', event => {
            if (event.key === 'Tab') {
                keyboardInput = true;
                if (desktop.matches && sidebar.contains(document.activeElement)) {
                    keyboardFocus = true;
                    cancelClose();
                    update();
                }
            }
        },true);
        sidebar.addEventListener('focusin', () => {
            if (!desktop.matches || !keyboardInput) return;
            keyboardFocus = true;
            cancelClose();
            update();
        });
        sidebar.addEventListener('focusout', event => {
            if (sidebar.contains(event.relatedTarget)) return;
            keyboardFocus = false;
            scheduleClose();
        });
        sidebar.addEventListener('keydown', event => {
            if (event.key !== 'Escape' || !desktop.matches || pinned) return;
            event.preventDefault();
            event.stopPropagation();
            cancelClose();
            pointerInside = false;
            keyboardFocus = false;
            document.getElementById('main-content')?.focus({preventScroll:true});
            update();
        });
        pin.addEventListener('click', () => {
            pinned = !pinned;
            cancelClose();
            try { localStorage.setItem(preferenceKey,String(pinned)); } catch (_) {}
            update();
        });
        sidebar.addEventListener('click', event => {
            if (!desktop.matches && event.target.closest('.app-nav-link')) {
                window.bootstrap?.Offcanvas.getInstance(sidebar)?.hide();
            }
        });
        desktop.addEventListener('change', () => {
            cancelClose();
            pointerInside = false;
            keyboardFocus = desktop.matches && keyboardInput && sidebar.contains(document.activeElement);
            update();
        });
        window.addEventListener('pagehide', cancelClose);
        update();
    });
})();
