<?php
declare(strict_types=1);

/**
 * Iconos Lucide locales. El texto del control proporciona su nombre accesible.
 * Los SVG decorativos heredan el color del menú o botón que los contiene.
 */
function icon(string $name): string
{
    static $names = [
        'layout-dashboard', 'message-circle', 'download', 'bot', 'brain',
        'users', 'calendar-clock', 'chart-column', 'layers', 'tag', 'user-cog',
        'scroll-text', 'clipboard-list', 'menu', 'x', 'chevron-down',
        'arrow-left', 'arrow-right', 'log-in', 'log-out', 'square-pen',
        'trash', 'refresh-cw', 'save', 'search', 'plus', 'copy', 'eye',
        'eye-off', 'funnel-x', 'circle-x', 'circle-check', 'key-round',
    ];
    static $spriteUrl = null;

    if (!in_array($name, $names, true)) {
        return '';
    }

    if ($spriteUrl === null) {
        $version = (string) filemtime(PUBLIC_PATH . '/icons/lucide.svg');
        $spriteUrl = url('/icons/lucide.svg') . '?v=' . $version;
    }

    return '<svg class="gs-icon" data-icon="' . e($name) . '"'
        . ' xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"'
        . ' fill="none" stroke="currentColor" stroke-width="1.8"'
        . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . '<use href="' . e($spriteUrl . '#' . $name) . '"></use></svg>';
}
