<?php
declare(strict_types=1);

function run_chatgpt_avisos_schema_migrations(\PDO $pdo): void
{
    ensure_table($pdo, 'chatgpt_avisos_config', 'CREATE TABLE chatgpt_avisos_config (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
        aviso_fin_pagado TINYINT UNSIGNED NOT NULL DEFAULT 3,
        aviso_fin_gracia TINYINT UNSIGNED NOT NULL DEFAULT 3,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION);
    $pdo->exec('INSERT IGNORE INTO chatgpt_avisos_config (id, aviso_fin_pagado, aviso_fin_gracia) VALUES (1, 3, 3)');
}
