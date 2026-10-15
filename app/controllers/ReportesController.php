<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ReporteFinanciero;
use App\Models\Suscripcion;

class ReportesController extends Controller
{
    private ReporteFinanciero $reportes;
    private Suscripcion $suscripciones;

    public function __construct()
    {
        $this->reportes = new ReporteFinanciero();
        $this->suscripciones = new Suscripcion();
    }

    public function index(): void
    {
        $this->suscripciones->recalculateStates(RECUP_DAYS);

        [$service, $months] = $this->filters();
        $report = $this->reportes->resumen($service, $months);
        $cartera = $this->suscripciones->estadisticas();

        $this->render('reportes/index', [
            'pageTitle' => 'Reportes',
            'cartera' => $cartera,
            'servicios' => ReporteFinanciero::SERVICIOS,
        ] + $report);
    }

    public function exportarMovimientos(): void
    {
        [$service, $months] = $this->filters();
        $report = $this->reportes->resumen($service, $months);
        $rows = $report['porMes'];
        $filename = 'reporte_' . $service . '_' . $months . '_meses_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
        fputcsv($out, ['Servicio', 'Desde', 'Hasta', 'Anio', 'Mes', 'Ventas o renovaciones', 'Ingresos registrados', 'Costos registrados', 'Saldo registrado'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                ReporteFinanciero::SERVICIOS[$service], $report['desde'], $report['hasta'],
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

    private function filters(): array
    {
        $service = is_string($_GET['servicio'] ?? null) && isset(ReporteFinanciero::SERVICIOS[$_GET['servicio']]) ? $_GET['servicio'] : 'todos';
        $months = (int) ($_GET['meses'] ?? 12);
        return [$service, in_array($months, [6, 12, 24], true) ? $months : 12];
    }
}
