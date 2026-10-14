<?php
declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;

class ClaudeCuenta extends CuentaCompartida
{
    public const CAPACIDAD = 5;
    public const COSTO_USD = null;
    public const TEMPLATE_KEYS = ['entrega_1', 'renovacion'];
    protected const SERVICIO = 'CLAUDE';
    protected const PREFIJO = 'claude';
    protected const ETIQUETA = 'Claude';
    protected const TIENE_GRACIA = false;

    public static function salePrice(int $devices): string
    {
        if ($devices !== 1) {
            throw new InvalidArgumentException('Cada venta de Claude debe ser de un cupo.');
        }
        return '85.00';
    }
}
