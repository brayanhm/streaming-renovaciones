<?php
declare(strict_types=1);

namespace App\Models;

class Modalidad extends BaseModel
{
    public const TIPOS_CUENTA = ['CUENTA_COMPLETA', 'POR_DISPOSITIVOS', 'AMBOS'];

    public function all(string $search = ''): array
    {
        $sql = 'SELECT m.*, p.nombre AS plataforma_nombre
            FROM modalidades m
            INNER JOIN plataformas p ON p.id = m.plataforma_id';
        $params = [];

        if ($search !== '') {
            $sql .= ' WHERE m.nombre_modalidad LIKE :term OR p.nombre LIKE :term OR m.tipo_cuenta LIKE :term';
            $params['term'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY p.nombre ASC, m.nombre_modalidad ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function allByPlataforma(int $plataformaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM modalidades WHERE plataforma_id = :plataforma_id ORDER BY nombre_modalidad ASC'
        );
        $stmt->execute(['plataforma_id' => $plataformaId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM modalidades WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO modalidades (
                plataforma_id,
                nombre_modalidad,
                tipo_cuenta,
                duracion_meses,
                dispositivos,
                precio
            ) VALUES (
                :plataforma_id,
                :nombre_modalidad,
                :tipo_cuenta,
                :duracion_meses,
                :dispositivos,
                :precio
            )'
        );
        $stmt->execute([
            'plataforma_id' => $data['plataforma_id'],
            'nombre_modalidad' => $data['nombre_modalidad'],
            'tipo_cuenta' => $data['tipo_cuenta'],
            'duracion_meses' => $data['duracion_meses'],
            'dispositivos' => $data['dispositivos'],
            'precio' => $data['precio'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE modalidades
             SET plataforma_id = :plataforma_id,
                 nombre_modalidad = :nombre_modalidad,
                 tipo_cuenta = :tipo_cuenta,
                 duracion_meses = :duracion_meses,
                 dispositivos = :dispositivos,
                 precio = :precio
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'plataforma_id' => $data['plataforma_id'],
            'nombre_modalidad' => $data['nombre_modalidad'],
            'tipo_cuenta' => $data['tipo_cuenta'],
            'duracion_meses' => $data['duracion_meses'],
            'dispositivos' => $data['dispositivos'],
            'precio' => $data['precio'],
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM modalidades WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public static function tipoCuentaLabel(string $tipo, ?int $dispositivos = null): string
    {
        return match ($tipo) {
            'POR_DISPOSITIVOS' => $dispositivos !== null && $dispositivos > 0
                ? 'Por dispositivos (' . $dispositivos . ')'
                : 'Por dispositivos',
            'AMBOS' => 'Cuenta completa o por dispositivos',
            default => 'Cuenta completa',
        };
    }
}
