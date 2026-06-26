<?php
require_once __DIR__ . '/config.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function require_login(): void {
    start_session();
    if (empty($_SESSION['operator'])) {
        header('Location: index.php');
        exit;
    }
    // タイムアウトチェック
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
        header('Location: index.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function get_operator(): string {
    return $_SESSION['operator'] ?? '';
}

function log_operation(int|null $item_id, string $action, string $detail = ''): void {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO operation_logs (item_id, action, operator, detail) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$item_id, $action, get_operator(), $detail]);
}
