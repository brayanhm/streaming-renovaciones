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
        'movimientos',
        'plataforma_id',
        'ALTER TABLE movimientos ADD COLUMN plataforma_id INT NULL AFTER suscripcion_id'
    );

    ensure_column(
        $pdo,
        'movimientos',
        'plataforma_nombre',
        'ALTER TABLE movimientos ADD COLUMN plataforma_nombre VARCHAR(100) NULL AFTER plataforma_id'
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

    ensure_table_utf8mb4($pdo, 'plataformas');

    ensure_column(
        $pdo,
        'suscripciones',
        'notas',
        'ALTER TABLE suscripciones ADD COLUMN notas TEXT NULL AFTER usuario_proveedor'
    );

    ensure_column(
        $pdo,
        'usuarios',
        'activo',
        'ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER rol'
    );

    ensure_column(
        $pdo,
        'suscripciones',
        'password_cuenta',
        'ALTER TABLE suscripciones ADD COLUMN password_cuenta VARCHAR(255) NULL AFTER usuario_proveedor'
    );

    ensure_table(
        $pdo,
        'auditoria',
        'CREATE TABLE auditoria (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario VARCHAR(60) NULL,
            accion VARCHAR(60) NOT NULL,
            detalle VARCHAR(255) NULL,
            ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_auditoria_fecha (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION
    );

    // Plataformas con "cuentas principales" y usuarios asignados (ej. ChatGPT, Claude).
    ensure_column(
        $pdo,
        'plataformas',
        'usa_cuentas_principales',
        'ALTER TABLE plataformas ADD COLUMN usa_cuentas_principales TINYINT(1) NOT NULL DEFAULT 0 AFTER dato_renovacion'
    );

    ensure_table(
        $pdo,
        'cuentas_principales',
        'CREATE TABLE cuentas_principales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plataforma_id INT NOT NULL,
            etiqueta VARCHAR(100) NOT NULL,
            correo VARCHAR(150) NULL,
            password_cuenta VARCHAR(255) NULL,
            capacidad INT NOT NULL DEFAULT 1,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            notas TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cp_plataforma (plataforma_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION
    );

    // Fechas propias de la cuenta principal: su activación y su vencimiento de pago
    // (independientes de la vigencia de cada usuario asignado).
    ensure_column(
        $pdo,
        'cuentas_principales',
        'fecha_inicio',
        'ALTER TABLE cuentas_principales ADD COLUMN fecha_inicio DATE NULL AFTER capacidad'
    );
    ensure_column(
        $pdo,
        'cuentas_principales',
        'fecha_vencimiento',
        'ALTER TABLE cuentas_principales ADD COLUMN fecha_vencimiento DATE NULL AFTER fecha_inicio'
    );

    ensure_column(
        $pdo,
        'suscripciones',
        'cuenta_principal_id',
        'ALTER TABLE suscripciones ADD COLUMN cuenta_principal_id INT NULL AFTER modalidad_id'
    );
    ensure_column(
        $pdo,
        'suscripciones',
        'departamento',
        'ALTER TABLE suscripciones ADD COLUMN departamento VARCHAR(40) NULL AFTER usuario_proveedor'
    );

    ensure_table(
        $pdo,
        'login_intentos',
        'CREATE TABLE login_intentos (
            ip VARCHAR(45) NOT NULL PRIMARY KEY,
            intentos INT NOT NULL DEFAULT 0,
            bloqueado_hasta DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION
    );

    // Llaves foraneas. Borrar una plataforma o un plan NO debe arrasar las
    // suscripciones que dependen de ellos: se fuerza ON DELETE RESTRICT (el
    // esquema original tenia CASCADE, un peligro de perdida masiva de datos).
    // La relacion cliente->suscripcion se deja como esta (borrar un cliente si
    // elimina sus suscripciones, que es la intencion).
    ensure_foreign_key($pdo, 'suscripciones', 'plataforma_id', 'plataformas', 'id', 'RESTRICT');
    ensure_foreign_key($pdo, 'suscripciones', 'modalidad_id', 'modalidades', 'id', 'RESTRICT');
    ensure_foreign_key($pdo, 'modalidades', 'plataforma_id', 'plataformas', 'id', 'RESTRICT');
    ensure_foreign_key($pdo, 'cuentas_principales', 'plataforma_id', 'plataformas', 'id', 'RESTRICT');
    ensure_foreign_key($pdo, 'suscripciones', 'cuenta_principal_id', 'cuentas_principales', 'id', 'SET NULL');

    $pdo->exec('UPDATE modalidades SET duracion_meses = 1 WHERE duracion_meses IS NULL OR duracion_meses <= 0');
    $pdo->exec('UPDATE modalidades SET dispositivos = NULL WHERE dispositivos IS NOT NULL AND dispositivos <= 0');
    $pdo->exec('UPDATE modalidades SET costo = precio WHERE costo IS NULL OR costo <= 0');
    $pdo->exec(
        'UPDATE suscripciones s
         INNER JOIN modalidades m ON m.id = s.modalidad_id
         SET s.costo_base = m.costo
         WHERE s.costo_base IS NULL'
    );
    $pdo->exec(
        'UPDATE movimientos mv
         INNER JOIN suscripciones s ON s.id = mv.suscripcion_id
         INNER JOIN plataformas p ON p.id = s.plataforma_id
         SET mv.plataforma_id = COALESCE(mv.plataforma_id, s.plataforma_id),
             mv.plataforma_nombre = COALESCE(mv.plataforma_nombre, p.nombre)
         WHERE mv.suscripcion_id IS NOT NULL
           AND (mv.plataforma_id IS NULL OR mv.plataforma_nombre IS NULL)'
    );
    ensure_movimientos_subscription_fk_set_null($pdo);
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

    // Telefonos en formato LOCAL (8 digitos): quitar el prefijo 591 ya guardado.
    // El codigo de pais se agrega automaticamente solo al generar el WhatsApp.
    // Idempotente: tras la conversion los numeros son de 8 digitos y no vuelven a coincidir.
    $pdo->exec(
        "UPDATE clientes SET telefono = SUBSTRING(telefono, 4)
         WHERE telefono LIKE '591%' AND LENGTH(telefono) = 11"
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

function ensure_table(\PDO $pdo, string $table, string $createSql): void
{
    $query = $pdo->prepare(
        'SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
    );
    $query->execute(['schema' => DB_NAME, 'table' => $table]);
    if ((int) ($query->fetch()['total'] ?? 0) === 0) {
        $pdo->exec($createSql);
    }
}

function ensure_foreign_key(
    \PDO $pdo,
    string $table,
    string $column,
    string $referencedTable,
    string $referencedColumn,
    string $onDelete = 'RESTRICT'
): void {
    $safeTable = str_replace('`', '``', $table);

    // Ya existe una FK sobre esta columna hacia esa tabla? Con que DELETE_RULE?
    $existing = $pdo->prepare(
        'SELECT k.CONSTRAINT_NAME, rc.DELETE_RULE
         FROM information_schema.KEY_COLUMN_USAGE k
         JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
           ON rc.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND rc.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA
         WHERE k.TABLE_SCHEMA = :schema
           AND k.TABLE_NAME = :table
           AND k.COLUMN_NAME = :column
           AND k.REFERENCED_TABLE_NAME = :ref
         LIMIT 1'
    );
    $existing->execute([
        'schema' => DB_NAME,
        'table' => $table,
        'column' => $column,
        'ref' => $referencedTable,
    ]);
    $current = $existing->fetch();

    if ($current) {
        // Ya tiene la regla deseada: nada que hacer.
        if (strtoupper((string) $current['DELETE_RULE']) === strtoupper($onDelete)) {
            return;
        }
        // Regla distinta (p. ej. CASCADE peligroso): se elimina para recrearla.
        $safeConstraint = str_replace('`', '``', (string) $current['CONSTRAINT_NAME']);
        try {
            $pdo->exec("ALTER TABLE `{$safeTable}` DROP FOREIGN KEY `{$safeConstraint}`");
        } catch (\PDOException $exception) {
            app_log('warning', "No se pudo soltar FK {$safeConstraint}: " . $exception->getMessage());
            return;
        }
    }

    // Hay filas huerfanas? Si las hay, no se agrega la FK (evita fallar la migracion).
    $safeColumn = str_replace('`', '``', $column);
    $safeRef = str_replace('`', '``', $referencedTable);
    $safeRefCol = str_replace('`', '``', $referencedColumn);

    $orphans = (int) $pdo->query(
        "SELECT COUNT(*) AS total
         FROM `{$safeTable}` t
         LEFT JOIN `{$safeRef}` r ON r.`{$safeRefCol}` = t.`{$safeColumn}`
         WHERE t.`{$safeColumn}` IS NOT NULL AND r.`{$safeRefCol}` IS NULL"
    )->fetch()['total'] ?? 0;

    if ($orphans > 0) {
        app_log('warning', "FK omitida: {$table}.{$column} -> {$referencedTable} ({$orphans} huerfanos)");
        return;
    }

    $constraintName = 'fk_' . $table . '_' . $column;
    try {
        $pdo->exec(
            "ALTER TABLE `{$safeTable}`
             ADD CONSTRAINT `{$constraintName}`
             FOREIGN KEY (`{$safeColumn}`) REFERENCES `{$safeRef}` (`{$safeRefCol}`)
             ON DELETE {$onDelete} ON UPDATE CASCADE"
        );
    } catch (\PDOException $exception) {
        app_log('warning', "No se pudo crear FK {$constraintName}: " . $exception->getMessage());
    }
}

function ensure_table_utf8mb4(\PDO $pdo, string $table): void
{
    $query = $pdo->prepare(
        'SELECT TABLE_COLLATION
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :table
         LIMIT 1'
    );
    $query->execute([
        'schema' => DB_NAME,
        'table' => $table,
    ]);

    $current = strtolower((string) ($query->fetch()['TABLE_COLLATION'] ?? ''));
    if ($current !== '' && str_starts_with($current, 'utf8mb4_')) {
        return;
    }

    $safeTable = str_replace('`', '``', $table);
    $pdo->exec(
        sprintf(
            'ALTER TABLE `%s` CONVERT TO CHARACTER SET %s COLLATE %s',
            $safeTable,
            DB_CHARSET,
            DB_COLLATION
        )
    );
}

function ensure_movimientos_subscription_fk_set_null(\PDO $pdo): void
{
    $query = $pdo->prepare(
        'SELECT CONSTRAINT_NAME, DELETE_RULE
         FROM information_schema.REFERENTIAL_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = :schema
           AND TABLE_NAME = :table
           AND REFERENCED_TABLE_NAME = :referenced
         LIMIT 1'
    );
    $query->execute([
        'schema' => DB_NAME,
        'table' => 'movimientos',
        'referenced' => 'suscripciones',
    ]);

    $constraint = $query->fetch();
    $deleteRule = strtoupper((string) ($constraint['DELETE_RULE'] ?? ''));
    if ($deleteRule === 'SET NULL') {
        return;
    }

    $constraintName = (string) ($constraint['CONSTRAINT_NAME'] ?? '');
    if ($constraintName !== '') {
        $safeConstraint = str_replace('`', '``', $constraintName);
        $pdo->exec('ALTER TABLE movimientos DROP FOREIGN KEY `' . $safeConstraint . '`');
    }

    $pdo->exec('ALTER TABLE movimientos MODIFY suscripcion_id INT NULL');
    $pdo->exec(
        'UPDATE movimientos mv
         LEFT JOIN suscripciones s ON s.id = mv.suscripcion_id
         SET mv.suscripcion_id = NULL
         WHERE mv.suscripcion_id IS NOT NULL
           AND s.id IS NULL'
    );

    $pdo->exec(
        'ALTER TABLE movimientos
         ADD CONSTRAINT movimientos_suscripciones_set_null_fk
         FOREIGN KEY (suscripcion_id) REFERENCES suscripciones (id)
         ON DELETE SET NULL ON UPDATE CASCADE'
    );
}
