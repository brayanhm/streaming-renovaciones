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
                m.nombre_modalidad,
                m.tipo_cuenta,
                m.duracion_meses,
                m.dispositivos,
                m.precio AS modalidad_precio,
                COALESCE(s.precio_venta, m.precio) AS precio_final,
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
                p.mensaje_menos_2,
                p.mensaje_menos_1,
                p.mensaje_rec_7,
                p.mensaje_rec_15,
                m.nombre_modalidad,
                m.tipo_cuenta,
                m.duracion_meses,
                m.dispositivos,
                m.precio AS modalidad_precio,
                COALESCE(s.precio_venta, m.precio) AS precio_final
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
        $updateStmt = $this->db->prepare('UPDATE suscripciones SET estado = :estado WHERE id = :id');

        foreach ($rows as $row) {
            $nextState = $this->resolveState($row, $recupDays);
            $current = (string) ($row['estado'] ?? '');

            if ($nextState !== $current) {
                $updateStmt->execute([
                    'id' => (int) $row['id'],
                    'estado' => $nextState,
                ]);
            }
        }
    }

    public function markWhatsappContact(int $id, string $contactType): bool
    {
        if (!in_array($contactType, self::CONTACTOS, true)) {
            return false;
        }

        $stmt = $this->db->prepare(
            'UPDATE suscripciones
             SET ultimo_contacto_fecha = NOW(), ultimo_contacto_tipo = :tipo, estado = :estado
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'tipo' => $contactType,
            'estado' => 'ESPERA',
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

            $allowedMonths = Plataforma::parseDuracionesDisponibles((string) ($row['duraciones_disponibles'] ?? ''));
            if ($allowedMonths !== [] && !in_array($months, $allowedMonths, true)) {
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
                'INSERT INTO movimientos (suscripcion_id, tipo, meses, monto)
                 VALUES (:suscripcion_id, :tipo, :meses, :monto)'
            );
            $movement->execute([
                'suscripcion_id' => $id,
                'tipo' => 'RENOVACION',
                'meses' => $months,
                'monto' => $row['precio_final'] !== null ? (float) $row['precio_final'] : null,
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

        $phone = \normalize_phone((string) ($subscription['cliente_telefono'] ?? ''));
        if ($phone === '') {
            return null;
        }

        $template = $this->resolveTemplate($subscription, $contactType);
        $message = $this->renderTemplate($template, $subscription);

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }

    public function inferContactType(array $subscription): string
    {
        $dias = (int) ($subscription['dias_para_vencer'] ?? 0);
        if ($dias <= -15) {
            return 'REC_15';
        }
        if ($dias <= -7) {
            return 'REC_7';
        }
        if ($dias <= 1) {
            return 'MENOS_1';
        }

        return 'MENOS_2';
    }

    private function resolveState(array $row, int $recupDays): string
    {
        $today = new DateTimeImmutable('today');
        $dueDate = new DateTimeImmutable((string) $row['fecha_vencimiento']);
        $daysToDue = (int) $today->diff($dueDate)->format('%r%a');

        if ($daysToDue <= 0) {
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
        $minus2Date = $dueDate->modify('-2 days')->format('Y-m-d');
        $minus1Date = $dueDate->modify('-1 days')->format('Y-m-d');

        $contactedMinus2 = $contactType === 'MENOS_2' && $contactDate === $minus2Date;
        $contactedMinus1 = $contactType === 'MENOS_1' && $contactDate === $minus1Date;

        if ($daysToDue === 1) {
            if ($contactedMinus2 && !$contactedMinus1) {
                return 'REENVIAR_1D';
            }

            if ($contactedMinus1 || $contactedMinus2) {
                return 'ESPERA';
            }

            return 'CONTACTAR_2D';
        }

        if ($daysToDue === 2) {
            return $contactedMinus2 ? 'ESPERA' : 'CONTACTAR_2D';
        }

        return 'ACTIVO';
    }

    private function resolveTemplate(array $subscription, string $contactType): string
    {
        return match ($contactType) {
            'MENOS_1' => trim((string) ($subscription['mensaje_menos_1'] ?? '')) ?: DEFAULT_TEMPLATE_MENOS_1,
            'REC_7' => trim((string) ($subscription['mensaje_rec_7'] ?? '')) ?: DEFAULT_TEMPLATE_RECUP,
            'REC_15' => trim((string) ($subscription['mensaje_rec_15'] ?? '')) ?: DEFAULT_TEMPLATE_RECUP,
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
