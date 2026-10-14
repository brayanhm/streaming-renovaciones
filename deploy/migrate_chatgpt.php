<?php
declare(strict_types=1);

// Migración incremental para instalaciones con APP_RUN_MIGRATIONS=false.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../app/config/config.php';
require_once APP_PATH . '/config/db.php';
require_once APP_PATH . '/config/migrations.php';
require_once APP_PATH . '/config/chatgpt_migrations.php';
require_once APP_PATH . '/config/claude_migrations.php';
run_chatgpt_schema_migrations(db());
run_claude_schema_migrations(db());
echo "Esquemas ChatGPT y Claude actualizados.\n";
