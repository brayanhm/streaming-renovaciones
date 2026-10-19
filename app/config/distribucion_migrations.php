<?php
declare(strict_types=1);

/** Selección única por servicio e historial de reinicios, sin alterar los accesos existentes. */
function run_distribucion_schema_migrations(\PDO $pdo): void
{
    ensure_column($pdo, 'cuentas_principales', 'plan_gratis',
        'ALTER TABLE cuentas_principales ADD COLUMN plan_gratis TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($pdo, 'cuentas_principales', 'ajuste_ocupacion',
        'ALTER TABLE cuentas_principales ADD COLUMN ajuste_ocupacion INT NOT NULL DEFAULT 0');
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
    ensure_table($pdo, 'cuentas_ajustes_ocupacion', 'CREATE TABLE cuentas_ajustes_ocupacion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cuenta_principal_id INT NOT NULL,
        ocupados_antes INT NOT NULL,
        ocupados_despues INT NOT NULL,
        motivo VARCHAR(255) NOT NULL,
        usuario VARCHAR(60) NULL,
        token_operacion VARCHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_ajuste_ocupacion_token (token_operacion),
        INDEX idx_ajuste_ocupacion_cuenta (cuenta_principal_id, id)
    )' . $options);
    // El ciclo 0 corresponde a la distribución anterior al primer reinicio.
    // No se rellenan fechas anteriores: solo se guardan eventos nuevos.
    ensure_table($pdo, 'cuentas_distribucion_historial', 'CREATE TABLE cuentas_distribucion_historial (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cuenta_principal_id INT NOT NULL,
        ciclo_id INT NOT NULL DEFAULT 0,
        inicio_distribucion DATETIME NULL,
        cupos_completados_at DATETIME NULL,
        capacidad_al_completar INT NULL,
        ocupados_al_completar INT NULL,
        UNIQUE KEY uq_distribucion_historial_ciclo (cuenta_principal_id, ciclo_id)
    )' . $options);
    ensure_foreign_key($pdo, 'cuentas_distribucion', 'cuenta_principal_id', 'cuentas_principales', 'id', 'RESTRICT');
    ensure_foreign_key($pdo, 'cuentas_ciclos', 'cuenta_principal_id', 'cuentas_principales', 'id', 'RESTRICT');
    ensure_foreign_key($pdo, 'cuentas_ajustes_ocupacion', 'cuenta_principal_id', 'cuentas_principales', 'id', 'RESTRICT');
    ensure_foreign_key($pdo, 'cuentas_distribucion_historial', 'cuenta_principal_id', 'cuentas_principales', 'id', 'RESTRICT');
    $pdo->exec("INSERT IGNORE INTO cuentas_distribucion (servicio) VALUES ('CHATGPT'), ('CLAUDE')");
}
