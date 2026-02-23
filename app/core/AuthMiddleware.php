<?php
declare(strict_types=1);

namespace App\Core;

class AuthMiddleware
{
    public static function handle(string $path): void
    {
        $publicPaths = [
            '/login',
        ];

        if (!in_array($path, $publicPaths, true) && !\is_logged_in()) {
            \flash('warning', 'Debes iniciar sesion para continuar.');
            \redirect('/login');
        }

        if ($path === '/login' && \is_logged_in()) {
            \redirect('/dashboard');
        }
    }
}
