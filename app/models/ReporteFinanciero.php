<?php
declare(strict_types=1);
namespace App\Models;

use DateTimeImmutable;
use InvalidArgumentException;

/** Lectura de ingresos y costos registrados; no cambia ventas, pagos ni suscripciones. */
final class ReporteFinanciero extends BaseModel
{
    public const SERVICIOS = ['todos' => 'Todos los servicios', 'streaming' => 'Renovaciones registradas', 'chatgpt' => 'ChatGPT', 'claude' => 'Claude'];

    public function resumen(string $service = 'todos', int $months = 12, ?DateTimeImmutable $today = null): array
    {
        if (!isset(self::SERVICIOS[$service]) || !in_array($months, [6, 12, 24], true)) throw new InvalidArgumentException('Selecciona un servicio y un período válidos.');
        $today = $today ?? new DateTimeImmutable('today');
        $month = $today->modify('first day of this month')->setTime(0, 0);
        $start = $month->modify('-' . ($months - 1) . ' months');
        $end = $month->modify('+1 month');
        $sql = "SELECT servicio, plataforma, DATE_FORMAT(fecha, '%Y-%m') AS periodo,
            SUM(operaciones) AS operaciones, SUM(ingreso) AS ingreso, SUM(costo) AS costo FROM (
                SELECT 'streaming' AS servicio, COALESCE(p.nombre, mv.plataforma_nombre, 'Sin plataforma') AS plataforma,
                    mv.fecha, 1 AS operaciones, mv.monto AS ingreso, mv.costo FROM movimientos mv
                    LEFT JOIN suscripciones s ON s.id = mv.suscripcion_id
                    LEFT JOIN plataformas p ON p.id = COALESCE(mv.plataforma_id, s.plataforma_id)
                UNION ALL SELECT 'chatgpt', 'ChatGPT', fecha_venta, 1, monto_bs, 0 FROM chatgpt_ventas
                UNION ALL SELECT 'chatgpt', 'ChatGPT', fecha_pago, 0, 0, monto_bs FROM chatgpt_pagos
                UNION ALL SELECT 'claude', 'Claude', fecha_venta, 1, monto_bs, 0 FROM claude_ventas
                UNION ALL SELECT 'claude', 'Claude', fecha_pago, 0, 0, monto_bs FROM claude_pagos
            ) registros WHERE fecha >= :desde AND fecha < :hasta";
        $params = ['desde' => $start->format('Y-m-d'), 'hasta' => $end->format('Y-m-d')];
        if ($service !== 'todos') { $sql .= ' AND servicio = :servicio'; $params['servicio'] = $service; }
        $sql .= ' GROUP BY servicio, plataforma, periodo ORDER BY periodo, servicio, plataforma';
        $stmt = $this->db->prepare($sql); $stmt->execute($params);
        $byMonth = []; $byPlatform = []; $total = $this->emptyBucket(); $current = $this->emptyBucket();
        foreach ($stmt->fetchAll() as $row) {
            $key = (string) $row['periodo'];
            $platformKey = $row['servicio'] . ':' . $row['plataforma'];
            $bucket = ['renovaciones' => (int) $row['operaciones'], 'income_cents' => self::cents((string) ($row['ingreso'] ?? '0')),
                'cost_cents' => self::cents((string) ($row['costo'] ?? '0'))];
            $byMonth[$key] = $this->add($byMonth[$key] ?? $this->emptyBucket(), $bucket);
            $byPlatform[$platformKey] = $this->add($byPlatform[$platformKey] ?? ($this->emptyBucket() + ['plataforma' => $row['plataforma'], 'servicio' => $row['servicio']]), $bucket);
            $total = $this->add($total, $bucket);
            if ($key === $month->format('Y-m')) $current = $this->add($current, $bucket);
        }
        ksort($byMonth);
        $monthly = [];
        foreach ($byMonth as $period => $bucket) $monthly[] = $this->present($bucket) + ['anio' => (int) substr($period, 0, 4), 'mes' => (int) substr($period, 5, 2)];
        return ['porMes' => $monthly, 'porPlataforma' => array_map(fn ($bucket) => $this->present($bucket), array_values($byPlatform)),
            'mesActual' => $this->present($current), 'totalPeriodo' => $this->present($total),
            'desde' => $start->format('Y-m-d'), 'hasta' => $end->modify('-1 day')->format('Y-m-d'), 'servicio' => $service, 'meses' => $months];
    }

    private function emptyBucket(): array { return ['renovaciones' => 0, 'income_cents' => 0, 'cost_cents' => 0]; }
    private function add(array $target, array $bucket): array
    {
        foreach (['renovaciones', 'income_cents', 'cost_cents'] as $key) $target[$key] += $bucket[$key];
        return $target;
    }
    private function present(array $bucket): array
    {
        $result = $bucket + ['total_monto' => $bucket['income_cents'] / 100, 'total_costo' => $bucket['cost_cents'] / 100,
            'total_utilidad' => ($bucket['income_cents'] - $bucket['cost_cents']) / 100];
        unset($result['income_cents'], $result['cost_cents']); return $result;
    }
    private static function cents(string $amount): int
    {
        $negative = str_starts_with($amount, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim($amount, '-')), 2, '');
        $value = (int) $whole * 100 + (int) substr(str_pad($fraction, 2, '0'), 0, 2);
        return $negative ? -$value : $value;
    }
}
