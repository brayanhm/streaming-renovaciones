<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ValidationException;
use DateTimeImmutable;
use PDOException;

class ChatgptAvisos extends BaseModel
{
    public const DEFAULTS = ['aviso_fin_pagado' => 3, 'aviso_fin_gracia' => 3];

    public function config(): array
    {
        try {
            $row = $this->db->query('SELECT aviso_fin_pagado, aviso_fin_gracia FROM chatgpt_avisos_config WHERE id = 1')->fetch();
        } catch (PDOException $error) {
            // La lectura funciona con los valores iniciales antes de aplicar la migración.
            if ($error->getCode() !== '42S02') throw $error;
            $row = false;
        }
        return $row ? array_map('intval', $row) : self::DEFAULTS;
    }

    public function saveConfig(array $input): void
    {
        $values = []; $errors = [];
        foreach (self::DEFAULTS as $field => $default) {
            $value = $input[$field] ?? null;
            if (!is_scalar($value) || !preg_match('/^(0|[1-9][0-9]?)$/D', (string) $value) || (int) $value > 60) {
                $errors[$field] = 'Indica entre 0 y 60 días de anticipación.';
            } else $values[$field] = (int) $value;
        }
        if ($errors !== []) throw new ValidationException($errors);
        $stmt = $this->db->prepare('INSERT INTO chatgpt_avisos_config (id, aviso_fin_pagado, aviso_fin_gracia)
            VALUES (1, :aviso_fin_pagado, :aviso_fin_gracia) ON DUPLICATE KEY UPDATE
            aviso_fin_pagado = VALUES(aviso_fin_pagado), aviso_fin_gracia = VALUES(aviso_fin_gracia)');
        $stmt->execute($values);
    }

    public function resumen(?DateTimeImmutable $today = null): array
    {
        $today = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);
        $config = $this->config();
        $result = ['config' => $config, 'pagado' => [], 'gracia' => [], 'vencidas' => [],
            'urgentes_pagado' => 0, 'urgentes_gracia' => 0, 'inactivas' => 0, 'fechas_pendientes' => 0];
        $rows = $this->db->query("SELECT id, etiqueta, correo, fecha_inicio, fecha_vencimiento, fin_gracia,
            plan_gratis, revision_vigencia_pendiente, activo FROM cuentas_principales WHERE servicio = 'CHATGPT'")->fetchAll();
        foreach ($rows as $row) {
            if ((int) $row['activo'] !== 1) { $result['inactivas']++; continue; }
            $period = ChatgptCuenta::period($row, $today);
            $paid = \parse_ymd_date((string) $row['fecha_vencimiento']);
            $grace = \parse_ymd_date((string) $row['fin_gracia']);
            if ($period['estado_vigencia'] === 'SIN_PAGO' || $paid === null || $grace === null || $grace <= $paid) {
                $result['fechas_pendientes']++; continue;
            }
            $row += $period;
            $paidDays = (int) $today->diff($paid)->format('%r%a');
            $graceDays = (int) $today->diff($grace)->format('%r%a');
            if ($paidDays >= 0) {
                $urgent = $paidDays <= $config['aviso_fin_pagado'];
                $result['pagado'][] = $row + ['fecha_aviso' => $paid->format('Y-m-d'), 'dias' => $paidDays, 'urgente' => $urgent];
                if ($urgent) $result['urgentes_pagado']++;
            }
            if ($graceDays >= 0) {
                $urgent = $graceDays <= $config['aviso_fin_gracia'];
                $result['gracia'][] = $row + ['fecha_aviso' => $grace->format('Y-m-d'), 'dias' => $graceDays, 'urgente' => $urgent];
                if ($urgent) $result['urgentes_gracia']++;
            } else {
                $result['vencidas'][] = $row + ['fecha_aviso' => $grace->format('Y-m-d'), 'dias' => $graceDays, 'urgente' => true];
            }
        }
        foreach (['pagado', 'gracia', 'vencidas'] as $key) {
            usort($result[$key], static function (array $left, array $right) use ($key): int {
                $order = strcmp($left['fecha_aviso'], $right['fecha_aviso']);
                // Lo próximo primero; entre vencidas, lo vencido más recientemente primero.
                if ($key === 'vencidas') $order *= -1;
                return $order ?: strcasecmp($left['etiqueta'], $right['etiqueta']) ?: ((int) $left['id'] <=> (int) $right['id']);
            });
        }
        return $result;
    }
}
