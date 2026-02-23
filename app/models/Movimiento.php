<?php
declare(strict_types=1);

namespace App\Models;

class Movimiento extends BaseModel
{
    public function createRenovacion(int $suscripcionId, int $meses, ?float $monto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO movimientos (suscripcion_id, tipo, meses, monto)
             VALUES (:suscripcion_id, :tipo, :meses, :monto)'
        );
        $stmt->execute([
            'suscripcion_id' => $suscripcionId,
            'tipo' => 'RENOVACION',
            'meses' => $meses,
            'monto' => $monto,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
