<?php
declare(strict_types=1);

/**
 * Conexion PDO singleton.
 */
function db(): \PDO
{
    static $pdo = null;

    if ($pdo instanceof \PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $setNames = 'SET NAMES ' . DB_CHARSET;
        if (defined('DB_COLLATION') && DB_COLLATION !== '') {
            $setNames .= ' COLLATE ' . DB_COLLATION;
        }
        $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = $setNames;
    }

    try {
        $pdo = new \PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            $options
        );
    } catch (\PDOException $exception) {
        $logLine = sprintf(
            "[%s] Error DB: %s%s",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            PHP_EOL
        );

        @file_put_contents(STORAGE_PATH . '/logs/app.log', $logLine, FILE_APPEND);

        http_response_code(500);

        if (APP_DEBUG) {
            die('Error de conexion a base de datos: ' . $exception->getMessage());
        }

        die('Error interno del servidor.');
    }

    return $pdo;
}
