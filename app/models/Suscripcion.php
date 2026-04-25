<?php
declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Exception;

class Suscripcion extends BaseModel
{
    public const ESTADOS = ['CONTACTAR_2D', 'REENVIAR_1D', 'ESPERA', 'ACTIVO', 'VENCIDO', 'RECUP'];
    public const CONTACTOS = ['MENOS_2', 'MENOS_1', 'REC_7', 'REC_15'];
    public const FILTROS_BUSQUEDA = ['TODOS', 'CONTACTO', 'USUARIO', 'TELEFONO'];

    public function all(
        string $search = '',
        string $estado = '',
        string $searchField = 'TODOS',
        string $contacto = '',
        string $usuario = '',
        string $telefono = '',
        int $limit = 0,
        int $offset = 0
    ): array
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
                (
                    SELECT GROUP_CONCAT(m2.duracion_meses ORDER BY m2.duracion_meses SEPARATOR \',\')
                    FROM modalidades m2
                    WHERE m2.plataforma_id = s.plataforma_id
                      AND m2.nombre_modalidad = m.nombre_modalidad
                      AND m2.tipo_cuenta = m.tipo_cuenta
                      AND m2.dispositivos <=> m.dispositivos
                ) AS modalidad_duraciones_disponibles,
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

        $searchField = strtoupper(trim($searchField));
        if (!in_array($searchField, self::FILTROS_BUSQUEDA, true)) {
            $searchField = 'TODOS';
        }

        $contacto = trim($contacto);
        $usuario = trim($usuario);
        $telefono = trim($telefono);

        if ($contacto !== '' || $usuario !== '' || $telefono !== '') {
            if ($contacto !== '') {
                $conditions[] = 'c.nombre LIKE :contacto_term';
                $params['contacto_term'] = '%' . $contacto . '%';
            }
            if ($usuario !== '') {
                $conditions[] = 's.usuario_proveedor LIKE :usuario_term';
                $params['usuario_term'] = '%' . $usuario . '%';
            }
            if ($telefono !== '') {
                $conditions[] = 'c.telefono LIKE :telefono_term';
                $params['telefono_term'] = '%' . $telefono . '%';
            }
        } elseif ($search !== '') {
            if ($searchField === 'CONTACTO') {
                $conditions[] = 'c.nombre LIKE :term';
            } elseif ($searchField === 'USUARIO') {
                $conditions[] = 's.usuario_proveedor LIKE :term';
            } elseif ($searchField === 'TELEFONO') {
                $conditions[] = 'c.telefono LIKE :term';
            } else {
                $conditions[] = '(c.nombre LIKE :term_contacto OR c.telefono LIKE :term_telefono OR s.usuario_proveedor LIKE :term_usuario OR p.nombre LIKE :term_plataforma OR m.nombre_modalidad LIKE :term_modalidad)';
                $like = '%' . $search . '%';
                $params['term_contacto'] = $like;
                $params['term_telefono'] = $like;
                $params['term_usuario'] = $like;
                $params['term_plataforma'] = $like;
                $params['term_modalidad'] = $like;
            }
            if ($searchField !== 'TODOS') {
                $params['term'] = '%' . $search . '%';
            }
        }

        if ($estado !== '' && in_array($estado, self::ESTADOS, true)) {
            $conditions[] = 's.estado = :estado';
            $params['estado'] = $estado;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY s.fecha_vencimiento ASC, s.id DESC';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }

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
        $estado = ((int) ($data['flag_no_renovo'] ?? 0) === 1) ? 'VENCIDO' : (string) $data['estado'];

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
                notas,
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
                :notas,
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
            'estado' => $estado,
            'usuario_proveedor' => $data['usuario_proveedor'] ?: null,
            'notas' => isset($data['notas']) && $data['notas'] !== '' ? $data['notas'] : null,
            'flag_no_renovo' => $data['flag_no_renovo'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $estado = ((int) ($data['flag_no_renovo'] ?? 0) === 1) ? 'VENCIDO' : (string) $data['estado'];

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
                notas = :notas,
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
            'estado' => $estado,
            'usuario_proveedor' => $data['usuario_proveedor'] ?: null,
            'notas' => isset($data['notas']) && $data['notas'] !== '' ? $data['notas'] : null,
            'flag_no_renovo' => $data['flag_no_renovo'],
        ]);
    }

    public function updateDueDate(int $id, string $dueDate): bool
    {
        $subscription = $this->find($id);
        if ($subscription === null) {
            return false;
        }

        $state = $this->resolveState([
            'fecha_vencimiento' => $dueDate,
            'estado' => (string) ($subscription['estado'] ?? 'ACTIVO'),
            'flag_no_renovo' => 0,
            'ultimo_contacto_tipo' => '',
        ], RECUP_DAYS);

        $stmt = $this->db->prepare(
            'UPDATE suscripciones
             SET fecha_vencimiento = :fecha_vencimiento,
                 estado = :estado,
                 flag_no_renovo = 0,
                 ultimo_contacto_fecha = NULL,
                 ultimo_contacto_tipo = NULL
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'fecha_vencimiento' => $dueDate,
            'estado' => $state,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM suscripciones WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function recalculateStates(int $recupDays = RECUP_DAYS): void
    {
        $sql = "UPDATE suscripciones
            SET
              estado = CASE
                WHEN flag_no_renovo = 1 THEN 'VENCIDO'
                WHEN DATEDIFF(fecha_vencimiento, CURDATE()) < 0 THEN
                  CASE
                    WHEN ABS(DATEDIFF(fecha_vencimiento, CURDATE())) >= :recupDays THEN 'RECUP'
                    ELSE 'VENCIDO'
                  END
                WHEN DATEDIFF(fecha_vencimiento, CURDATE()) >= 0 THEN 'ACTIVO'
                ELSE estado
              END";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['recupDays' => $recupDays]);
    }

    public function markWhatsappContact(int $id, string $contactType): bool
    {
        if (!in_array($contactType, self::CONTACTOS, true)) {
            return false;
        }

        $findStmt = $this->db->prepare(
            'SELECT id, fecha_vencimiento, estado, flag_no_renovo
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
        $nextFlagNoRenovo = (int) ($row['flag_no_renovo'] ?? 0);

        $nextState = $this->resolveState([
            'fecha_vencimiento' => (string) $row['fecha_vencimiento'],
            'estado' => (string) ($row['estado'] ?? ''),
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
             SET flag_no_renovo = 1,
                 estado = :estado
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'estado' => 'VENCIDO',
        ]);
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
                    s.plataforma_id,
                    p.nombre AS plataforma_nombre,
                    m.nombre_modalidad,
                    m.tipo_cuenta,
                    m.duracion_meses,
                    m.dispositivos,
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

            $targetModalidad = $this->findMatchingRenewalModalidad(
                (int) $row['plataforma_id'],
                (string) ($row['nombre_modalidad'] ?? ''),
                (string) ($row['tipo_cuenta'] ?? 'CUENTA_COMPLETA'),
                isset($row['dispositivos']) ? (int) $row['dispositivos'] : null,
                $months
            );
            if ($targetModalidad === null) {
                $this->db->rollBack();

                return null;
            }

            $preserveCurrentAmounts = (int) ($row['duracion_meses'] ?? 0) === $months;
            $nextPrecioVenta = $preserveCurrentAmounts
                ? number_format((float) ($row['precio_final'] ?? $targetModalidad['precio'] ?? 0), 2, '.', '')
                : (string) $targetModalidad['precio'];
            $nextCostoBase = $preserveCurrentAmounts
                ? number_format((float) ($row['costo_final'] ?? $targetModalidad['costo'] ?? 0), 2, '.', '')
                : (string) $targetModalidad['costo'];

            $today = new DateTimeImmutable('today');
            $currentDue = new DateTimeImmutable((string) $row['fecha_vencimiento']);

            // Si aun no vencio, renovar sobre el vencimiento actual.
            // Si ya vencio, renovar sobre la fecha de hoy.
            $base = $currentDue >= $today ? $currentDue : $today;
            $newDue = $this->addMonthsClamped($base, $months);
            $newDueStr = $newDue->format('Y-m-d');

            $update = $this->db->prepare(
                'UPDATE suscripciones
                 SET modalidad_id = :modalidad_id,
                     precio_venta = :precio_venta,
                     costo_base = :costo_base,
                     fecha_vencimiento = :fecha_vencimiento,
                     estado = :estado,
                     flag_no_renovo = 0,
                     ultimo_contacto_fecha = NULL,
                     ultimo_contacto_tipo = NULL
                 WHERE id = :id'
            );
            $update->execute([
                'id' => $id,
                'modalidad_id' => (int) $targetModalidad['id'],
                'precio_venta' => $nextPrecioVenta,
                'costo_base' => $nextCostoBase,
                'fecha_vencimiento' => $newDueStr,
                'estado' => 'ACTIVO',
            ]);

            $movement = $this->db->prepare(
                'INSERT INTO movimientos (suscripcion_id, plataforma_id, plataforma_nombre, tipo, meses, monto, costo, utilidad)
                 VALUES (:suscripcion_id, :plataforma_id, :plataforma_nombre, :tipo, :meses, :monto, :costo, :utilidad)'
            );
            $monto = (float) $nextPrecioVenta;
            $costo = (float) $nextCostoBase;
            $movement->execute([
                'suscripcion_id' => $id,
                'plataforma_id' => (int) $row['plataforma_id'],
                'plataforma_nombre' => (string) ($row['plataforma_nombre'] ?? ''),
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
        $message = $this->sanitizeWhatsAppMessage(
            $this->renderTemplate($template, $subscription)
        );
        $encodedMessage = rawurlencode($message);
        // Preservar marcadores de formato para que WhatsApp procese *negritas*, _cursivas_ y ~tachado~.
        $encodedMessage = str_replace(['%2A', '%5F', '%7E'], ['*', '_', '~'], $encodedMessage);

        return 'https://api.whatsapp.com/send/?phone=' . $phone . '&text=' . $encodedMessage . '&type=phone_number&app_absent=0';
    }

    public function inferContactType(array $subscription): string
    {
        $estado = (string) ($subscription['estado'] ?? '');
        if ($estado === 'CONTACTAR_2D') {
            return 'MENOS_2';
        }
        if ($estado === 'REENVIAR_1D') {
            return 'MENOS_1';
        }
        if ($estado === 'RECUP') {
            $dias = (int) ($subscription['dias_para_vencer'] ?? 0);

            return $dias <= -15 ? 'REC_15' : 'REC_7';
        }

        $dias = (int) ($subscription['dias_para_vencer'] ?? 0);
        if ($dias <= -15) {
            return 'REC_15';
        }
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
        if ((int) ($row['flag_no_renovo'] ?? 0) === 1) {
            return 'VENCIDO';
        }

        $today = new DateTimeImmutable('today');
        $dueDate = new DateTimeImmutable((string) $row['fecha_vencimiento']);
        $daysToDue = (int) $today->diff($dueDate)->format('%r%a');

        if ($daysToDue < 0) {
            if (abs($daysToDue) >= $recupDays) {
                return 'RECUP';
            }

            return 'VENCIDO';
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

    private function sanitizeWhatsAppMessage(string $message): string
    {
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        // Elimina solo caracteres inválidos o de control, manteniendo emojis/símbolos.
        $message = str_replace("\u{FFFD}", '', $message);
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $message) ?? $message;

        $message = preg_replace('/[ \t]+\n/u', "\n", $message) ?? $message;
        $message = preg_replace('/\n{3,}/u', "\n\n", $message) ?? $message;

        return trim($message);
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

    private function addMonthsClamped(DateTimeImmutable $baseDate, int $months): DateTimeImmutable
    {
        return \shift_months_clamped($baseDate, $months);
    }

    private function findMatchingRenewalModalidad(
        int $plataformaId,
        string $nombreModalidad,
        string $tipoCuenta,
        ?int $dispositivos,
        int $duracionMeses
    ): ?array {
        $stmt = $this->db->prepare(
            'SELECT id, precio, costo
             FROM modalidades
             WHERE plataforma_id = :plataforma_id
               AND nombre_modalidad = :nombre_modalidad
               AND tipo_cuenta = :tipo_cuenta
               AND dispositivos <=> :dispositivos
               AND duracion_meses = :duracion_meses
             LIMIT 1'
        );
        $stmt->execute([
            'plataforma_id' => $plataformaId,
            'nombre_modalidad' => $nombreModalidad,
            'tipo_cuenta' => $tipoCuenta,
            'dispositivos' => $dispositivos,
            'duracion_meses' => $duracionMeses,
        ]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function count(
        string $search = '',
        string $estado = '',
        string $searchField = 'TODOS',
        string $contacto = '',
        string $usuario = '',
        string $telefono = ''
    ): int {
        $sql = 'SELECT COUNT(*) AS total
            FROM suscripciones s
            INNER JOIN clientes c ON c.id = s.cliente_id
            INNER JOIN plataformas p ON p.id = s.plataforma_id
            INNER JOIN modalidades m ON m.id = s.modalidad_id';
        $conditions = [];
        $params = [];

        $searchField = strtoupper(trim($searchField));
        if (!in_array($searchField, self::FILTROS_BUSQUEDA, true)) {
            $searchField = 'TODOS';
        }

        $contacto = trim($contacto);
        $usuario = trim($usuario);
        $telefono = trim($telefono);

        if ($contacto !== '' || $usuario !== '' || $telefono !== '') {
            if ($contacto !== '') {
                $conditions[] = 'c.nombre LIKE :contacto_term';
                $params['contacto_term'] = '%' . $contacto . '%';
            }
            if ($usuario !== '') {
                $conditions[] = 's.usuario_proveedor LIKE :usuario_term';
                $params['usuario_term'] = '%' . $usuario . '%';
            }
            if ($telefono !== '') {
                $conditions[] = 'c.telefono LIKE :telefono_term';
                $params['telefono_term'] = '%' . $telefono . '%';
            }
        } elseif ($search !== '') {
            if ($searchField === 'CONTACTO') {
                $conditions[] = 'c.nombre LIKE :term';
            } elseif ($searchField === 'USUARIO') {
                $conditions[] = 's.usuario_proveedor LIKE :term';
            } elseif ($searchField === 'TELEFONO') {
                $conditions[] = 'c.telefono LIKE :term';
            } else {
                $conditions[] = '(c.nombre LIKE :term_contacto OR c.telefono LIKE :term_telefono OR s.usuario_proveedor LIKE :term_usuario OR p.nombre LIKE :term_plataforma OR m.nombre_modalidad LIKE :term_modalidad)';
                $like = '%' . $search . '%';
                $params['term_contacto'] = $like;
                $params['term_telefono'] = $like;
                $params['term_usuario'] = $like;
                $params['term_plataforma'] = $like;
                $params['term_modalidad'] = $like;
            }
            if ($searchField !== 'TODOS') {
                $params['term'] = '%' . $search . '%';
            }
        }

        if ($estado !== '' && in_array($estado, self::ESTADOS, true)) {
            $conditions[] = 's.estado = :estado';
            $params['estado'] = $estado;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    public function allByCliente(int $clienteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                s.*,
                p.nombre AS plataforma_nombre,
                p.tipo_servicio AS plataforma_tipo_servicio,
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
            INNER JOIN plataformas p ON p.id = s.plataforma_id
            INNER JOIN modalidades m ON m.id = s.modalidad_id
            WHERE s.cliente_id = :cliente_id
            ORDER BY s.fecha_vencimiento DESC, s.id DESC'
        );
        $stmt->execute(['cliente_id' => $clienteId]);

        return $stmt->fetchAll();
    }

    public function bulkMarkContacted(array $ids): int
    {
        $count = 0;
        foreach ($ids as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0) {
                continue;
            }

            $stmt = $this->db->prepare(
                'SELECT id, estado FROM suscripciones WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if (!$row) {
                continue;
            }

            $estado = (string) ($row['estado'] ?? '');
            $tipo = match ($estado) {
                'CONTACTAR_2D' => 'MENOS_2',
                'REENVIAR_1D' => 'MENOS_1',
                'VENCIDO', 'RECUP' => 'REC_7',
                default => 'MENOS_2',
            };

            if ($this->markWhatsappContact($id, $tipo)) {
                $count++;
            }
        }

        return $count;
    }
}
