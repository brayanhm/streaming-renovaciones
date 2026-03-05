<?php
declare(strict_types=1);

namespace App\Models;

class User extends BaseModel
{
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

        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (username, password_hash, rol) VALUES (:username, :password_hash, :rol)'
        );
        $stmt->execute([
            'username' => $username,
            'password_hash' => $hash,
            'rol' => $rol,
        ]);

        return (int) $this->db->lastInsertId();
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

        unset($user['password_hash']);

        return $user;
    }
}
