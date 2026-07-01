<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: items.php');
    exit;
}

$pdo      = get_pdo();
$photo_id = (int)($_POST['photo_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM item_photos WHERE id = ?');
$stmt->execute([$photo_id]);
$photo = $stmt->fetch();

if ($photo) {
    $path = __DIR__ . '/uploads/items/' . $photo['item_id'] . '/' . $photo['filename'];
    if (is_file($path)) unlink($path);

    $pdo->prepare('DELETE FROM item_photos WHERE id = ?')->execute([$photo_id]);
    log_operation((int)$photo['item_id'], '写真削除', '写真を削除');
}

header('Location: item_detail.php?id=' . ($photo['item_id'] ?? 0));
exit;
