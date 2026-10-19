<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function render(string $view, array $data = []): void
    {
        $pageTitle = $data['pageTitle'] ?? APP_NAME;
        $flashMessages = consume_flash();
        $currentPath = request_path();
        $authUser = auth_user();

        extract($data, EXTR_SKIP);

        require APP_PATH . '/views/layouts/header.php';
        $modalPage = ($data['modalPage'] ?? false) || str_ends_with($view, '/edit')
            || in_array($view, ['modalidades/precios', 'suscripciones/historial', 'auth/profile'], true);
        if ($modalPage) echo '<div data-modal-page data-modal-title="' . e($pageTitle) . '" data-modal-size="'
            . e($data['modalSize'] ?? ($view === 'suscripciones/historial' ? 'wide' : 'normal')) . '">';
        require APP_PATH . '/views/' . $view . '.php';
        if ($modalPage) echo '</div>';
        require APP_PATH . '/views/layouts/footer.php';
        \clear_old();
    }

    protected function redirect(string $path): void
    {
        \redirect($path);
    }

    protected function goBack(string $fallback = '/dashboard'): void
    {
        if (\is_modal_form_request()) $this->redirect($fallback);
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if (is_string($referer) && $referer !== '') {
            $refererParts = parse_url($referer);
            $currentHost = $_SERVER['HTTP_HOST'] ?? '';
            $refererHost = is_array($refererParts) ? (string) ($refererParts['host'] ?? '') : '';
            $refererPath = is_array($refererParts) ? (string) ($refererParts['path'] ?? '') : '';
            $basePath = rtrim(base_url_path(), '/');
            $sameHost = $refererHost === '' || strcasecmp($refererHost, (string) $currentHost) === 0;
            $sameBasePath = $basePath === '' || $basePath === '/' || str_starts_with($refererPath, $basePath . '/') || $refererPath === $basePath;

            if ($sameHost && $sameBasePath) {
                header('Location: ' . $referer);
                exit;
            }
        }

        $this->redirect($fallback);
    }
}
