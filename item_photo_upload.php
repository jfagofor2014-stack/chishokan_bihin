<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_login();

$pdo = get_pdo();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { header('Location: items.php'); exit; }

$errors = [];
$allowed_types = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
$max_size = 5 * 1024 * 1024; // 5MB

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'アップロードに失敗しました。';
    } elseif ($file['size'] > $max_size) {
        $errors[] = 'ファイルサイズが大きすぎます（上限5MB）。';
    } else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed_types[$mime])) {
            $errors[] = '対応していないファイル形式です（JPEG, PNG, WEBPのみ）。';
        } else {
            $ext      = $allowed_types[$mime];
            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $dir      = __DIR__ . '/uploads/items/' . $id;

            if (!is_dir($dir)) mkdir($dir, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
                $pdo->prepare(
                    'INSERT INTO item_photos (item_id, filename, uploaded_by) VALUES (?, ?, ?)'
                )->execute([$id, $filename, get_operator()]);
                log_operation($id, '写真アップロード', $item['name'] . ' の写真を追加');
                header('Location: item_detail.php?id=' . $id);
                exit;
            }
            $errors[] = 'ファイルの保存に失敗しました。';
        }
    }
}

layout_head('写真アップロード');
layout_nav();
?>
<div class="max-w-xl mx-auto px-4 py-6">
  <div class="flex items-center gap-3 mb-6">
    <a href="item_detail.php?id=<?= $id ?>" class="text-blue-600 hover:underline text-sm">← 備品詳細へ戻る</a>
  </div>
  <h2 class="text-xl font-bold mb-6">写真アップロード：<?= h($item['name']) ?></h2>

  <?php if ($errors): ?>
  <div class="bg-red-50 text-red-700 rounded p-3 mb-4 text-sm">
    <?php foreach ($errors as $e): ?><p><?= h($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="bg-white rounded shadow p-6 space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">写真ファイル <span class="text-red-500">*</span></label>
      <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required
             class="w-full border rounded px-3 py-2 text-sm">
      <p class="text-xs text-gray-500 mt-1">JPEG, PNG, WEBP形式、5MBまで</p>
    </div>
    <div class="flex gap-3 pt-2">
      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">アップロードする</button>
      <a href="item_detail.php?id=<?= $id ?>" class="px-6 py-2 rounded border text-sm hover:bg-gray-50">キャンセル</a>
    </div>
  </form>
</div>
<?php layout_foot(); ?>
