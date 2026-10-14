'use strict';

// Conserva los SVG cuando un control cambia su etiqueta o su estado.
window.GhostIcons = {
    set(element, name) {
        const svg = element.querySelector('.gs-icon');
        const use = svg && svg.querySelector('use');
        if (!use || !/^[a-z][a-z0-9-]*$/.test(name)) return;
        const source = (use.getAttribute('href') || '').split('#')[0];
        use.setAttribute('href', source + '#' + name);
        svg.dataset.icon = name;
    },
    setLabel(element, text) {
        const label = element.querySelector('.button-label');
        if (label) {
            label.textContent = text;
        } else {
            element.textContent = text;
        }
    },
};
