<?php
declare(strict_types=1);

namespace App\Models;

class Modalidad extends BaseModel
{
    public const TIPOS_CUENTA = ['CUENTA_COMPLETA', 'POR_DISPOSITIVOS', 'AMBOS'];

    public function all(string $search = '', int $plataformaId = 0): array
    {
        $sql = 'SELECT
                m.*,
                p.nombre AS plataforma_nombre,
                (m.precio - m.costo) AS utilidad_plan
            FROM modalidades m
            INNER JOIN plataformas p ON p.id = m.plataforma_id';
        $conditions = [];
        $params = [];

        if ($plataformaId > 0) {
            $conditions[] = 'm.plataforma_id = :plataforma_id';
            $params['plataforma_id'] = $plataformaId;
        }

        if ($search !== '') {
            $conditions[] = '(m.nombre_modalidad LIKE :term OR p.nombre LIKE :term OR m.tipo_cuenta LIKE :term)';
            $params['term'] = '%' . $search . '%';
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY p.nombre ASC, m.nombre_modalidad ASC, m.duracion_meses ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function allByPlataforma(int $plataformaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM modalidades WHERE plataforma_id = :plataforma_id ORDER BY nombre_modalidad ASC, duracion_meses ASC'
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
                precio,
                costo
            ) VALUES (
                :plataforma_id,
                :nombre_modalidad,
                :tipo_cuenta,
                :duracion_meses,
                :dispositivos,
                :precio,
                :costo
            )'
        );
        $stmt->execute([
            'plataforma_id' => $data['plataforma_id'],
            'nombre_modalidad' => $data['nombre_modalidad'],
            'tipo_cuenta' => $data['tipo_cuenta'],
            'duracion_meses' => $data['duracion_meses'],
            'dispositivos' => $data['dispositivos'],
            'precio' => $data['precio'],
            'costo' => $data['costo'],
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
                 precio = :precio,
                 costo = :costo
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
            'costo' => $data['costo'],
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM modalidades WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function ensureTemplateDurations(array $data, array $duraciones): int
    {
        $duraciones = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => max(0, (int) $value),
            $duraciones
        ))));
        sort($duraciones);

        $inserted = 0;
        foreach ($duraciones as $duracion) {
            if ($duracion <= 0) {
                continue;
            }

            if ($this->existsTemplateDuration(
                (int) $data['plataforma_id'],
                (string) $data['nombre_modalidad'],
                (string) $data['tipo_cuenta'],
                isset($data['dispositivos']) ? (int) $data['dispositivos'] : null,
                $duracion
            )) {
                continue;
            }

            $payload = $data;
            $payload['duracion_meses'] = $duracion;
            $this->create($payload);
            $inserted++;
        }

        return $inserted;
    }

    public function ensurePlatformDurations(int $plataformaId, array $duraciones): int
    {
        $rows = $this->allByPlataforma($plataformaId);
        if ($rows === []) {
            return 0;
        }

        $inserted = 0;
        $templates = [];
        foreach ($rows as $row) {
            $dispositivos = isset($row['dispositivos']) && $row['dispositivos'] !== null
                ? (int) $row['dispositivos']
                : null;
            $key = implode('|', [
                (string) ($row['nombre_modalidad'] ?? ''),
                (string) ($row['tipo_cuenta'] ?? ''),
                $dispositivos === null ? 'NULL' : (string) $dispositivos,
            ]);

            if (isset($templates[$key])) {
                continue;
            }

            $templates[$key] = [
                'plataforma_id' => $plataformaId,
                'nombre_modalidad' => (string) ($row['nombre_modalidad'] ?? ''),
                'tipo_cuenta' => (string) ($row['tipo_cuenta'] ?? 'CUENTA_COMPLETA'),
                'duracion_meses' => (int) ($row['duracion_meses'] ?? 1),
                'dispositivos' => $dispositivos,
                'precio' => (string) ((float) ($row['precio'] ?? 0)),
                'costo' => (string) ((float) ($row['costo'] ?? 0)),
            ];
        }

        foreach ($templates as $template) {
            $inserted += $this->ensureTemplateDurations($template, $duraciones);
        }

        return $inserted;
    }

    private function existsTemplateDuration(
        int $plataformaId,
        string $nombreModalidad,
        string $tipoCuenta,
        ?int $dispositivos,
        int $duracionMeses
    ): bool {
        $stmt = $this->db->prepare(
            'SELECT id
             FROM modalidades
             WHERE plataforma_id = :plataforma_id
               AND nombre_modalidad = :nombre_modalidad
               AND tipo_cuenta = :tipo_cuenta
               AND duracion_meses = :duracion_meses
               AND dispositivos <=> :dispositivos
             LIMIT 1'
        );
        $stmt->execute([
            'plataforma_id' => $plataformaId,
            'nombre_modalidad' => $nombreModalidad,
            'tipo_cuenta' => $tipoCuenta,
            'duracion_meses' => $duracionMeses,
            'dispositivos' => $dispositivos,
        ]);

        return (bool) $stmt->fetch();
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
