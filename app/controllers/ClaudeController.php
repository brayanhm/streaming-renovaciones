<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ClaudeCuenta;

class ClaudeController extends CuentasCompartidasController
{
    protected const MODEL = ClaudeCuenta::class;
    protected const SERVICE = 'claude';
    protected const LABEL = 'Claude';
    protected const UNIT = 'cupos';
}
