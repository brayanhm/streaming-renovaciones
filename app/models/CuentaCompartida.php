<?php
declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

abstract class CuentaCompartida extends BaseModel
{
    public const CAPACIDAD = 0;
    public const COSTO_USD = null;
    public const TEMPLATE_KEYS = [];
    protected const SERVICIO = '';
    protected const PREFIJO = '';
    protected const ETIQUETA = '';
    protected const TIENE_GRACIA = false;
    protected const CONTAR_DISPOSITIVOS = false;

    public function all(string $search = ''): array
    {
        $sql = $this->accountSelect() . ' WHERE cp.servicio = :servicio';
        $params = ['servicio' => static::SERVICIO];
        if (trim($search) !== '') {
            $sql .= ' AND (cp.etiqueta LIKE :etiqueta OR cp.correo LIKE :correo)';
            $params += ['etiqueta' => '%' . trim($search) . '%', 'correo' => '%' . trim($search) . '%'];
        }
        $expiry = static::TIENE_GRACIA ? 'cp.fin_gracia' : 'cp.fecha_vencimiento';
        $sql .= ' ORDER BY distribucion_actual DESC, cp.activo DESC, ' . $expiry . ' IS NULL, ' . $expiry . ' ASC, cp.etiqueta ASC, cp.id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        // Cada grupo conserva el orden SQL por vencimiento, nombre e ID.
        // Se evalúa la disponibilidad real, incluidas las credenciales cifradas.
        $groups = [[], [], [], [], []];
        foreach ($stmt->fetchAll() as $row) {
            $account = $this->decorate($row);
            $priority = $account['distribucion_actual'] ? 0
                : ($account['lista_para_distribucion'] ? 1
                : ($account['motivo_bloqueo'] === '' ? 2 : ((int) $account['activo'] === 1 ? 3 : 4)));
            $groups[$priority][] = $account;
        }
        return array_merge(...$groups);
    }

    public function listing(string $search = '', string $filter = '', int $page = 1, int $perPage = 25): array
    {
        $filters = ['' => 'Todas', 'actual' => 'Actual', 'listas' => 'Listas para distribuir', 'gratis' => 'Gratis'];
        if (static::TIENE_GRACIA) $filters['gracia'] = 'En gracia';
        $filters += ['pendientes' => 'Pendientes', 'inactivas' => 'Inactivas'];
        if (!array_key_exists($filter, $filters)) $filter = '';
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
        $rows = $this->all($search);
        $position = 0;
        foreach ($rows as &$row) {
            if ($search === '' && $row['lista_para_distribucion']) $row['turno_distribucion'] = ++$position;
        }
        unset($row);
        $matches = static function (array $row, string $key): bool {
            switch ($key) {
                case 'actual': return (bool) $row['distribucion_actual'];
                case 'listas': return (bool) $row['lista_para_distribucion'];
                case 'gratis': return $row['estado_periodo'] === 'GRATIS';
                case 'gracia': return $row['estado_periodo'] === 'GRACIA';
                case 'pendientes': return (int) $row['activo'] === 1 && (!$row['credenciales_completas'] || $row['estado_periodo'] === 'SIN_PAGO' || !empty($row['revision_vigencia_pendiente']));
                case 'inactivas': return (int) $row['activo'] !== 1;
                default: return true;
            }
        };
        $counts = [];
        foreach ($filters as $key => $label) $counts[$key] = count(array_filter($rows, static fn (array $row): bool => $matches($row, $key)));
        $rows = array_values(array_filter($rows, static fn (array $row): bool => $matches($row, $filter)));
        $summary = ['cuentas' => count($rows), 'ocupados' => 0, 'libres' => 0, 'ventas_bs' => 0.0, 'pagos_bs' => 0.0];
        foreach ($rows as $row) foreach (['ocupados', 'libres', 'ventas_bs', 'pagos_bs'] as $key) $summary[$key] += (float) $row[$key];
        $totalRows = count($rows);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $page = min(max(1, $page), $totalPages);
        return ['rows' => array_slice($rows, ($page - 1) * $perPage, $perPage), 'summary' => $summary,
            'filter' => $filter, 'filters' => $filters, 'filterCounts' => $counts,
            'page' => $page, 'perPage' => $perPage, 'totalPages' => $totalPages, 'totalRows' => $totalRows];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare($this->accountSelect() . ' WHERE cp.id = :id AND cp.servicio = :servicio LIMIT 1');
        $stmt->execute(['id' => $id, 'servicio' => static::SERVICIO]);
        $row = $stmt->fetch();
        return $row ? $this->decorate($row) : null;
    }

    public function create(array $data): int
    {
        $fields = static::accountFields($data);
        $fields['capacidad'] = static::capacityValue($data['capacidad'] ?? static::CAPACIDAD);
        $dates = static::paymentDates((string) ($data['fecha_pago'] ?? ''));
        static::assertNotFuture($dates['fecha_pago']);
        $amount = static::money((string) ($data['monto_bs'] ?? ''));
        $password = static::encryptedPassword((string) ($data['password_cuenta'] ?? ''));

        return $this->transaction(function () use ($fields, $dates, $amount, $password): int {
            $platformId = $this->ensurePlatform();
            $this->assertUniqueEmail($fields['correo']);
            $stmt = $this->db->prepare("INSERT INTO cuentas_principales
                (plataforma_id, servicio, etiqueta, correo, password_cuenta, capacidad,
                 fecha_inicio, fecha_vencimiento, fin_gracia, activo, notas)
                VALUES (:plataforma_id, :servicio, :etiqueta, :correo, :password_cuenta, :capacidad,
                    :fecha_inicio, :fecha_vencimiento, :fin_gracia, 1, :notas)");
            $stmt->execute($fields + [
                'plataforma_id' => $platformId,
                'servicio' => static::SERVICIO,
                'password_cuenta' => $password,
                'fecha_inicio' => $dates['fecha_pago'],
                'fecha_vencimiento' => $dates['fin_pagado'],
                'fin_gracia' => $dates['fin_gracia'],
            ]);
            $id = (int) $this->db->lastInsertId();
            $this->insertPayment($id, $dates, $amount, bin2hex(random_bytes(32)));
            return $id;
        });
    }

    public function update(int $id, array $data): void
    {
        $fields = static::accountFields($data);
        $password = static::encryptedPassword((string) ($data['password_cuenta'] ?? ''));
        $capacity = array_key_exists('capacidad', $data) ? static::capacityValue($data['capacidad']) : null;
        $this->transaction(function () use ($id, $fields, $password, $capacity): void {
            $account = $this->lockAccount($id);
            $this->assertUniqueEmail($fields['correo'], $id);
            $occupied = $this->occupied($id);
            $sql = 'UPDATE cuentas_principales SET etiqueta = :etiqueta, correo = :correo, notas = :notas, capacidad = :capacidad';
            $params = $fields + ['id' => $id, 'capacidad' => $capacity ?? (int) $account['capacidad']];
            if ($password !== null) {
                $sql .= ', password_cuenta = :password_cuenta';
                $params['password_cuenta'] = $password;
            }
            $stmt = $this->db->prepare($sql . ' WHERE id = :id');
            $stmt->execute($params);
            $this->recordFirstCompletion($id, $occupied, $occupied, $params['capacidad'], (int) $account['capacidad']);
        });
    }

    public function registerPayment(int $id, string $date, string $montoBs, string $token): int
    {
        $dates = static::paymentDates($date);
        static::assertNotFuture($date);
        $amount = static::money($montoBs);
        static::assertToken($token);
        try {
            return $this->transaction(function () use ($id, $dates, $amount, $token): int {
                $account = $this->lockAccount($id);
                $existing = $this->operation('pagos', $token);
                if ($existing !== null) {
                    return static::samePayment($existing, $id, $dates['fecha_pago'], $amount);
                }
                return $this->registerPaymentLocked($account, $dates, $amount, $token);
            });
        } catch (Throwable $exception) {
            // También cubre dos solicitudes concurrentes con el mismo token.
            if ($exception instanceof \PDOException && $exception->getCode() === '23000') {
                $existing = $this->operation('pagos', $token);
                if ($existing !== null) {
                    return static::samePayment($existing, $id, $date, $amount);
                }
            }
            throw $exception;
        }
    }

    public function sell(int $id, int $dispositivos, string $date, string $token): int
    {
        $price = static::salePrice($dispositivos);
        static::date($date);
        static::assertNotFuture($date);
        static::assertToken($token);
        try {
            return $this->transaction(function () use ($id, $dispositivos, $date, $token, $price): int {
                $selected = $this->lockDistribution();
                $account = $this->lockAccount($id);
                $existing = $this->operation('ventas', $token);
                if ($existing !== null) {
                    return static::sameSale($existing, $id, $dispositivos, $date);
                }
                $this->assertReady($account);
                if ($selected !== $id) {
                    throw new RuntimeException('Solo se puede vender en la cuenta seleccionada para distribución.');
                }
                $baseOccupied = $this->occupied($id, false);
                $occupied = max(0, $baseOccupied + (int) $account['ajuste_ocupacion']);
                $end = static::TIENE_GRACIA ? $account['fin_gracia'] : $account['fecha_vencimiento'];
                $start = (string) ($account['fecha_inicio'] ?? '');
                if ($start === '' && static::TIENE_GRACIA) {
                    $start = (string) $account['fecha_vencimiento'];
                }
                if ($date < $start || $date >= $end) {
                    throw new InvalidArgumentException('La fecha de venta debe estar dentro de la vigencia actual de la cuenta.');
                }
                $expiry = !static::TIENE_GRACIA || $date < $account['fecha_vencimiento']
                    ? $account['fecha_vencimiento'] : $account['fin_gracia'];
                // Si se retiraron accesos heredados, el contador parte de cero antes de sumar la venta.
                if ($baseOccupied + (int) $account['ajuste_ocupacion'] < 0) {
                    $this->db->prepare('UPDATE cuentas_principales SET ajuste_ocupacion = :ajuste WHERE id = :id')
                        ->execute(['ajuste' => -$baseOccupied, 'id' => $id]);
                }
                $stmt = $this->db->prepare('INSERT INTO ' . $this->table('ventas') . '
                    (cuenta_principal_id, fecha_venta, dispositivos, monto_bs, fecha_vencimiento, token_operacion)
                    VALUES (:cuenta, :fecha, :dispositivos, :monto, :vencimiento, :token)');
                $stmt->execute([
                    'cuenta' => $id, 'fecha' => $date, 'dispositivos' => $dispositivos,
                    'monto' => $price, 'vencimiento' => $expiry, 'token' => $token,
                ]);
                $saleId = (int) $this->db->lastInsertId();
                $this->recordFirstCompletion($id, $occupied, $occupied + $dispositivos, (int) $account['capacidad']);
                return $saleId;
            });
        } catch (Throwable $exception) {
            if ($exception instanceof \PDOException && $exception->getCode() === '23000') {
                $existing = $this->operation('ventas', $token);
                if ($existing !== null) {
                    return static::sameSale($existing, $id, $dispositivos, $date);
                }
            }
            throw $exception;
        }
    }

    protected function releaseSlots(int $id, string $motivo, string $usuario): int
    {
        $motivo = trim($motivo) ?: 'Liberación manual de cupos';
        if (static::length($motivo) > 255 || static::length($usuario) > 60) {
            throw new InvalidArgumentException('El motivo o el usuario excede la longitud permitida.');
        }
        // Se ejecuta dentro de la transacción del nuevo ciclo, con la cuenta bloqueada.
        $occupied = $this->occupied($id);
        $stmt = $this->db->prepare('UPDATE ' . $this->table('ventas') . ' SET liberada_at = NOW()
            WHERE cuenta_principal_id = :id AND liberada_at IS NULL');
        $stmt->execute(['id' => $id]);
        $stmt = $this->db->prepare("UPDATE suscripciones SET flag_no_renovo = 1, estado = 'VENCIDO'
            WHERE cuenta_principal_id = :id AND flag_no_renovo = 0");
        $stmt->execute(['id' => $id]);
        $stmt = $this->db->prepare('UPDATE cuentas_principales SET ajuste_ocupacion = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $stmt = $this->db->prepare('INSERT INTO ' . $this->table('liberaciones') . '
            (cuenta_principal_id, dispositivos_liberados, motivo, usuario)
            VALUES (:id, :ocupados, :motivo, :usuario)');
        $stmt->execute(['id' => $id, 'ocupados' => $occupied, 'motivo' => $motivo, 'usuario' => trim($usuario) ?: null]);
        return $occupied;
    }

    public function adjustOccupation(int $id, mixed $value, mixed $expected, string $motivo, string $usuario, string $token): array
    {
        $target = static::occupationValue($value);
        $expected = static::occupationValue($expected, PHP_INT_MAX);
        $motivo = trim($motivo) ?: 'Ajuste manual de ocupación';
        $usuario = trim($usuario);
        if (static::length($motivo) > 255 || static::length($usuario) > 60) {
            throw new InvalidArgumentException('El motivo o el usuario excede la longitud permitida.');
        }
        static::assertToken($token);
        return $this->transaction(function () use ($id, $target, $expected, $motivo, $usuario, $token): array {
            // Mismo orden de bloqueo que las ventas y los ciclos.
            $this->lockDistribution();
            $account = $this->lockAccount($id);
            $stmt = $this->db->prepare('SELECT * FROM cuentas_ajustes_ocupacion WHERE token_operacion = :token FOR UPDATE');
            $stmt->execute(['token' => $token]);
            $existing = $stmt->fetch();
            if ($existing) {
                if ((int) $existing['cuenta_principal_id'] !== $id || (int) $existing['ocupados_despues'] !== $target
                    || (int) $existing['ocupados_antes'] !== $expected || $existing['motivo'] !== $motivo) {
                    throw new InvalidArgumentException('Este comprobante ya se usó con otros datos. Recarga la cuenta.');
                }
                return $existing;
            }
            $baseOccupied = $this->occupied($id, false);
            $current = max(0, $baseOccupied + (int) $account['ajuste_ocupacion']);
            if ($current !== $expected) {
                throw new RuntimeException('La ocupación cambió mientras editabas. Recarga la cuenta y revisa el contador antes de ajustar.');
            }
            $stmt = $this->db->prepare('UPDATE cuentas_principales SET ajuste_ocupacion = :ajuste WHERE id = :id');
            $stmt->execute(['id' => $id, 'ajuste' => $target - $baseOccupied]);
            $stmt = $this->db->prepare('INSERT INTO cuentas_ajustes_ocupacion
                (cuenta_principal_id, ocupados_antes, ocupados_despues, motivo, usuario, token_operacion)
                VALUES (:id, :antes, :despues, :motivo, :usuario, :token)');
            $stmt->execute(['id' => $id, 'antes' => $current, 'despues' => $target,
                'motivo' => $motivo, 'usuario' => $usuario ?: null, 'token' => $token]);
            $adjustmentId = (int) $this->db->lastInsertId();
            $this->recordFirstCompletion($id, $current, $target, (int) $account['capacidad']);
            return ['id' => $adjustmentId, 'ocupados_antes' => $current, 'ocupados_despues' => $target];
        });
    }

    public function occupationAdjustments(int $id): array
    {
        $stmt = $this->db->prepare('SELECT a.* FROM cuentas_ajustes_ocupacion a
            INNER JOIN cuentas_principales cp ON cp.id = a.cuenta_principal_id
            WHERE a.cuenta_principal_id = :id AND cp.servicio = :servicio ORDER BY a.id DESC LIMIT 10');
        $stmt->execute(['id' => $id, 'servicio' => static::SERVICIO]);
        return $stmt->fetchAll();
    }

    public function selectDistribution(int $id): void
    {
        $this->transaction(function () use ($id): void {
            $selected = $this->lockDistribution();
            $this->assertReady($this->lockAccount($id));
            $this->setDistribution($id);
            if ($selected !== $id) {
                $this->ensureDistributionHistory($id, $this->currentCycleId($id), date('Y-m-d H:i:s'));
            }
        });
    }

    public function payAndSelectDistribution(int $id, string $date, string $montoBs, string $token): int
    {
        $dates = static::paymentDates($date);
        if ($date !== date('Y-m-d')) {
            throw new InvalidArgumentException('El pago para distribuir una cuenta Gratis debe registrarse con la fecha de hoy.');
        }
        $amount = static::money($montoBs);
        static::assertToken($token);
        return $this->transaction(function () use ($id, $dates, $amount, $token): int {
            $this->lockDistribution();
            $account = $this->lockAccount($id);
            $existing = $this->operation('pagos', $token);
            if ($existing !== null) {
                // Un reintento nunca vuelve a seleccionar una cuenta ya reemplazada.
                return static::samePayment($existing, $id, $dates['fecha_pago'], $amount);
            }
            if (static::period($account)['estado_periodo'] !== 'GRATIS') {
                throw new RuntimeException('La cuenta ya no está Gratis. Recarga el listado antes de seleccionarla para evitar otro pago.');
            }
            $this->assertReady(array_replace($account, [
                'fecha_inicio' => $dates['fecha_pago'], 'fecha_vencimiento' => $dates['fin_pagado'],
                'fin_gracia' => $dates['fin_gracia'], 'plan_gratis' => 0, 'revision_vigencia_pendiente' => 0,
            ]));
            $paymentId = $this->registerPaymentLocked($account, $dates, $amount, $token);
            $this->setDistribution($id);
            $this->ensureDistributionHistory($id, $this->currentCycleId($id), date('Y-m-d H:i:s'));
            return $paymentId;
        });
    }

    public function currentCycleId(int $id): int
    {
        $stmt = $this->db->prepare('SELECT c.id FROM cuentas_ciclos c
            INNER JOIN cuentas_principales cp ON cp.id = c.cuenta_principal_id
            WHERE c.cuenta_principal_id = :id AND cp.servicio = :servicio ORDER BY c.id DESC LIMIT 1');
        $stmt->execute(['id' => $id, 'servicio' => static::SERVICIO]);
        return (int) $stmt->fetchColumn();
    }

    public function startCycle(int $id, bool $sessionsClosed, string $usuario, string $token, int $expectedCycle): int
    {
        if (!$sessionsClosed) {
            throw new InvalidArgumentException('Confirma que cerraste las sesiones en todos los dispositivos antes de iniciar el nuevo ciclo.');
        }
        static::assertToken($token);
        return $this->transaction(function () use ($id, $usuario, $token, $expectedCycle): int {
            $this->lockDistribution();
            $account = $this->lockAccount($id);
            $stmt = $this->db->prepare('SELECT * FROM cuentas_ciclos WHERE token_operacion = :token FOR UPDATE');
            $stmt->execute(['token' => $token]);
            $existing = $stmt->fetch();
            if ($existing) {
                if ((int) $existing['cuenta_principal_id'] !== $id) {
                    throw new InvalidArgumentException('Este comprobante ya se usó para otra cuenta. Recarga el formulario.');
                }
                return (int) $existing['cupos_liberados'];
            }
            if ($expectedCycle < 0 || $expectedCycle !== $this->currentCycleId($id)) {
                throw new RuntimeException('Ya se inició otro ciclo desde que abriste este formulario. Recarga la cuenta antes de continuar.');
            }
            $this->assertReady($account);
            $released = $this->releaseSlots($id, 'Nuevo ciclo: cierre de sesiones confirmado', $usuario);
            $stmt = $this->db->prepare('INSERT INTO cuentas_ciclos
                (cuenta_principal_id, cupos_liberados, usuario, token_operacion) VALUES (:id, :cupos, :usuario, :token)');
            $stmt->execute(['id' => $id, 'cupos' => $released, 'usuario' => trim($usuario) ?: null, 'token' => $token]);
            $cycleId = (int) $this->db->lastInsertId();
            $this->ensureDistributionHistory($id, $cycleId, date('Y-m-d H:i:s'));
            $this->setDistribution($id);
            return $released;
        });
    }

    public function setFreePlan(int $id, bool $free): void
    {
        $this->transaction(function () use ($id, $free): void {
            $account = $this->lockAccount($id);
            if (!$free && !in_array(static::period(array_replace($account, ['plan_gratis' => 0]))['estado_periodo'], ['PAGADO', 'GRACIA'], true)) {
                throw new RuntimeException('La vigencia terminó o está pendiente. Registra un nuevo pago antes de restaurar el plan.');
            }
            $stmt = $this->db->prepare('UPDATE cuentas_principales SET plan_gratis = :gratis WHERE id = :id');
            $stmt->execute(['gratis' => (int) $free, 'id' => $id]);
        });
    }

    public function currentDistribution(): ?array
    {
        $stmt = $this->db->prepare('SELECT cuenta_principal_id FROM cuentas_distribucion WHERE servicio = :servicio');
        $stmt->execute(['servicio' => static::SERVICIO]);
        $id = (int) $stmt->fetchColumn();
        return $id > 0 ? $this->find($id) : null;
    }

    public function cycles(int $id): array
    {
        $stmt = $this->db->prepare('SELECT c.* FROM cuentas_ciclos c INNER JOIN cuentas_principales cp ON cp.id = c.cuenta_principal_id
            WHERE c.cuenta_principal_id = :id AND cp.servicio = :servicio ORDER BY c.id DESC');
        $stmt->execute(['id' => $id, 'servicio' => static::SERVICIO]);
        return $stmt->fetchAll();
    }

    public function distributionHistory(int $id): array
    {
        $stmt = $this->db->prepare('SELECT h.*,
            1 + (SELECT COUNT(*) FROM cuentas_ciclos c
                WHERE c.cuenta_principal_id = h.cuenta_principal_id AND c.id <= h.ciclo_id) AS numero_ciclo
            FROM cuentas_distribucion_historial h
            INNER JOIN cuentas_principales cp ON cp.id = h.cuenta_principal_id
            WHERE h.cuenta_principal_id = :id AND cp.servicio = :servicio
            ORDER BY h.ciclo_id DESC');
        $stmt->execute(['id' => $id, 'servicio' => static::SERVICIO]);
        return $stmt->fetchAll();
    }

    /** La cuenta debe estar bloqueada; volver a seleccionarla conserva su primer inicio. */
    protected function ensureDistributionHistory(int $id, int $cycleId, ?string $start = null): void
    {
        $stmt = $this->db->prepare('INSERT INTO cuentas_distribucion_historial
            (cuenta_principal_id, ciclo_id, inicio_distribucion) VALUES (:id, :ciclo, :inicio)
            ON DUPLICATE KEY UPDATE inicio_distribucion = COALESCE(inicio_distribucion, VALUES(inicio_distribucion))');
        $stmt->execute(['id' => $id, 'ciclo' => $cycleId, 'inicio' => $start]);
    }

    /** Solo una transición nueva a llena registra el primer llenado del ciclo. */
    protected function recordFirstCompletion(int $id, int $before, int $after, int $capacity, ?int $previousCapacity = null): void
    {
        if ($before >= ($previousCapacity ?? $capacity) || $after < $capacity) {
            return;
        }
        $cycleId = $this->currentCycleId($id);
        $this->ensureDistributionHistory($id, $cycleId);
        $stmt = $this->db->prepare('UPDATE cuentas_distribucion_historial
            SET cupos_completados_at = :fecha, capacidad_al_completar = :capacidad, ocupados_al_completar = :ocupados
            WHERE cuenta_principal_id = :id AND ciclo_id = :ciclo AND cupos_completados_at IS NULL');
        $stmt->execute(['fecha' => date('Y-m-d H:i:s'), 'capacidad' => $capacity, 'ocupados' => $after,
            'id' => $id, 'ciclo' => $cycleId]);
    }

    protected function lockDistribution(): int
    {
        // Todas las selecciones, ventas y reinicios toman este bloqueo primero.
        $stmt = $this->db->prepare('SELECT cuenta_principal_id FROM cuentas_distribucion WHERE servicio = :servicio FOR UPDATE');
        $stmt->execute(['servicio' => static::SERVICIO]);
        return (int) $stmt->fetchColumn();
    }

    protected function setDistribution(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE cuentas_distribucion SET cuenta_principal_id = :id WHERE servicio = :servicio');
        $stmt->execute(['id' => $id, 'servicio' => static::SERVICIO]);
    }

    protected function assertReady(array $account): void
    {
        if (trim(\decrypt_secret((string) ($account['password_cuenta'] ?? ''))) === ''
            || !filter_var(trim((string) ($account['correo'] ?? '')), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Completa el correo y la contraseña de la cuenta antes de distribuirla.');
        }
        if ((int) $account['activo'] !== 1) {
            throw new RuntimeException('La cuenta está desactivada. Actívala antes de distribuirla.');
        }
        if (!in_array(static::period($account)['estado_periodo'], ['PAGADO', 'GRACIA'], true)) {
            throw new RuntimeException('La cuenta está Gratis o sin vigencia confirmada. Revisa su plan o registra un pago antes de distribuirla.');
        }
    }

    public function setActive(int $id, bool $active): void
    {
        $this->transaction(function () use ($id, $active): void {
            $this->lockAccount($id);
            $stmt = $this->db->prepare('UPDATE cuentas_principales SET activo = :activo WHERE id = :id');
            $stmt->execute(['activo' => (int) $active, 'id' => $id]);
        });
    }

    public static function deliveryUnavailableReason(array $account, array $sale): ?string
    {
        if ((int) ($sale['cuenta_principal_id'] ?? 0) !== (int) ($account['id'] ?? 0)) return 'La entrega no corresponde a esta cuenta.';
        if (!empty($sale['liberada_at'])) return 'Entrega de un ciclo anterior.';
        $expiry = \parse_ymd_date((string) ($sale['fecha_vencimiento'] ?? ''));
        if ($expiry === null || $expiry <= new DateTimeImmutable('today')) return 'El acceso de esta venta ya venció.';
        if ((int) ($account['activo'] ?? 0) !== 1) return 'La cuenta está desactivada.';
        if (!in_array(static::period($account)['estado_periodo'], ['PAGADO', 'GRACIA'], true)) return 'La cuenta está Gratis o sin vigencia confirmada.';
        return null;
    }

    public function payments(int $id): array
    {
        $stmt = $this->db->prepare('SELECT p.* FROM ' . $this->table('pagos') . ' p
            INNER JOIN cuentas_principales cp ON cp.id = p.cuenta_principal_id AND cp.servicio = :servicio
            WHERE p.cuenta_principal_id = :id ORDER BY p.fecha_pago DESC, p.id DESC');
        $stmt->execute(['id' => $id, 'servicio' => static::SERVICIO]);
        return $stmt->fetchAll();
    }

    public function sales(int $id): array
    {
        $stmt = $this->db->prepare('SELECT v.* FROM ' . $this->table('ventas') . ' v
            INNER JOIN cuentas_principales cp ON cp.id = v.cuenta_principal_id AND cp.servicio = :servicio
            WHERE v.cuenta_principal_id = :id ORDER BY v.fecha_venta DESC, v.id DESC');
        $stmt->execute(['id' => $id, 'servicio' => static::SERVICIO]);
        return $stmt->fetchAll();
    }

    public function sale(int $accountId, int $saleId): ?array
    {
        $stmt = $this->db->prepare('SELECT v.* FROM ' . $this->table('ventas') . ' v
            INNER JOIN cuentas_principales cp ON cp.id = v.cuenta_principal_id AND cp.servicio = :servicio
            WHERE v.cuenta_principal_id = :cuenta AND v.id = :venta LIMIT 1');
        $stmt->execute(['cuenta' => $accountId, 'venta' => $saleId, 'servicio' => static::SERVICIO]);
        return $stmt->fetch() ?: null;
    }

    public function deliveryTemplates(): array
    {
        $templates = $this->templates();
        $defaults = require APP_PATH . '/config/' . static::PREFIJO . '_templates.php';
        $plainText = static fn(string $value): string => str_replace(["\r\n", "\r"], "\n", trim(str_replace('*', '', $value)));
        foreach ($defaults as $key => $text) {
            $stored = (string) ($templates[$key] ?? '');
            // Actualizar el formato de las entregas estándar ya instaladas, conservando textos personalizados.
            if (trim($stored) === '' || $plainText($stored) === $plainText($text)) {
                $templates[$key] = $text;
            }
        }
        return $templates;
    }

    public function templates(): array
    {
        $templates = array_fill_keys(static::TEMPLATE_KEYS, '');
        foreach ($this->db->query('SELECT clave, contenido FROM ' . $this->table('plantillas'))->fetchAll() as $row) {
            if (array_key_exists($row['clave'], $templates)) {
                $templates[$row['clave']] = (string) $row['contenido'];
            }
        }
        return $templates;
    }

    public function saveTemplates(array $data): void
    {
        $templates = [];
        foreach (static::TEMPLATE_KEYS as $key) {
            $templates[$key] = trim((string) ($data[$key] ?? ''));
            if (strlen($templates[$key]) > 65535) {
                throw new InvalidArgumentException('La plantilla es demasiado larga.');
            }
        }
        $this->transaction(function () use ($templates): void {
            $stmt = $this->db->prepare('INSERT INTO ' . $this->table('plantillas') . ' (clave, contenido)
                VALUES (:clave, :contenido) ON DUPLICATE KEY UPDATE contenido = VALUES(contenido)');
            foreach ($templates as $key => $content) {
                $stmt->execute(['clave' => $key, 'contenido' => $content]);
            }
        });
    }

    public static function paymentDates(string $date): array
    {
        $start = static::date($date);
        $paid = \shift_months_clamped($start, 1);
        $grace = static::TIENE_GRACIA ? \shift_months_clamped($paid, 1) : null;
        if (strlen($paid->format('Y-m-d')) !== 10 || ($grace !== null && strlen($grace->format('Y-m-d')) !== 10)) {
            throw new InvalidArgumentException('La fecha de pago está fuera del rango permitido.');
        }
        return ['fecha_pago' => $start->format('Y-m-d'), 'fin_pagado' => $paid->format('Y-m-d'), 'fin_gracia' => $grace?->format('Y-m-d')];
    }

    public static function period(array $account, ?DateTimeImmutable $today = null): array
    {
        $manualFree = (int) ($account['plan_gratis'] ?? 0) === 1;
        $unknown = ['estado_periodo' => $manualFree ? 'GRATIS' : 'SIN_PAGO',
            'estado_vigencia' => 'SIN_PAGO', 'origen_gratis' => $manualFree ? 'MANUAL' : null,
            'dias_pagado' => null, 'dias_gracia' => null];
        if ((int) ($account['revision_vigencia_pendiente'] ?? 0) === 1) {
            return $unknown;
        }
        $today = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);
        $start = (string) ($account['fecha_inicio'] ?? '');
        $paid = (string) ($account['fecha_vencimiento'] ?? '');
        $grace = (string) ($account['fin_gracia'] ?? '');
        if (($start === '' && !static::TIENE_GRACIA) || $paid === '' || (static::TIENE_GRACIA && $grace === '')) {
            return $unknown;
        }
        try {
            $startDate = $start !== '' ? static::date($start) : null;
            $paidDate = static::date($paid);
            $graceDate = static::TIENE_GRACIA ? static::date($grace) : null;
        } catch (InvalidArgumentException $exception) {
            return $unknown;
        }
        $state = $startDate === null
            ? ($today < $paidDate ? 'SIN_PAGO' : ($today < $graceDate ? 'GRACIA' : 'GRATIS'))
            : ($today < $startDate ? 'SIN_PAGO'
                : ($today < $paidDate ? 'PAGADO' : ($graceDate !== null && $today < $graceDate ? 'GRACIA' : 'GRATIS')));
        return [
            'estado_periodo' => $manualFree ? 'GRATIS' : $state,
            'estado_vigencia' => $state,
            'origen_gratis' => $manualFree ? 'MANUAL' : ($state === 'GRATIS' ? 'VENCIMIENTO' : null),
            'dias_pagado' => $startDate !== null ? (int) $today->diff($paidDate)->format('%r%a') : null,
            'dias_gracia' => $graceDate !== null ? (int) $today->diff($graceDate)->format('%r%a') : null,
        ];
    }

    abstract public static function salePrice(int $devices): string;

    public static function renderMessage(string $template, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $replace['{' . strtoupper(trim((string) $key, '{} ')) . '}'] = (string) $value;
            }
        }
        return strtr($template, $replace);
    }

    protected function accountSelect(): string
    {
        $payments = $this->table('pagos');
        $sales = $this->table('ventas');
        $legacy = static::legacyOccupation();
        return "SELECT cp.*, p.nombre AS plataforma_nombre,
            COALESCE((SELECT MAX(c.id) FROM cuentas_ciclos c WHERE c.cuenta_principal_id = cp.id), 0) AS ciclo_actual,
            dh.inicio_distribucion, dh.cupos_completados_at, dh.capacidad_al_completar, dh.ocupados_al_completar,
            COALESCE(d.cuenta_principal_id = cp.id, 0) AS distribucion_actual,
            COALESCE(v.ocupados, 0) AS ocupados_ventas,
            COALESCE(v.ventas_bs, 0) AS ventas_bs,
            COALESCE(l.ocupados, 0) AS ocupados_anteriores,
            COALESCE(pg.pagos_bs, 0) AS pagos_bs,
            (SELECT pago.monto_bs FROM {$payments} pago WHERE pago.cuenta_principal_id = cp.id
                ORDER BY pago.fecha_pago DESC, pago.id DESC LIMIT 1) AS ultimo_costo_bs
            FROM cuentas_principales cp
            INNER JOIN plataformas p ON p.id = cp.plataforma_id
            LEFT JOIN cuentas_distribucion d ON d.servicio = cp.servicio
            LEFT JOIN cuentas_distribucion_historial dh ON dh.cuenta_principal_id = cp.id
                AND dh.ciclo_id = COALESCE((SELECT MAX(c.id) FROM cuentas_ciclos c WHERE c.cuenta_principal_id = cp.id), 0)
            LEFT JOIN (SELECT cuenta_principal_id,
                SUM(CASE WHEN liberada_at IS NULL THEN dispositivos ELSE 0 END) AS ocupados,
                SUM(monto_bs) AS ventas_bs FROM {$sales} GROUP BY cuenta_principal_id) v
                ON v.cuenta_principal_id = cp.id
            LEFT JOIN (SELECT s.cuenta_principal_id,
                {$legacy} AS ocupados
                FROM suscripciones s LEFT JOIN modalidades m ON m.id = s.modalidad_id
                WHERE s.flag_no_renovo = 0 GROUP BY s.cuenta_principal_id) l
                ON l.cuenta_principal_id = cp.id
            LEFT JOIN (SELECT cuenta_principal_id, SUM(monto_bs) AS pagos_bs
                FROM {$payments} GROUP BY cuenta_principal_id) pg ON pg.cuenta_principal_id = cp.id";
    }

    protected function decorate(array $row): array
    {
        $row['capacidad'] = (int) $row['capacidad'];
        $row['ciclo_actual'] = (int) $row['ciclo_actual'];
        $row['ocupados_ventas'] = (int) $row['ocupados_ventas'];
        $row['ocupados_anteriores'] = (int) $row['ocupados_anteriores'];
        $row['ajuste_ocupacion'] = (int) ($row['ajuste_ocupacion'] ?? 0);
        $row['ocupados'] = max(0, $row['ocupados_ventas'] + $row['ocupados_anteriores'] + $row['ajuste_ocupacion']);
        $row['recomendado'] = $row['capacidad'];
        $row['excedidos'] = max($row['ocupados'] - $row['recomendado'], 0);
        $row['supera_recomendado'] = $row['excedidos'] > 0;
        $row['hasta_recomendado'] = max($row['recomendado'] - $row['ocupados'], 0);
        $row['libres'] = $row['hasta_recomendado'];
        $row['distribucion_actual'] = (bool) ($row['distribucion_actual'] ?? false);
        $row['llena'] = $row['ocupados'] >= $row['capacidad'];
        $row += static::period($row);
        $row['credenciales_completas'] = filter_var((string) ($row['correo'] ?? ''), FILTER_VALIDATE_EMAIL) !== false
            && trim(\decrypt_secret((string) ($row['password_cuenta'] ?? ''))) !== '';
        $row['motivo_bloqueo'] = (int) $row['activo'] !== 1 ? 'Cuenta inactiva.'
            : (!in_array($row['estado_periodo'], ['PAGADO', 'GRACIA'], true) ? ((int) ($row['plan_gratis'] ?? 0) === 1 ? 'Plan marcado Gratis.' : ($row['estado_periodo'] === 'GRATIS' ? 'Vigencia finalizada.' : 'Vigencia pendiente de revisión.'))
            : (!$row['credenciales_completas'] ? 'Credenciales pendientes de completar.' : ''));
        $row['puede_pagar_distribucion'] = (int) $row['activo'] === 1 && $row['credenciales_completas'] && $row['estado_periodo'] === 'GRATIS';
        $row['ventas_habilitadas'] = $row['motivo_bloqueo'] === '' && $row['distribucion_actual'];
        $row['lista_para_distribucion'] = !$row['distribucion_actual'] && !$row['llena'] && $row['motivo_bloqueo'] === '';
        $row['estado_distribucion'] = $row['distribucion_actual'] ? 'En distribución'
            : ($row['llena'] ? 'Vendida' : ($row['motivo_bloqueo'] !== '' ? 'Ventas bloqueadas' : 'En reserva'));
        return $row;
    }

    protected static function capacityValue(mixed $value): int
    {
        if (!is_scalar($value) || !preg_match('/^[1-9][0-9]{0,4}$/D', (string) $value)) {
            throw new \App\Core\ValidationException(['capacidad' => 'La capacidad debe ser un número entero entre 1 y 99999.']);
        }
        return (int) $value;
    }

    protected function occupied(int $id, bool $withAdjustment = true): int
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(dispositivos), 0) FROM ' . $this->table('ventas') . '
            WHERE cuenta_principal_id = :id AND liberada_at IS NULL');
        $stmt->execute(['id' => $id]);
        $sales = (int) $stmt->fetchColumn();
        $stmt = $this->db->prepare('SELECT COALESCE(' . static::legacyOccupation() . ', 0)
            FROM suscripciones s LEFT JOIN modalidades m ON m.id = s.modalidad_id
            WHERE s.cuenta_principal_id = :id AND s.flag_no_renovo = 0');
        $stmt->execute(['id' => $id]);
        $legacy = (int) $stmt->fetchColumn();
        if (!$withAdjustment) return $sales + $legacy;
        $stmt = $this->db->prepare('SELECT ajuste_ocupacion FROM cuentas_principales WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return max(0, $sales + $legacy + (int) $stmt->fetchColumn());
    }

    protected static function occupationValue(mixed $value, int $maximum = 99999): int
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^(0|[1-9][0-9]{0,9})$/D', (string) $value)
            || (float) $value > $maximum) {
            throw new InvalidArgumentException('Los ocupados deben ser un número entero entre 0 y ' . $maximum . '.');
        }
        return (int) $value;
    }

    protected function ensurePlatform(): int
    {
        $stmt = $this->db->prepare('SELECT id FROM plataformas
            WHERE LOWER(nombre) LIKE :coincidencia
            ORDER BY LOWER(TRIM(nombre)) = :nombre DESC, id ASC LIMIT 1 FOR UPDATE');
        $stmt->execute(['coincidencia' => '%' . static::PREFIJO . '%', 'nombre' => static::PREFIJO]);
        $platform = $stmt->fetch();
        if ($platform) {
            $id = (int) $platform['id'];
            $stmt = $this->db->prepare('UPDATE plataformas SET usa_cuentas_principales = 1 WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return $id;
        }
        $stmt = $this->db->prepare("INSERT INTO plataformas
            (nombre, tipo_servicio, duraciones_disponibles, dato_renovacion, usa_cuentas_principales)
            VALUES (:nombre, 'RENOVABLE', '1', 'CORREO', 1)");
        $stmt->execute(['nombre' => static::ETIQUETA]);
        return (int) $this->db->lastInsertId();
    }

    protected function lockAccount(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM cuentas_principales
            WHERE id = :id AND servicio = :servicio LIMIT 1 FOR UPDATE");
        $stmt->execute(['id' => $id, 'servicio' => static::SERVICIO]);
        $account = $stmt->fetch();
        if (!$account) {
            throw new RuntimeException('No se encontró la cuenta de ' . static::ETIQUETA . '.');
        }
        return $account;
    }

    protected function assertUniqueEmail(string $email, ?int $except = null): void
    {
        $sql = 'SELECT id FROM cuentas_principales WHERE servicio = :servicio AND correo = :correo';
        $params = ['correo' => $email, 'servicio' => static::SERVICIO];
        if ($except !== null) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $except;
        }
        $stmt = $this->db->prepare($sql . ' LIMIT 1 FOR UPDATE');
        $stmt->execute($params);
        if ($stmt->fetch()) {
            throw new \App\Core\ValidationException(['correo' => 'Ya existe una cuenta de ' . static::ETIQUETA . ' con ese correo.']);
        }
    }

    protected function operation(string $table, string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ' . $this->table($table) . ' WHERE token_operacion = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() ?: null;
    }

    protected function insertPayment(int $id, array $dates, string $amount, string $token): int
    {
        $stmt = $this->db->prepare('INSERT INTO ' . $this->table('pagos') . '
            (cuenta_principal_id, fecha_pago, fin_pagado, fin_gracia, monto_usd, monto_bs, token_operacion)
            VALUES (:id, :fecha_pago, :fin_pagado, :fin_gracia, :monto_usd, :monto_bs, :token)');
        $stmt->execute($dates + ['id' => $id, 'monto_usd' => static::COSTO_USD, 'monto_bs' => $amount, 'token' => $token]);
        return (int) $this->db->lastInsertId();
    }

    /** La cuenta ya está bloqueada y se comprobó que el token no corresponde a un pago existente. */
    protected function registerPaymentLocked(array $account, array $dates, string $amount, string $token): int
    {
        $id = (int) $account['id'];
        $stmt = $this->db->prepare('SELECT fecha_pago FROM ' . $this->table('pagos') . '
            WHERE cuenta_principal_id = :id ORDER BY fecha_pago DESC, id DESC LIMIT 1');
        $stmt->execute(['id' => $id]);
        $lastDate = $stmt->fetchColumn() ?: ($account['fecha_inicio'] ?? null);
        if (!$lastDate && static::TIENE_GRACIA) $lastDate = $account['fecha_vencimiento'] ?? null;
        if ($lastDate && $dates['fecha_pago'] < $lastDate) {
            throw new InvalidArgumentException('La fecha de pago no puede ser anterior al último pago de esta cuenta.');
        }
        $paymentId = $this->insertPayment($id, $dates, $amount, $token);
        $stmt = $this->db->prepare('UPDATE cuentas_principales SET fecha_inicio = :fecha_inicio,
            fecha_vencimiento = :fecha_vencimiento, fin_gracia = :fin_gracia,
            revision_vigencia_pendiente = 0, plan_gratis = 0 WHERE id = :id');
        $stmt->execute(['id' => $id, 'fecha_inicio' => $dates['fecha_pago'],
            'fecha_vencimiento' => $dates['fin_pagado'], 'fin_gracia' => $dates['fin_gracia']]);
        return $paymentId;
    }

    protected function table(string $suffix): string
    {
        // Ambos componentes provienen de valores internos, nunca de un formulario.
        if (!in_array(static::PREFIJO, ['chatgpt', 'claude'], true)
            || !in_array($suffix, ['pagos', 'ventas', 'liberaciones', 'plantillas'], true)) {
            throw new RuntimeException('Tabla de cuentas compartidas no válida.');
        }
        return static::PREFIJO . '_' . $suffix;
    }

    protected static function legacyOccupation(): string
    {
        return static::CONTAR_DISPOSITIVOS ? 'SUM(GREATEST(COALESCE(m.dispositivos, 1), 1))' : 'COUNT(*)';
    }

    protected static function samePayment(array $row, int $id, string $date, string $amount): int
    {
        if ((int) $row['cuenta_principal_id'] !== $id || $row['fecha_pago'] !== $date || (string) $row['monto_bs'] !== $amount) {
            throw new InvalidArgumentException('Ese comprobante de operación ya fue usado con otros datos. Recarga el formulario.');
        }
        return (int) $row['id'];
    }

    protected static function sameSale(array $row, int $id, int $devices, string $date): int
    {
        if ((int) $row['cuenta_principal_id'] !== $id || (int) $row['dispositivos'] !== $devices || $row['fecha_venta'] !== $date) {
            throw new InvalidArgumentException('Ese comprobante de operación ya fue usado con otros datos. Recarga el formulario.');
        }
        return (int) $row['id'];
    }

    protected function transaction(callable $work): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $work();
            $this->db->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    protected static function date(string $value): DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            throw new InvalidArgumentException('Ingresa una fecha válida con formato año-mes-día.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value || (int) $date->format('Y') < 1000) {
            throw new InvalidArgumentException('Ingresa una fecha válida con formato año-mes-día.');
        }
        return $date;
    }

    protected static function assertNotFuture(string $date): void
    {
        if ($date > (new DateTimeImmutable('today'))->format('Y-m-d')) {
            throw new InvalidArgumentException('La fecha no puede ser posterior a hoy.');
        }
    }

    protected static function money(string $value): string
    {
        $value = str_replace(',', '.', trim($value));
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $value)) {
            throw new \App\Core\ValidationException(['monto_bs' => 'Ingresa el costo en bolivianos con un máximo de dos decimales.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $normalized = (string) (int) $whole . '.' . str_pad($fraction, 2, '0');
        if ($normalized === '0.00') {
            throw new \App\Core\ValidationException(['monto_bs' => 'El costo en bolivianos debe ser mayor que cero.']);
        }
        return $normalized;
    }

    protected static function assertToken(string $token): void
    {
        if (!preg_match('/^[A-Za-z0-9_-]{16,64}$/D', $token)) {
            throw new InvalidArgumentException('El comprobante de operación no es válido. Recarga el formulario.');
        }
    }

    protected static function accountFields(array $data): array
    {
        $email = trim((string) ($data['correo'] ?? ''));
        $label = trim((string) ($data['etiqueta'] ?? '')) ?: $email;
        $notes = trim((string) ($data['notas'] ?? ''));
        if ($label === '' || static::length($label) > 100) {
            throw new \App\Core\ValidationException(['etiqueta' => 'El nombre interno debe tener entre 1 y 100 caracteres.']);
        }
        if ($email === '' || static::length($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \App\Core\ValidationException(['correo' => 'Ingresa un correo válido para la cuenta.']);
        }
        if (strlen($notes) > 65535) {
            throw new \App\Core\ValidationException(['notas' => 'Las notas son demasiado largas.']);
        }
        return ['etiqueta' => $label, 'correo' => $email, 'notas' => $notes !== '' ? $notes : null];
    }

    protected static function encryptedPassword(string $password): ?string
    {
        $password = trim($password);
        if ($password === '') {
            return null;
        }
        if (!defined('APP_KEY') || trim((string) APP_KEY) === '' || !function_exists('openssl_encrypt')) {
            throw new RuntimeException('Configura APP_KEY en el archivo .env para guardar las contraseñas cifradas.');
        }
        $encrypted = \encrypt_secret($password);
        if (!str_starts_with($encrypted, 'enc:') || \decrypt_secret($encrypted) !== $password) {
            throw new RuntimeException('No se pudo cifrar la contraseña de forma segura.');
        }
        if (strlen($encrypted) > 255) {
            throw new \App\Core\ValidationException(['password_cuenta' => 'La contraseña es demasiado larga.']);
        }
        return $encrypted;
    }

    protected static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}

