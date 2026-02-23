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
