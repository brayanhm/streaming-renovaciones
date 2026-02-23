<?php
declare(strict_types=1);

namespace App\Models;

class Plataforma extends BaseModel
{
    public function all(string $search = ''): array
    {
        $sql = 'SELECT * FROM plataformas';
        $params = [];

        if ($search !== '') {
            $sql .= ' WHERE nombre LIKE :term OR tipo_servicio LIKE :term';
            $params['term'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY nombre ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM plataformas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO plataformas (
                nombre,
                tipo_servicio,
                mensaje_menos_2,
                mensaje_menos_1,
                mensaje_rec_7,
                mensaje_rec_15
            ) VALUES (
                :nombre,
                :tipo_servicio,
                :mensaje_menos_2,
                :mensaje_menos_1,
                :mensaje_rec_7,
                :mensaje_rec_15
            )'
        );

        $stmt->execute([
            'nombre' => $data['nombre'],
            'tipo_servicio' => $data['tipo_servicio'],
            'mensaje_menos_2' => $data['mensaje_menos_2'] ?: null,
            'mensaje_menos_1' => $data['mensaje_menos_1'] ?: null,
            'mensaje_rec_7' => $data['mensaje_rec_7'] ?: null,
            'mensaje_rec_15' => $data['mensaje_rec_15'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE plataformas SET
                nombre = :nombre,
                tipo_servicio = :tipo_servicio,
                mensaje_menos_2 = :mensaje_menos_2,
                mensaje_menos_1 = :mensaje_menos_1,
                mensaje_rec_7 = :mensaje_rec_7,
                mensaje_rec_15 = :mensaje_rec_15
            WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'tipo_servicio' => $data['tipo_servicio'],
            'mensaje_menos_2' => $data['mensaje_menos_2'] ?: null,
            'mensaje_menos_1' => $data['mensaje_menos_1'] ?: null,
            'mensaje_rec_7' => $data['mensaje_rec_7'] ?: null,
            'mensaje_rec_15' => $data['mensaje_rec_15'] ?: null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM plataformas WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }
}
