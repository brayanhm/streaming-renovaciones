<?php
declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Exception;

class Suscripcion extends BaseModel
{
    public const ESTADOS = ['CONTACTAR_2D', 'REENVIAR_1D', 'ESPERA', 'ACTIVO', 'VENCIDO', 'RECUP'];
    public const CONTACTOS = ['MENOS_2', 'MENOS_1', 'REC_7', 'REC_15'];

    public function all(string $search = '', string $estado = ''): array
    {
        $sql = 'SELECT
                s.*,
                c.nombre AS cliente_nombre,
                c.telefono AS cliente_telefono,
                p.nombre AS plataforma_nombre,
                p.tipo_servicio AS plataforma_tipo_servicio,
                p.duraciones_disponibles AS plataforma_duraciones_disponibles,
                p.dato_renovacion AS plataforma_dato_renovacion,
                m.nombre_modalidad,
                m.tipo_cuenta,
                m.duracion_meses,
                m.dispositivos,
                m.precio AS modalidad_precio,
                m.costo AS modalidad_costo,
                COALESCE(s.precio_venta, m.precio) AS precio_final,
                COALESCE(s.costo_base, m.costo) AS costo_final,
                (COALESCE(s.precio_venta, m.precio) - COALESCE(s.costo_base, m.costo)) AS ganancia_final,
                DATEDIFF(s.fecha_vencimiento, CURDATE()) AS dias_para_vencer
            FROM suscripciones s
            INNER JOIN clientes c ON c.id = s.cliente_id
            INNER JOIN plataformas p ON p.id = s.plataforma_id
            INNER JOIN modalidades m ON m.id = s.modalidad_id';
        $conditions = [];
        $params = [];

        if ($search !== '') {
            $conditions[] = '(c.nombre LIKE :term OR c.telefono LIKE :term OR p.nombre LIKE :term OR m.nombre_modalidad LIKE :term)';
            $params['term'] = '%' . $search . '%';
        }

        if ($estado !== '' && in_array($estado, self::ESTADOS, true)) {
            $conditions[] = 's.estado = :estado';
            $params['estado'] = $estado;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY s.fecha_vencimiento ASC, s.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM suscripciones WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function findWithRelations(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT
                s.*,
                c.nombre AS cliente_nombre,
                c.telefono AS cliente_telefono,
                p.nombre AS plataforma_nombre,
                p.tipo_servicio AS plataforma_tipo_servicio,
                p.duraciones_disponibles AS plataforma_duraciones_disponibles,
                p.dato_renovacion AS plataforma_dato_renovacion,
                p.mensaje_menos_2,
                p.mensaje_menos_1,
                p.mensaje_rec_7,
                p.mensaje_rec_15,
                m.nombre_modalidad,
                m.tipo_cuenta,
                m.duracion_meses,
                m.dispositivos,
                m.precio AS modalidad_precio,
                m.costo AS modalidad_costo,
                COALESCE(s.precio_venta, m.precio) AS precio_final,
                COALESCE(s.costo_base, m.costo) AS costo_final,
                (COALESCE(s.precio_venta, m.precio) - COALESCE(s.costo_base, m.costo)) AS ganancia_final
            FROM suscripciones s
            INNER JOIN clientes c ON c.id = s.cliente_id
            INNER JOIN plataformas p ON p.id = s.plataforma_id
            INNER JOIN modalidades m ON m.id = s.modalidad_id
            WHERE s.id = :id
            LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO suscripciones (
                cliente_id,
                plataforma_id,
                modalidad_id,
                precio_venta,
                costo_base,
                fecha_inicio,
                fecha_vencimiento,
                estado,
                usuario_proveedor,
                flag_no_renovo
            ) VALUES (
                :cliente_id,
                :plataforma_id,
                :modalidad_id,
                :precio_venta,
                :costo_base,
                :fecha_inicio,
                :fecha_vencimiento,
                :estado,
                :usuario_proveedor,
                :flag_no_renovo
            )'
        );

        $stmt->execute([
            'cliente_id' => $data['cliente_id'],
            'plataforma_id' => $data['plataforma_id'],
            'modalidad_id' => $data['modalidad_id'],
            'precio_venta' => $data['precio_venta'],
            'costo_base' => $data['costo_base'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_vencimiento' => $data['fecha_vencimiento'],
            'estado' => $data['estado'],
            'usuario_proveedor' => $data['usuario_proveedor'] ?: null,
            'flag_no_renovo' => $data['flag_no_renovo'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE suscripciones SET
                cliente_id = :cliente_id,
                plataforma_id = :plataforma_id,
                modalidad_id = :modalidad_id,
                precio_venta = :precio_venta,
                costo_base = :costo_base,
                fecha_inicio = :fecha_inicio,
                fecha_vencimiento = :fecha_vencimiento,
                estado = :estado,
                usuario_proveedor = :usuario_proveedor,
                flag_no_renovo = :flag_no_renovo
            WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'cliente_id' => $data['cliente_id'],
            'plataforma_id' => $data['plataforma_id'],
            'modalidad_id' => $data['modalidad_id'],
            'precio_venta' => $data['precio_venta'],
            'costo_base' => $data['costo_base'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_vencimiento' => $data['fecha_vencimiento'],
            'estado' => $data['estado'],
            'usuario_proveedor' => $data['usuario_proveedor'] ?: null,
            'flag_no_renovo' => $data['flag_no_renovo'],
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM suscripciones WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function recalculateStates(int $recupDays = RECUP_DAYS): void
    {
        $stmt = $this->db->query(
            'SELECT id, fecha_vencimiento, estado, ultimo_contacto_fecha, ultimo_contacto_tipo, flag_no_renovo
             FROM suscripciones'
        );
        $rows = $stmt->fetchAll();
        $updateStmt = $this->db->prepare(
            'UPDATE suscripciones
             SET estado = :estado, flag_no_renovo = :flag_no_renovo
             WHERE id = :id'
        );

        foreach ($rows as $row) {
            $nextState = $this->resolveState($row, $recupDays);
            $current = (string) ($row['estado'] ?? '');
            $currentFlagNoRenovo = (int) ($row['flag_no_renovo'] ?? 0);
            $nextFlagNoRenovo = $currentFlagNoRenovo;

            $today = new DateTimeImmutable('today');
            $dueDate = new DateTimeImmutable((string) $row['fecha_vencimiento']);
            $daysToDue = (int) $today->diff($dueDate)->format('%r%a');

            // Cuando la vigencia ya paso, se marca automaticamente como no renovado.
            if ($daysToDue < 0) {
                $nextFlagNoRenovo = 1;
            }

            if ($nextState !== $current || $nextFlagNoRenovo !== $currentFlagNoRenovo) {
                $updateStmt->execute([
                    'id' => (int) $row['id'],
                    'estado' => $nextState,
                    'flag_no_renovo' => $nextFlagNoRenovo,
                ]);
            }
        }
    }

    public function markWhatsappContact(int $id, string $contactType): bool
    {
        if (!in_array($contactType, self::CONTACTOS, true)) {
            return false;
        }

        $findStmt = $this->db->prepare(
            'SELECT id, fecha_vencimiento, flag_no_renovo
             FROM suscripciones
             WHERE id = :id
             LIMIT 1'
        );
        $findStmt->execute(['id' => $id]);
        $row = $findStmt->fetch();

        if (!$row) {
            return false;
        }

        $contactedAt = new DateTimeImmutable('now');
        $today = new DateTimeImmutable('today');
        $dueDate = new DateTimeImmutable((string) $row['fecha_vencimiento']);
        $daysToDue = (int) $today->diff($dueDate)->format('%r%a');
        $nextFlagNoRenovo = $daysToDue < 0 ? 1 : (int) ($row['flag_no_renovo'] ?? 0);

        $nextState = $this->resolveState([
            'fecha_vencimiento' => (string) $row['fecha_vencimiento'],
            'flag_no_renovo' => (int) ($row['flag_no_renovo'] ?? 0),
            'ultimo_contacto_fecha' => $contactedAt->format('Y-m-d H:i:s'),
            'ultimo_contacto_tipo' => $contactType,
        ], RECUP_DAYS);

        $updateStmt = $this->db->prepare(
            'UPDATE suscripciones
             SET ultimo_contacto_fecha = :contacted_at, ultimo_contacto_tipo = :tipo, estado = :estado, flag_no_renovo = :flag_no_renovo
             WHERE id = :id'
        );

        return $updateStmt->execute([
            'id' => $id,
            'contacted_at' => $contactedAt->format('Y-m-d H:i:s'),
            'tipo' => $contactType,
            'estado' => $nextState,
            'flag_no_renovo' => $nextFlagNoRenovo,
        ]);
    }

    public function markNoRenovo(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE suscripciones
             SET flag_no_renovo = 1
             WHERE id = :id'
        );

        return $stmt->execute(['id' => $id]);
    }

    public function renovar(int $id, int $months): ?string
    {
        if ($months <= 0) {
            return null;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                'SELECT
                    s.id,
                    s.fecha_vencimiento,
                    COALESCE(s.precio_venta, m.precio) AS precio_final,
                    COALESCE(s.costo_base, m.costo) AS costo_final,
                    p.duraciones_disponibles
                 FROM suscripciones s
                 INNER JOIN modalidades m ON m.id = s.modalidad_id
                 INNER JOIN plataformas p ON p.id = s.plataforma_id
                 WHERE s.id = :id
                 FOR UPDATE'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            if (!$row) {
                $this->db->rollBack();

                return null;
            }

            $allowedMonths = Plataforma::resolveRenewalMonths(
                isset($row['duraciones_disponibles']) ? (string) $row['duraciones_disponibles'] : null
            );
            if (!in_array($months, $allowedMonths, true)) {
                $this->db->rollBack();

                return null;
            }

            $today = new DateTimeImmutable('today');
            $currentDue = new DateTimeImmutable((string) $row['fecha_vencimiento']);
            $base = $currentDue < $today ? $today : $currentDue;
            $newDue = $base->modify('+' . $months . ' months');
            $newDueStr = $newDue->format('Y-m-d');

            $update = $this->db->prepare(
                'UPDATE suscripciones
                 SET fecha_vencimiento = :fecha_vencimiento,
                     estado = :estado,
                     flag_no_renovo = 0,
                     ultimo_contacto_fecha = NULL,
                     ultimo_contacto_tipo = NULL
                 WHERE id = :id'
            );
            $update->execute([
                'id' => $id,
                'fecha_vencimiento' => $newDueStr,
                'estado' => 'ACTIVO',
            ]);

            $movement = $this->db->prepare(
                'INSERT INTO movimientos (suscripcion_id, tipo, meses, monto, costo, utilidad)
                 VALUES (:suscripcion_id, :tipo, :meses, :monto, :costo, :utilidad)'
            );
            $monto = $row['precio_final'] !== null ? (float) $row['precio_final'] : null;
            $costo = $row['costo_final'] !== null ? (float) $row['costo_final'] : null;
            $movement->execute([
                'suscripcion_id' => $id,
                'tipo' => 'RENOVACION',
                'meses' => $months,
                'monto' => $monto,
                'costo' => $costo,
                'utilidad' => ($monto !== null && $costo !== null) ? ($monto - $costo) : null,
            ]);

            $this->db->commit();

            return $newDueStr;
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function buildWhatsAppLink(int $id, string $contactType): ?string
    {
        $subscription = $this->findWithRelations($id);
        if ($subscription === null) {
            return null;
        }

        $phone = \normalize_whatsapp_phone_bolivia((string) ($subscription['cliente_telefono'] ?? ''));
        if ($phone === '' || !\is_valid_whatsapp_phone_bolivia($phone)) {
            return null;
        }

        $template = $this->resolveTemplate($subscription, $contactType);
        $message = $this->renderTemplate($template, $subscription);
        $encodedMessage = rawurlencode($message);
        // Preservar marcadores de formato para que WhatsApp procese *negritas*, _cursivas_ y ~tachado~.
        $encodedMessage = str_replace(['%2A', '%5F', '%7E'], ['*', '_', '~'], $encodedMessage);

        return 'https://wa.me/' . $phone . '?text=' . $encodedMessage;
    }

    public function inferContactType(array $subscription): string
    {
        $dias = (int) ($subscription['dias_para_vencer'] ?? 0);
        if ($dias <= -RECUP_DAYS) {
            return 'REC_7';
        }
        if ($dias <= 0) {
            return 'MENOS_1';
        }
        if ($dias <= 3) {
            return 'MENOS_2';
        }

        return 'MENOS_2';
    }

    private function resolveState(array $row, int $recupDays): string
    {
        $today = new DateTimeImmutable('today');
        $dueDate = new DateTimeImmutable((string) $row['fecha_vencimiento']);
        $daysToDue = (int) $today->diff($dueDate)->format('%r%a');

        if ($daysToDue < 0) {
            if (abs($daysToDue) >= $recupDays) {
                return 'RECUP';
            }

            return 'VENCIDO';
        }

        $contactDate = null;
        if (!empty($row['ultimo_contacto_fecha'])) {
            $contactDate = (new DateTimeImmutable((string) $row['ultimo_contacto_fecha']))->format('Y-m-d');
        }

        $contactType = (string) ($row['ultimo_contacto_tipo'] ?? '');
        $minus3Date = $dueDate->modify('-3 days')->format('Y-m-d');
        $dueDayDate = $dueDate->format('Y-m-d');

        $contactedMinus3 = $contactType === 'MENOS_2' && $contactDate === $minus3Date;
        $contactedDueDay = $contactType === 'MENOS_1' && $contactDate === $dueDayDate;

        if ($daysToDue === 0) {
            if ($contactedDueDay) {
                return 'ESPERA';
            }

            return 'REENVIAR_1D';
        }

        if ($daysToDue === 3) {
            return $contactedMinus3 ? 'ESPERA' : 'CONTACTAR_2D';
        }

        if ($daysToDue === 1 || $daysToDue === 2) {
            return 'ESPERA';
        }

        return 'ACTIVO';
    }

    private function resolveTemplate(array $subscription, string $contactType): string
    {
        return match ($contactType) {
            'MENOS_1' => trim((string) ($subscription['mensaje_menos_1'] ?? '')) ?: DEFAULT_TEMPLATE_MENOS_1,
            'REC_7' => trim((string) ($subscription['mensaje_rec_7'] ?? '')) ?: DEFAULT_TEMPLATE_RECUP,
            'REC_15' => trim((string) ($subscription['mensaje_rec_7'] ?? '')) ?: DEFAULT_TEMPLATE_RECUP,
            default => trim((string) ($subscription['mensaje_menos_2'] ?? '')) ?: DEFAULT_TEMPLATE_MENOS_2,
        };
    }

    private function renderTemplate(string $template, array $subscription): string
    {
        $fechaVence = '';
        if (!empty($subscription['fecha_vencimiento'])) {
            $fechaVence = (new DateTimeImmutable((string) $subscription['fecha_vencimiento']))->format('d/m/Y');
        }

        $price = \money((float) ($subscription['precio_final'] ?? $subscription['modalidad_precio'] ?? 0));

        $replace = [
            '{NOMBRE}' => (string) ($subscription['cliente_nombre'] ?? ''),
            '{PLATAFORMA}' => (string) ($subscription['plataforma_nombre'] ?? ''),
            '{PLAN}' => $this->buildPlanLabel($subscription),
            '{FECHA_VENCE}' => $fechaVence,
            '{PRECIO}' => $price,
        ];

        return strtr($template, $replace);
    }

    private function buildPlanLabel(array $subscription): string
    {
        $nombre = (string) ($subscription['nombre_modalidad'] ?? '');
        $duracion = max(1, (int) ($subscription['duracion_meses'] ?? 1));
        $tipoCuenta = (string) ($subscription['tipo_cuenta'] ?? 'CUENTA_COMPLETA');
        $dispositivos = isset($subscription['dispositivos']) ? (int) $subscription['dispositivos'] : null;

        $tipoCuentaLabel = Modalidad::tipoCuentaLabel($tipoCuenta, $dispositivos);
        $mesesLabel = $duracion . ' ' . ($duracion === 1 ? 'mes' : 'meses');

        return trim($nombre . ' - ' . $tipoCuentaLabel . ' - ' . $mesesLabel);
    }
}
