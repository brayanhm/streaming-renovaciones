<?php
declare(strict_types=1);

namespace App\Models;

class User extends BaseModel
{
    public const ROLES = ['admin', 'operador'];

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT id, username, rol, activo, created_at FROM usuarios ORDER BY id ASC'
        );

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) AS total FROM usuarios');
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function create(string $username, string $password, string $rol = 'admin'): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $rol = in_array($rol, self::ROLES, true) ? $rol : 'operador';

        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (username, password_hash, rol, activo) VALUES (:username, :password_hash, :rol, 1)'
        );
        $stmt->execute([
            'username' => $username,
            'password_hash' => $hash,
            'rol' => $rol,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateProfileData(int $id, string $username, string $rol): void
    {
        $rol = in_array($rol, self::ROLES, true) ? $rol : 'operador';

        $stmt = $this->db->prepare('UPDATE usuarios SET username = :username, rol = :rol WHERE id = :id');
        $stmt->execute(['username' => $username, 'rol' => $rol, 'id' => $id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET activo = :activo WHERE id = :id');
        $stmt->execute(['activo' => $active ? 1 : 0, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    /**
     * Cuenta administradores activos, opcionalmente excluyendo un id.
     * Sirve para impedir que el sistema quede sin ningun admin habilitado.
     */
    public function activeAdminCount(int $excludeId = 0): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin' AND activo = 1 AND id <> :id"
        );
        $stmt->execute(['id' => $excludeId]);

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    public function usernameExists(string $username, int $excludeId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS total FROM usuarios WHERE username = :username AND id <> :id LIMIT 1'
        );
        $stmt->execute(['username' => $username, 'id' => $excludeId]);

        return (int) ($stmt->fetch()['total'] ?? 0) > 0;
    }

    public function updateUsername(int $id, string $username): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET username = :username WHERE id = :id');
        $stmt->execute(['username' => $username, 'id' => $id]);
    }

    public function updatePassword(int $id, string $password): void
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }

    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);
        if ($user === null) {
            return null;
        }

        $hash = (string) ($user['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return null;
        }

        // La columna 'activo' puede no existir en instalaciones muy antiguas: por
        // defecto se considera activo. Un usuario desactivado no puede iniciar sesion.
        if (array_key_exists('activo', $user) && (int) $user['activo'] === 0) {
            return null;
        }

        unset($user['password_hash']);

        return $user;
    }
}
