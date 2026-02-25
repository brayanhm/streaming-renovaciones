<?php
declare(strict_types=1);

/**
 * Ejecuta migraciones ligeras de esquema necesarias para esta version.
 * Se corre en cada request, pero solo aplica cambios faltantes.
 */
function run_schema_migrations(): void
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    $pdo = db();

    ensure_column(
        $pdo,
        'modalidades',
        'tipo_cuenta',
        "ALTER TABLE modalidades ADD COLUMN tipo_cuenta ENUM('CUENTA_COMPLETA','POR_DISPOSITIVOS','AMBOS') NOT NULL DEFAULT 'CUENTA_COMPLETA' AFTER nombre_modalidad"
    );

    ensure_column(
        $pdo,
        'modalidades',
        'duracion_meses',
        'ALTER TABLE modalidades ADD COLUMN duracion_meses INT NOT NULL DEFAULT 1 AFTER tipo_cuenta'
    );

    ensure_column(
        $pdo,
        'modalidades',
        'dispositivos',
        'ALTER TABLE modalidades ADD COLUMN dispositivos INT NULL AFTER duracion_meses'
    );

    ensure_column(
        $pdo,
        'suscripciones',
        'precio_venta',
        'ALTER TABLE suscripciones ADD COLUMN precio_venta DECIMAL(10,2) NULL AFTER modalidad_id'
    );

    ensure_column(
        $pdo,
        'modalidades',
        'costo',
        'ALTER TABLE modalidades ADD COLUMN costo DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER precio'
    );

    ensure_column(
        $pdo,
        'suscripciones',
        'costo_base',
        'ALTER TABLE suscripciones ADD COLUMN costo_base DECIMAL(10,2) NULL AFTER precio_venta'
    );

    ensure_column(
        $pdo,
        'movimientos',
        'costo',
        'ALTER TABLE movimientos ADD COLUMN costo DECIMAL(10,2) NULL AFTER monto'
    );

    ensure_column(
        $pdo,
        'movimientos',
        'utilidad',
        'ALTER TABLE movimientos ADD COLUMN utilidad DECIMAL(10,2) NULL AFTER costo'
    );

    ensure_column(
        $pdo,
        'plataformas',
        'duraciones_disponibles',
        'ALTER TABLE plataformas ADD COLUMN duraciones_disponibles VARCHAR(100) NULL AFTER tipo_servicio'
    );

    ensure_column(
        $pdo,
        'plataformas',
        'dato_renovacion',
        "ALTER TABLE plataformas ADD COLUMN dato_renovacion VARCHAR(20) NOT NULL DEFAULT 'NO_APLICA' AFTER duraciones_disponibles"
    );

    $pdo->exec('UPDATE modalidades SET duracion_meses = 1 WHERE duracion_meses IS NULL OR duracion_meses <= 0');
    $pdo->exec('UPDATE modalidades SET dispositivos = NULL WHERE dispositivos IS NOT NULL AND dispositivos <= 0');
    $pdo->exec('UPDATE modalidades SET costo = precio WHERE costo IS NULL OR costo <= 0');
    $pdo->exec(
        'UPDATE suscripciones s
         INNER JOIN modalidades m ON m.id = s.modalidad_id
         SET s.costo_base = m.costo
         WHERE s.costo_base IS NULL'
    );
    $pdo->exec("UPDATE plataformas SET duraciones_disponibles = NULL WHERE TRIM(COALESCE(duraciones_disponibles, '')) = ''");
    $pdo->exec(
        "UPDATE plataformas
         SET dato_renovacion = 'USUARIO'
         WHERE tipo_servicio = 'RENOVABLE'
           AND UPPER(TRIM(COALESCE(dato_renovacion, ''))) NOT IN ('USUARIO', 'CORREO')"
    );
    $pdo->exec(
        "UPDATE plataformas
         SET dato_renovacion = UPPER(TRIM(dato_renovacion))
         WHERE tipo_servicio = 'RENOVABLE'
           AND UPPER(TRIM(COALESCE(dato_renovacion, ''))) IN ('USUARIO', 'CORREO')"
    );
    $pdo->exec(
        "UPDATE plataformas
         SET dato_renovacion = 'NO_APLICA'
         WHERE tipo_servicio <> 'RENOVABLE'"
    );
}

function ensure_column(\PDO $pdo, string $table, string $column, string $ddl): void
{
    $query = $pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :table
           AND COLUMN_NAME = :column'
    );
    $query->execute([
        'schema' => DB_NAME,
        'table' => $table,
        'column' => $column,
    ]);

    $total = (int) ($query->fetch()['total'] ?? 0);
    if ($total === 0) {
        $pdo->exec($ddl);
    }
}
