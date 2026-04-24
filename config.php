<?php
// Load .env from the project root
$_env_file = __DIR__ . '/.env';
if (file_exists($_env_file)) {
    foreach (file($_env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (str_starts_with(trim($_line), '#') || !str_contains($_line, '=')) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_k = trim($_k); $_v = trim($_v);
        if (!isset($_ENV[$_k])) { putenv("$_k=$_v"); $_ENV[$_k] = $_v; }
    }
}

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_NAME',    getenv('DB_NAME')    ?: 'cv_editor');
define('MAIL_FROM',  getenv('MAIL_FROM')  ?: 'noreply@example.com');
define('APP_NAME',   getenv('APP_NAME')   ?: 'CVFlow');
