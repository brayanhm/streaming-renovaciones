<?php
declare(strict_types=1);

namespace App\Models;

class Plataforma extends BaseModel
{
    public const DEFAULT_RENEWAL_MONTHS = [1, 3, 6];
    public const DATOS_RENOVACION = ['USUARIO', 'CORREO', 'NO_APLICA'];

    public static function parseDuracionesDisponibles(?string $csv): array
    {
        if ($csv === null) {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', trim($csv)) ?: [];
        $values = [];

        foreach ($parts as $part) {
            if ($part === '' || !ctype_digit($part)) {
                continue;
            }

            $months = (int) $part;
            if ($months <= 0) {
                continue;
            }

            $values[$months] = $months;
        }

        ksort($values);

        return array_values($values);
    }

    public static function normalizeDuracionesDisponibles(?string $csv): ?string
    {
        $values = self::parseDuracionesDisponibles($csv);
        if ($values === []) {
            return null;
        }

        return implode(',', $values);
    }

    public static function resolveRenewalMonths(?string $csv): array
    {
        $configured = self::parseDuracionesDisponibles($csv);

        return $configured !== [] ? $configured : self::DEFAULT_RENEWAL_MONTHS;
    }

    public static function normalizeDatoRenovacion(?string $value, string $tipoServicio): string
    {
        $tipoServicio = strtoupper(trim($tipoServicio));
        if ($tipoServicio !== 'RENOVABLE') {
            return 'NO_APLICA';
        }

        $value = strtoupper(trim((string) $value));

        return in_array($value, ['USUARIO', 'CORREO'], true) ? $value : 'USUARIO';
    }

    public static function datoRenovacionLabel(?string $value): string
    {
        return match (strtoupper(trim((string) $value))) {
            'CORREO' => 'Correo',
            'USUARIO' => 'Usuario',
            default => 'No aplica',
        };
    }

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
                duraciones_disponibles,
                dato_renovacion,
                mensaje_menos_2,
                mensaje_menos_1,
                mensaje_rec_7,
                mensaje_rec_15
            ) VALUES (
                :nombre,
                :tipo_servicio,
                :duraciones_disponibles,
                :dato_renovacion,
                :mensaje_menos_2,
                :mensaje_menos_1,
                :mensaje_rec_7,
                :mensaje_rec_15
            )'
        );

        $stmt->execute([
            'nombre' => $data['nombre'],
            'tipo_servicio' => $data['tipo_servicio'],
            'duraciones_disponibles' => $data['duraciones_disponibles'],
            'dato_renovacion' => $data['dato_renovacion'],
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
                duraciones_disponibles = :duraciones_disponibles,
                dato_renovacion = :dato_renovacion,
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
            'duraciones_disponibles' => $data['duraciones_disponibles'],
            'dato_renovacion' => $data['dato_renovacion'],
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
