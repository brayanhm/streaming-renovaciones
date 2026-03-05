<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movimiento;

class ReportesController extends Controller
{
    private Movimiento $movimientos;

    public function __construct()
    {
        $this->movimientos = new Movimiento();
    }

    public function index(): void
    {
        $porMes = $this->movimientos->reportePorMes(12);
        $porPlataforma = $this->movimientos->reportePorPlataforma();
        $mesActual = $this->movimientos->resumenMesActual();

        $this->render('reportes/index', [
            'pageTitle' => 'Reportes',
            'porMes' => $porMes,
            'porPlataforma' => $porPlataforma,
            'mesActual' => $mesActual,
        ]);
    }
}
