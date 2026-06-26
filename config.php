<?php
// Xサーバーのデータベース設定に合わせて変更すること
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// 共通パスワード（必ず変更すること）
define('APP_PASSWORD', 'change_this_password');

// セッションタイムアウト（秒）
define('SESSION_TIMEOUT', 7200);

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
