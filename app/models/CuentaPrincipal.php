<?php
declare(strict_types=1);

namespace App\Models;

class CuentaPrincipal extends BaseModel
{
    public const DEPARTAMENTOS = [
        'La Paz',
        'Santa Cruz',
        'Cochabamba',
        'Oruro',
        'Potosí',
        'Chuquisaca',
        'Tarija',
        'Beni',
        'Pando',
    ];

    /**
     * Todas las cuentas principales con su plataforma y cupos ocupados.
     */
    public function all(): array
    {
        $sql = "SELECT
                cp.*,
                p.nombre AS plataforma_nombre,
                (SELECT COUNT(*) FROM suscripciones s
                 WHERE s.cuenta_principal_id = cp.id AND s.flag_no_renovo = 0) AS ocupados
            FROM cuentas_principales cp
            INNER JOIN plataformas p ON p.id = cp.plataforma_id
            ORDER BY p.nombre ASC, cp.etiqueta ASC";

        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT cp.*, p.nombre AS plataforma_nombre,
                    (SELECT COUNT(*) FROM suscripciones s
                     WHERE s.cuenta_principal_id = cp.id AND s.flag_no_renovo = 0) AS ocupados
             FROM cuentas_principales cp
             INNER JOIN plataformas p ON p.id = cp.plataforma_id
             WHERE cp.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function countAsignados(int $id): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS n FROM suscripciones
             WHERE cuenta_principal_id = :id AND flag_no_renovo = 0'
        );
        $stmt->execute(['id' => $id]);

        return (int) ($stmt->fetch()['n'] ?? 0);
    }

    public function create(array $data): int
    {
        $password = trim((string) ($data['password_cuenta'] ?? ''));
        $stmt = $this->db->prepare(
            'INSERT INTO cuentas_principales (plataforma_id, etiqueta, correo, password_cuenta, capacidad, activo, notas)
             VALUES (:plataforma_id, :etiqueta, :correo, :password_cuenta, :capacidad, :activo, :notas)'
        );
        $stmt->execute([
            'plataforma_id' => (int) $data['plataforma_id'],
            'etiqueta' => (string) $data['etiqueta'],
            'correo' => ($data['correo'] ?? '') !== '' ? (string) $data['correo'] : null,
            'password_cuenta' => $password !== '' ? \encrypt_secret($password) : null,
            'capacidad' => max(1, (int) ($data['capacidad'] ?? 1)),
            'activo' => (int) ($data['activo'] ?? 1) === 1 ? 1 : 0,
            'notas' => ($data['notas'] ?? '') !== '' ? (string) $data['notas'] : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        // La contraseña solo se cambia si se envía una nueva (vacío = conservar).
        $password = trim((string) ($data['password_cuenta'] ?? ''));
        if ($password !== '') {
            $stmt = $this->db->prepare(
                'UPDATE cuentas_principales SET etiqueta=:etiqueta, correo=:correo,
                    password_cuenta=:password_cuenta, capacidad=:capacidad, activo=:activo, notas=:notas
                 WHERE id=:id'
            );
            $params = ['password_cuenta' => \encrypt_secret($password)];
        } else {
            $stmt = $this->db->prepare(
                'UPDATE cuentas_principales SET etiqueta=:etiqueta, correo=:correo,
                    capacidad=:capacidad, activo=:activo, notas=:notas
                 WHERE id=:id'
            );
            $params = [];
        }

        $params += [
            'id' => $id,
            'etiqueta' => (string) $data['etiqueta'],
            'correo' => ($data['correo'] ?? '') !== '' ? (string) $data['correo'] : null,
            'capacidad' => max(1, (int) ($data['capacidad'] ?? 1)),
            'activo' => (int) ($data['activo'] ?? 1) === 1 ? 1 : 0,
            'notas' => ($data['notas'] ?? '') !== '' ? (string) $data['notas'] : null,
        ];

        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM cuentas_principales WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    /**
     * Usuarios asignados a una cuenta principal (suscripciones) con sus datos.
     */
    public function asignados(int $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.fecha_inicio, s.fecha_vencimiento, s.estado, s.departamento, s.flag_no_renovo,
                    DATEDIFF(s.fecha_vencimiento, CURDATE()) AS dias_para_vencer,
                    c.id AS cliente_id, c.nombre AS cliente_nombre, c.telefono AS cliente_telefono
             FROM suscripciones s
             INNER JOIN clientes c ON c.id = s.cliente_id
             WHERE s.cuenta_principal_id = :id
             ORDER BY s.flag_no_renovo ASC, s.fecha_vencimiento ASC"
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetchAll();
    }
}
