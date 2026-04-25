<?php
declare(strict_types=1);

namespace App\Models;

class Movimiento extends BaseModel
{
    public function createRenovacion(int $suscripcionId, int $meses, ?float $monto): int
    {
        $subscription = $this->db->prepare(
            'SELECT s.plataforma_id, p.nombre AS plataforma_nombre
             FROM suscripciones s
             INNER JOIN plataformas p ON p.id = s.plataforma_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $subscription->execute(['id' => $suscripcionId]);
        $row = $subscription->fetch() ?: [];

        $stmt = $this->db->prepare(
            'INSERT INTO movimientos (suscripcion_id, plataforma_id, plataforma_nombre, tipo, meses, monto)
             VALUES (:suscripcion_id, :plataforma_id, :plataforma_nombre, :tipo, :meses, :monto)'
        );
        $stmt->execute([
            'suscripcion_id' => $suscripcionId,
            'plataforma_id' => isset($row['plataforma_id']) ? (int) $row['plataforma_id'] : null,
            'plataforma_nombre' => isset($row['plataforma_nombre']) ? (string) $row['plataforma_nombre'] : null,
            'tipo' => 'RENOVACION',
            'meses' => $meses,
            'monto' => $monto,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function allBySuscripcion(int $suscripcionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT mv.*
             FROM movimientos mv
             WHERE mv.suscripcion_id = :suscripcion_id
             ORDER BY mv.fecha DESC, mv.id DESC'
        );
        $stmt->execute(['suscripcion_id' => $suscripcionId]);

        return $stmt->fetchAll();
    }

    public function reportePorMes(int $meses = 12): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                YEAR(fecha) AS anio,
                MONTH(fecha) AS mes,
                COUNT(*) AS renovaciones,
                SUM(monto) AS total_monto,
                SUM(costo) AS total_costo,
                SUM(utilidad) AS total_utilidad
             FROM movimientos
             WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL :meses MONTH)
             GROUP BY YEAR(fecha), MONTH(fecha)
             ORDER BY YEAR(fecha) ASC, MONTH(fecha) ASC'
        );
        $stmt->execute(['meses' => $meses]);

        return $stmt->fetchAll();
    }

    public function reportePorPlataforma(): array
    {
        $stmt = $this->db->query(
            'SELECT
                COALESCE(p.nombre, mv.plataforma_nombre, "Sin plataforma") AS plataforma,
                COUNT(mv.id) AS renovaciones,
                SUM(mv.monto) AS total_monto,
                SUM(mv.costo) AS total_costo,
                SUM(mv.utilidad) AS total_utilidad
             FROM movimientos mv
             LEFT JOIN suscripciones s ON s.id = mv.suscripcion_id
             LEFT JOIN plataformas p ON p.id = COALESCE(mv.plataforma_id, s.plataforma_id)
             GROUP BY COALESCE(mv.plataforma_id, s.plataforma_id, 0), COALESCE(p.nombre, mv.plataforma_nombre, "Sin plataforma")
             ORDER BY total_utilidad DESC'
        );

        return $stmt->fetchAll();
    }

    public function resumenMesActual(): array
    {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS renovaciones,
                COALESCE(SUM(monto), 0) AS total_monto,
                COALESCE(SUM(costo), 0) AS total_costo,
                COALESCE(SUM(utilidad), 0) AS total_utilidad
             FROM movimientos
             WHERE YEAR(fecha) = YEAR(CURDATE())
               AND MONTH(fecha) = MONTH(CURDATE())"
        );

        return $stmt->fetch() ?: ['renovaciones' => 0, 'total_monto' => 0, 'total_costo' => 0, 'total_utilidad' => 0];
    }
}
