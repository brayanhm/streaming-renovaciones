<?php
declare(strict_types=1);

/**
 * Registro independiente de pagos y dispositivos de ChatGPT.
 * Las suscripciones anteriores se conservan y siguen ocupando sus cupos.
 */
function run_chatgpt_schema_migrations(\PDO $pdo): void
{
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
    ensure_column(
        $pdo,
        'cuentas_principales',
        'revision_vigencia_pendiente',
        'ALTER TABLE cuentas_principales ADD COLUMN revision_vigencia_pendiente TINYINT(1) NOT NULL DEFAULT 0 AFTER fin_gracia'
    );

    $tableOptions = ' ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION;
    ensure_table($pdo, 'chatgpt_pagos', 'CREATE TABLE chatgpt_pagos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cuenta_principal_id INT NOT NULL,
        fecha_pago DATE NOT NULL,
        fin_pagado DATE NOT NULL,
        fin_gracia DATE NOT NULL,
        monto_usd DECIMAL(10,2) NOT NULL DEFAULT 20.00,
        monto_bs DECIMAL(10,2) NOT NULL,
        token_operacion VARCHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_chatgpt_pago_token (token_operacion),
        INDEX idx_chatgpt_pago_cuenta (cuenta_principal_id, fecha_pago, id)
    )' . $tableOptions);
    ensure_table($pdo, 'chatgpt_ventas', 'CREATE TABLE chatgpt_ventas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cuenta_principal_id INT NOT NULL,
        fecha_venta DATE NOT NULL,
        dispositivos TINYINT UNSIGNED NOT NULL,
        monto_bs DECIMAL(10,2) NOT NULL,
        fecha_vencimiento DATE NOT NULL,
        token_operacion VARCHAR(64) NOT NULL,
        liberada_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_chatgpt_venta_token (token_operacion),
        INDEX idx_chatgpt_venta_cuenta (cuenta_principal_id, liberada_at)
    )' . $tableOptions);
    ensure_table($pdo, 'chatgpt_liberaciones', 'CREATE TABLE chatgpt_liberaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cuenta_principal_id INT NOT NULL,
        dispositivos_liberados INT NOT NULL,
        motivo VARCHAR(255) NOT NULL,
        usuario VARCHAR(60) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_chatgpt_liberacion_cuenta (cuenta_principal_id, created_at)
    )' . $tableOptions);
    ensure_table($pdo, 'chatgpt_plantillas', 'CREATE TABLE chatgpt_plantillas (
        clave VARCHAR(20) PRIMARY KEY,
        contenido TEXT NOT NULL
    )' . $tableOptions);

    // Instalar las entregas iniciales sin sobrescribir textos guardados por el administrador.
    $defaultTemplates = require __DIR__ . '/chatgpt_templates.php';
    $insertTemplate = $pdo->prepare('INSERT INTO chatgpt_plantillas (clave, contenido)
        VALUES (:clave, :contenido) ON DUPLICATE KEY UPDATE clave = VALUES(clave)');
    foreach ($defaultTemplates as $key => $content) {
        $insertTemplate->execute(['clave' => $key, 'contenido' => $content]);
    }

    foreach (['chatgpt_pagos', 'chatgpt_ventas', 'chatgpt_liberaciones'] as $table) {
        ensure_foreign_key($pdo, $table, 'cuenta_principal_id', 'cuentas_principales', 'id', 'RESTRICT');
    }

    $index = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND INDEX_NAME = :index_name');
    $index->execute([
        'schema' => DB_NAME,
        'table' => 'cuentas_principales',
        'index_name' => 'idx_cp_servicio_correo',
    ]);
    if ((int) $index->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE cuentas_principales ADD INDEX idx_cp_servicio_correo (servicio, correo)');
    }

    // La clasificación inicial no depende de una coincidencia posterior en el nombre.
    $pdo->exec("UPDATE cuentas_principales cp
        INNER JOIN plataformas p ON p.id = cp.plataforma_id
        SET cp.servicio = CASE
            WHEN LOWER(p.nombre) LIKE '%chatgpt%' THEN 'CHATGPT'
            WHEN LOWER(p.nombre) LIKE '%claude%' THEN 'CLAUDE'
            ELSE NULL END
        WHERE cp.servicio IS NULL
          AND (LOWER(p.nombre) LIKE '%chatgpt%' OR LOWER(p.nombre) LIKE '%claude%')");

    // PHP limita el día al último del mes; DATE_ADD puede producir otro resultado.
    $rows = $pdo->query("SELECT id, fecha_vencimiento FROM cuentas_principales
        WHERE servicio = 'CHATGPT' AND fin_gracia IS NULL AND fecha_vencimiento IS NOT NULL")->fetchAll();
    $update = $pdo->prepare('UPDATE cuentas_principales SET fin_gracia = :fin_gracia
        WHERE id = :id AND fin_gracia IS NULL');
    foreach ($rows as $row) {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $row['fecha_vencimiento']);
        if (!$date || $date->format('Y-m-d') !== $row['fecha_vencimiento'] || (int) $date->format('Y') < 1000) {
            continue;
        }
        $grace = shift_months_clamped($date, 1)->format('Y-m-d');
        if (strlen($grace) !== 10) {
            continue;
        }
        $update->execute(['id' => (int) $row['id'], 'fin_gracia' => $grace]);
    }
}
