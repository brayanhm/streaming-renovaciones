<?php
declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;

class ChatgptCuenta extends CuentaCompartida
{
    public const CAPACIDAD = 20;
    public const COSTO_USD = '20.00';
    public const TEMPLATE_KEYS = ['entrega_1', 'entrega_2', 'renovacion'];
    protected const SERVICIO = 'CHATGPT';
    protected const PREFIJO = 'chatgpt';
    protected const ETIQUETA = 'ChatGPT';
    protected const TIENE_GRACIA = true;
    protected const CONTAR_DISPOSITIVOS = true;

    public function importPendingEmails(array $emails): array
    {
        if (count($emails) > 200) {
            throw new InvalidArgumentException('Importa un máximo de 200 correos por vez.');
        }
        $unique = [];
        foreach ($emails as $email) {
            if (!is_string($email)) {
                throw new InvalidArgumentException('Ingresa un correo válido para cada cuenta.');
            }
            $email = strtolower(trim($email));
            if ($email === '' || static::length($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Ingresa un correo válido para cada cuenta.');
            }
            $unique[$email] = $email;
        }
        if ($unique === []) {
            return ['creadas' => 0, 'existentes' => 0, 'ids' => []];
        }
        return $this->transaction(function () use ($unique): array {
            $platformId = $this->ensurePlatform();
            $find = $this->db->prepare('SELECT id FROM cuentas_principales
                WHERE servicio = :servicio AND LOWER(TRIM(correo)) = :correo
                ORDER BY id ASC LIMIT 1 FOR UPDATE');
            $insert = $this->db->prepare('INSERT INTO cuentas_principales
                (plataforma_id, servicio, etiqueta, correo, password_cuenta, capacidad,
                 fecha_inicio, fecha_vencimiento, fin_gracia, activo, notas, revision_vigencia_pendiente)
                VALUES (:plataforma, :servicio, :etiqueta, :correo, NULL, :capacidad,
                    NULL, NULL, NULL, 1, :notas, 1)');
            $result = ['creadas' => 0, 'existentes' => 0, 'ids' => []];
            foreach ($unique as $email) {
                $find->execute(['servicio' => static::SERVICIO, 'correo' => $email]);
                $existing = $find->fetchColumn();
                if ($existing !== false) {
                    $result['existentes']++;
                    $result['ids'][] = (int) $existing;
                    continue;
                }
                $insert->execute([
                    'plataforma' => $platformId,
                    'servicio' => static::SERVICIO,
                    'etiqueta' => substr($email, 0, 100),
                    'correo' => $email,
                    'capacidad' => static::CAPACIDAD,
                    'notas' => 'Cuenta existente: fechas y credenciales pendientes de revisión',
                ]);
                $result['creadas']++;
                $result['ids'][] = (int) $this->db->lastInsertId();
            }
            return $result;
        });
    }

    public function configureExistingPeriod(int $id, string $origin, string $date): void
    {
        if (!in_array($origin, ['PAGO', 'GRACIA'], true)) {
            throw new InvalidArgumentException('Indica si conoces la fecha de pago o el inicio de gracia.');
        }
        $start = static::date($date);
        static::assertNotFuture($date);
        if ($origin === 'PAGO') {
            $dates = static::paymentDates($date);
            $fields = [
                'fecha_inicio' => $dates['fecha_pago'],
                'fecha_vencimiento' => $dates['fin_pagado'],
                'fin_gracia' => $dates['fin_gracia'],
            ];
        } else {
            $fields = [
                'fecha_inicio' => null,
                'fecha_vencimiento' => $start->format('Y-m-d'),
                'fin_gracia' => \shift_months_clamped($start, 1)->format('Y-m-d'),
            ];
        }
        $this->transaction(function () use ($id, $fields): void {
            $this->lockAccount($id);
            $stmt = $this->db->prepare('UPDATE cuentas_principales SET fecha_inicio = :fecha_inicio,
                fecha_vencimiento = :fecha_vencimiento, fin_gracia = :fin_gracia,
                revision_vigencia_pendiente = 0 WHERE id = :id');
            $stmt->execute($fields + ['id' => $id]);
        });
    }

    public static function salePrice(int $devices): string
    {
        return match ($devices) {
            1 => '30.00',
            2 => '55.00',
            default => throw new InvalidArgumentException('La venta debe ser de 1 o 2 dispositivos.'),
        };
    }
}
