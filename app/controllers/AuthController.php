<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function showLogin(): void
    {
        $hasUsers = $this->users->countAll() > 0;

        $this->render('auth/login', [
            'pageTitle' => 'Iniciar sesión',
            'hasUsers' => $hasUsers,
        ]);
    }

    public function login(): void
    {
        $isSetupRequest = ((string) ($_POST['_action'] ?? '')) === 'setup_admin' || $this->users->countAll() === 0;
        if ($isSetupRequest) {
            $this->handleSetupAdmin();
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));

        if ($username === '' || $password === '') {
            set_old(['username' => $username]);
            flash('danger', 'Ingresa tu usuario y contraseña.');
            $this->redirect('/login');
        }

        $user = $this->users->authenticate($username, $password);
        if ($user === null) {
            set_old(['username' => $username]);
            flash('danger', 'Usuario o contraseña incorrectos.');
            $this->redirect('/login');
        }

        $_SESSION['auth'] = [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'rol' => (string) ($user['rol'] ?? 'operador'),
        ];

        clear_old();
        flash('success', 'Sesión iniciada correctamente.');
        $this->redirect('/dashboard');
    }

    private function handleSetupAdmin(): void
    {
        if ($this->users->countAll() > 0) {
            flash('warning', 'El usuario inicial ya existe. Inicia sesión.');
            $this->redirect('/login');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        $confirm = trim((string) ($_POST['password_confirm'] ?? ''));

        if ($username === '' || strlen($password) < 6 || $password !== $confirm) {
            set_old(['username' => $username]);
            flash('danger', 'Datos no válidos. La contraseña debe tener al menos 6 caracteres y coincidir con la confirmación.');
            $this->redirect('/login');
        }

        try {
            $this->users->create($username, $password, 'admin');
            clear_old();
            flash('success', 'Usuario administrador inicial creado. Ahora inicia sesión.');
        } catch (\Throwable $exception) {
            set_old(['username' => $username]);
            flash('danger', 'No se pudo crear el usuario inicial: ' . $exception->getMessage());
        }

        $this->redirect('/login');
    }

    public function logout(): void
    {
        unset($_SESSION['auth']);
        session_regenerate_id(true);
        flash('success', 'Sesión cerrada.');
        $this->redirect('/login');
    }
}

