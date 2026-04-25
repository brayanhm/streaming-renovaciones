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

        session_regenerate_id(true);
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

    public function showProfile(): void
    {
        $user = $this->users->findById((int) ($_SESSION['auth']['id'] ?? 0));
        if ($user === null) {
            $this->redirect('/login');
        }

        $this->render('auth/profile', [
            'pageTitle' => 'Mi perfil',
            'user' => $user,
        ]);
    }

    public function updateProfile(): void
    {
        $userId = (int) ($_SESSION['auth']['id'] ?? 0);
        $action = (string) ($_POST['_action'] ?? '');

        if ($action === 'change_username') {
            $newUsername = trim((string) ($_POST['username'] ?? ''));

            if ($newUsername === '' || strlen($newUsername) < 3) {
                flash('danger', 'El usuario debe tener al menos 3 caracteres.');
                $this->redirect('/perfil');
            }

            if ($this->users->usernameExists($newUsername, $userId)) {
                flash('danger', 'Ese nombre de usuario ya está en uso.');
                $this->redirect('/perfil');
            }

            $this->users->updateUsername($userId, $newUsername);
            $_SESSION['auth']['username'] = $newUsername;
            flash('success', 'Usuario actualizado correctamente.');
            $this->redirect('/perfil');
        }

        if ($action === 'change_password') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword     = (string) ($_POST['new_password'] ?? '');
            $confirm         = (string) ($_POST['confirm_password'] ?? '');

            $user = $this->users->findById($userId);
            if ($user === null || !password_verify($currentPassword, (string) ($user['password_hash'] ?? ''))) {
                flash('danger', 'La contraseña actual es incorrecta.');
                $this->redirect('/perfil');
            }

            if (strlen($newPassword) < 6) {
                flash('danger', 'La nueva contraseña debe tener al menos 6 caracteres.');
                $this->redirect('/perfil');
            }

            if ($newPassword !== $confirm) {
                flash('danger', 'Las contraseñas no coinciden.');
                $this->redirect('/perfil');
            }

            $this->users->updatePassword($userId, $newPassword);
            flash('success', 'Contraseña actualizada correctamente.');
            $this->redirect('/perfil');
        }

        $this->redirect('/perfil');
    }

    public function logout(): void
    {
        unset($_SESSION['auth']);
        session_regenerate_id(true);
        flash('success', 'Sesión cerrada.');
        $this->redirect('/login');
    }
}

