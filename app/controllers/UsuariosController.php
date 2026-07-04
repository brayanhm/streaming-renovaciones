<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class UsuariosController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
        $this->requireAdmin();
    }

    public function index(): void
    {
        $this->render('usuarios/index', [
            'pageTitle' => 'Usuarios del sistema',
            'rows' => $this->users->all(),
            'roles' => User::ROLES,
            'currentUserId' => $this->currentUserId(),
            'activeAdmins' => $this->users->activeAdminCount(),
        ]);
    }

    public function auditoria(): void
    {
        $rows = \db()->query('SELECT * FROM auditoria ORDER BY id DESC LIMIT 300')->fetchAll();

        $this->render('usuarios/auditoria', [
            'pageTitle' => 'Auditoría',
            'rows' => $rows,
        ]);
    }

    public function store(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');
        $rol      = strtolower(trim((string) ($_POST['rol'] ?? 'operador')));

        if (strlen($username) < 3) {
            flash('danger', 'El usuario debe tener al menos 3 caracteres.');
            $this->redirect('/usuarios');
        }
        if (!in_array($rol, User::ROLES, true)) {
            flash('danger', 'El rol seleccionado no es válido.');
            $this->redirect('/usuarios');
        }
        if ($this->users->usernameExists($username, 0)) {
            flash('danger', 'Ese nombre de usuario ya está en uso.');
            $this->redirect('/usuarios');
        }
        if (strlen($password) < 6) {
            flash('danger', 'La contraseña debe tener al menos 6 caracteres.');
            $this->redirect('/usuarios');
        }
        if ($password !== $confirm) {
            flash('danger', 'Las contraseñas no coinciden.');
            $this->redirect('/usuarios');
        }

        try {
            $this->users->create($username, $password, $rol);
            audit('usuario.crear', $username . ' (' . $rol . ')');
            flash('success', 'Usuario "' . $username . '" creado correctamente.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo crear el usuario: ' . $exception->getMessage());
        }

        $this->redirect('/usuarios');
    }

    public function edit(int $id): void
    {
        $user = $this->users->findById($id);
        if ($user === null) {
            flash('danger', 'Usuario no encontrado.');
            $this->redirect('/usuarios');
        }

        $this->render('usuarios/edit', [
            'pageTitle' => 'Editar usuario',
            'user' => $user,
            'roles' => User::ROLES,
            'currentUserId' => $this->currentUserId(),
            'isLastActiveAdmin' => $this->isLastActiveAdmin($user),
        ]);
    }

    public function update(int $id): void
    {
        $user = $this->users->findById($id);
        if ($user === null) {
            flash('danger', 'Usuario no encontrado.');
            $this->redirect('/usuarios');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $rol      = strtolower(trim((string) ($_POST['rol'] ?? '')));

        if (strlen($username) < 3) {
            flash('danger', 'El usuario debe tener al menos 3 caracteres.');
            $this->redirect('/usuarios/editar/' . $id);
        }
        if (!in_array($rol, User::ROLES, true)) {
            flash('danger', 'El rol seleccionado no es válido.');
            $this->redirect('/usuarios/editar/' . $id);
        }
        if ($this->users->usernameExists($username, $id)) {
            flash('danger', 'Ese nombre de usuario ya está en uso.');
            $this->redirect('/usuarios/editar/' . $id);
        }

        // No permitir quitar el rol admin al ultimo administrador activo.
        if ($rol !== 'admin' && $this->isLastActiveAdmin($user)) {
            flash('danger', 'No puedes quitar el rol de administrador al último admin activo.');
            $this->redirect('/usuarios/editar/' . $id);
        }

        $this->users->updateProfileData($id, $username, $rol);
        audit('usuario.editar', 'ID ' . $id . ' -> ' . $username . ' / ' . $rol);

        // Si el admin se edita a si mismo, refrescar los datos de la sesion.
        if ($id === $this->currentUserId()) {
            $_SESSION['auth']['username'] = $username;
            $_SESSION['auth']['rol'] = $rol;
        }

        flash('success', 'Usuario actualizado correctamente.');
        $this->redirect('/usuarios/editar/' . $id);
    }

    public function resetPassword(int $id): void
    {
        $user = $this->users->findById($id);
        if ($user === null) {
            flash('danger', 'Usuario no encontrado.');
            $this->redirect('/usuarios');
        }

        $password = (string) ($_POST['new_password'] ?? '');
        $confirm  = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($password) < 6) {
            flash('danger', 'La nueva contraseña debe tener al menos 6 caracteres.');
            $this->redirect('/usuarios/editar/' . $id);
        }
        if ($password !== $confirm) {
            flash('danger', 'Las contraseñas no coinciden.');
            $this->redirect('/usuarios/editar/' . $id);
        }

        $this->users->updatePassword($id, $password);
        audit('usuario.password', 'Reset contrasena ID ' . $id . ' (' . (string) $user['username'] . ')');
        flash('success', 'Contraseña de "' . (string) $user['username'] . '" restablecida.');
        $this->redirect('/usuarios/editar/' . $id);
    }

    public function toggleActive(int $id): void
    {
        $user = $this->users->findById($id);
        if ($user === null) {
            flash('danger', 'Usuario no encontrado.');
            $this->redirect('/usuarios');
        }

        $activate = (int) ($_POST['activo'] ?? -1) === 1;

        if (!$activate) {
            if ($id === $this->currentUserId()) {
                flash('danger', 'No puedes desactivar tu propia cuenta.');
                $this->redirect('/usuarios');
            }
            if ($this->isLastActiveAdmin($user)) {
                flash('danger', 'No puedes desactivar al último administrador activo.');
                $this->redirect('/usuarios');
            }
        }

        $this->users->setActive($id, $activate);
        audit('usuario.estado', 'ID ' . $id . ' ' . ($activate ? 'activado' : 'desactivado'));
        flash('success', 'Usuario "' . (string) $user['username'] . ($activate ? '" activado.' : '" desactivado.'));
        $this->redirect('/usuarios');
    }

    public function destroy(int $id): void
    {
        $user = $this->users->findById($id);
        if ($user === null) {
            flash('danger', 'Usuario no encontrado.');
            $this->redirect('/usuarios');
        }

        if ($id === $this->currentUserId()) {
            flash('danger', 'No puedes eliminar tu propia cuenta.');
            $this->redirect('/usuarios');
        }
        if ($this->isLastActiveAdmin($user)) {
            flash('danger', 'No puedes eliminar al último administrador activo.');
            $this->redirect('/usuarios');
        }

        try {
            $this->users->delete($id);
            audit('usuario.eliminar', 'ID ' . $id . ' (' . (string) $user['username'] . ')');
            flash('success', 'Usuario "' . (string) $user['username'] . '" eliminado.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo eliminar el usuario: ' . $exception->getMessage());
        }

        $this->redirect('/usuarios');
    }

    private function requireAdmin(): void
    {
        if ((string) ($_SESSION['auth']['rol'] ?? '') !== 'admin') {
            flash('danger', 'No tienes permisos para acceder a la gestión de usuarios.');
            $this->redirect('/dashboard');
        }
    }

    private function currentUserId(): int
    {
        return (int) ($_SESSION['auth']['id'] ?? 0);
    }

    /**
     * True si el usuario dado es admin, esta activo y no queda ningun otro admin activo.
     */
    private function isLastActiveAdmin(array $user): bool
    {
        $isActiveAdmin = (string) ($user['rol'] ?? '') === 'admin' && (int) ($user['activo'] ?? 0) === 1;

        return $isActiveAdmin && $this->users->activeAdminCount((int) $user['id']) === 0;
    }
}
