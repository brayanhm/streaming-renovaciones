<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

abstract class BaseModel
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? \db();
    }
}
