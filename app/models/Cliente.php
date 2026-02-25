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

    public function countMissingContactData(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM clientes WHERE nombre = '' OR telefono = ''");
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function missingContactData(string $search = ''): array
    {
        $sql = "SELECT
                c.*,
                s.usuario_proveedor,
                s.fecha_vencimiento,
                p.nombre AS plataforma_nombre
            FROM clientes c
            LEFT JOIN suscripciones s ON s.id = (
                SELECT s2.id
                FROM suscripciones s2
                WHERE s2.cliente_id = c.id
                ORDER BY s2.fecha_vencimiento DESC, s2.id DESC
                LIMIT 1
            )
            LEFT JOIN plataformas p ON p.id = s.plataforma_id
            WHERE (c.nombre = '' OR c.telefono = '')";
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (c.nombre LIKE :term OR c.telefono LIKE :term OR s.usuario_proveedor LIKE :term)';
            $params['term'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY c.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
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
