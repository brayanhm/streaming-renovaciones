<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movimiento;
use App\Models\Suscripcion;

class ReportesController extends Controller
{
    private Movimiento $movimientos;
    private Suscripcion $suscripciones;

    public function __construct()
    {
        $this->movimientos = new Movimiento();
        $this->suscripciones = new Suscripcion();
    }

    public function index(): void
    {
        $this->suscripciones->recalculateStates(RECUP_DAYS);

        $porMes = $this->movimientos->reportePorMes(12);
        $porPlataforma = $this->movimientos->reportePorPlataforma();
        $mesActual = $this->movimientos->resumenMesActual();
        $cartera = $this->suscripciones->estadisticas();

        $this->render('reportes/index', [
            'pageTitle' => 'Reportes',
            'porMes' => $porMes,
            'porPlataforma' => $porPlataforma,
            'mesActual' => $mesActual,
            'cartera' => $cartera,
        ]);
    }

    public function exportarMovimientos(): void
    {
        $rows = $this->movimientos->reportePorMes(120);
        $filename = 'reporte_por_mes_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
        fputcsv($out, ['Anio', 'Mes', 'Renovaciones', 'Ingreso', 'Costo', 'Ganancia'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                (int) ($r['anio'] ?? 0),
                (int) ($r['mes'] ?? 0),
                (int) ($r['renovaciones'] ?? 0),
                number_format((float) ($r['total_monto'] ?? 0), 2, '.', ''),
                number_format((float) ($r['total_costo'] ?? 0), 2, '.', ''),
                number_format((float) ($r['total_utilidad'] ?? 0), 2, '.', ''),
            ], ';');
        }
        fclose($out);
        exit;
    }
}
