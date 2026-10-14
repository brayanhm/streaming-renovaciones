<?php
declare(strict_types=1);

/**
 * Registro independiente de pagos y cupos de Claude.
 * Las suscripciones anteriores se conservan y siguen ocupando sus cupos.
 */
function run_claude_schema_migrations(\PDO $pdo): void
{
    require_once __DIR__ . '/distribucion_migrations.php';
    run_distribucion_schema_migrations($pdo);
    ensure_column(
        $pdo,
        'cuentas_principales',
        'servicio',
        'ALTER TABLE cuentas_principales ADD COLUMN servicio VARCHAR(20) NULL AFTER plataforma_id'
    );
    ensure_column(
        $pdo,
        'cuentas_principales',
        'fin_gracia',
        'ALTER TABLE cuentas_principales ADD COLUMN fin_gracia DATE NULL AFTER fecha_vencimiento'
    );

    $tableOptions = ' ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION;
    ensure_table($pdo, 'claude_pagos', 'CREATE TABLE claude_pagos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cuenta_principal_id INT NOT NULL,
        fecha_pago DATE NOT NULL,
        fin_pagado DATE NOT NULL,
        fin_gracia DATE NULL,
        monto_usd DECIMAL(10,2) NULL DEFAULT NULL,
        monto_bs DECIMAL(10,2) NOT NULL,
        token_operacion VARCHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_claude_pago_token (token_operacion),
        INDEX idx_claude_pago_cuenta (cuenta_principal_id, fecha_pago, id)
    )' . $tableOptions);
    ensure_table($pdo, 'claude_ventas', 'CREATE TABLE claude_ventas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cuenta_principal_id INT NOT NULL,
        fecha_venta DATE NOT NULL,
        dispositivos TINYINT UNSIGNED NOT NULL,
        monto_bs DECIMAL(10,2) NOT NULL,
        fecha_vencimiento DATE NOT NULL,
        token_operacion VARCHAR(64) NOT NULL,
        liberada_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_claude_venta_token (token_operacion),
        INDEX idx_claude_venta_cuenta (cuenta_principal_id, liberada_at)
    )' . $tableOptions);
    ensure_table($pdo, 'claude_liberaciones', 'CREATE TABLE claude_liberaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cuenta_principal_id INT NOT NULL,
        dispositivos_liberados INT NOT NULL,
        motivo VARCHAR(255) NOT NULL,
        usuario VARCHAR(60) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_claude_liberacion_cuenta (cuenta_principal_id, created_at)
    )' . $tableOptions);
    ensure_table($pdo, 'claude_plantillas', 'CREATE TABLE claude_plantillas (
        clave VARCHAR(20) PRIMARY KEY,
        contenido TEXT NOT NULL
    )' . $tableOptions);

    foreach (['claude_pagos', 'claude_ventas', 'claude_liberaciones'] as $table) {
        ensure_foreign_key($pdo, $table, 'cuenta_principal_id', 'cuentas_principales', 'id', 'RESTRICT');
    }
}
