<?php
declare(strict_types=1);

/** Selección única por servicio e historial de reinicios, sin alterar los accesos existentes. */
function run_distribucion_schema_migrations(\PDO $pdo): void
{
    ensure_column($pdo, 'cuentas_principales', 'plan_gratis',
        'ALTER TABLE cuentas_principales ADD COLUMN plan_gratis TINYINT(1) NOT NULL DEFAULT 0');
    $options = ' ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION;
    ensure_table($pdo, 'cuentas_distribucion', 'CREATE TABLE cuentas_distribucion (
        servicio VARCHAR(20) PRIMARY KEY,
        cuenta_principal_id INT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )' . $options);
    ensure_table($pdo, 'cuentas_ciclos', 'CREATE TABLE cuentas_ciclos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cuenta_principal_id INT NOT NULL,
        cupos_liberados INT NOT NULL,
        usuario VARCHAR(60) NULL,
        token_operacion VARCHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_ciclo_token (token_operacion),
        INDEX idx_ciclo_cuenta (cuenta_principal_id, id)
    )' . $options);
    ensure_foreign_key($pdo, 'cuentas_distribucion', 'cuenta_principal_id', 'cuentas_principales', 'id', 'RESTRICT');
    ensure_foreign_key($pdo, 'cuentas_ciclos', 'cuenta_principal_id', 'cuentas_principales', 'id', 'RESTRICT');
    $pdo->exec("INSERT IGNORE INTO cuentas_distribucion (servicio) VALUES ('CHATGPT'), ('CLAUDE')");
}
