<?php
declare(strict_types=1);

namespace App\Models;

class Cliente extends BaseModel
{
    public function all(string $search = ''): array
    {
        $sql = 'SELECT * FROM clientes';
        $params = [];

        if ($search !== '') {
            $sql .= ' WHERE nombre LIKE :term OR telefono LIKE :term';
            $params['term'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM clientes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO clientes (nombre, telefono, notas) VALUES (:nombre, :telefono, :notas)'
        );
        $stmt->execute([
            'nombre' => $data['nombre'],
            'telefono' => $data['telefono'],
            'notas' => $data['notas'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE clientes SET nombre = :nombre, telefono = :telefono, notas = :notas WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'telefono' => $data['telefono'],
            'notas' => $data['notas'] ?: null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM clientes WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }
}
