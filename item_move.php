<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_login();

$pdo = get_pdo();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare(
    'SELECT i.*, s.name AS school_name FROM items i
     JOIN schools s ON i.school_id = s.id WHERE i.id = ?'
);
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    header('Location: items.php');
    exit;
}

$schools = $pdo->query('SELECT * FROM schools ORDER BY id')->fetchAll();
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_school_id  = (int)($_POST['to_school_id']  ?? 0);
    $to_location   = trim($_POST['to_location']   ?? '');
    $note          = trim($_POST['note']           ?? '');

    if ($to_school_id === 0)  $errors[] = '移動先校舎を選択してください。';
    if ($to_location  === '') $errors[] = '移動先場所を入力してください。';

    if (empty($errors)) {
        // 移動履歴を記録
        $pdo->prepare(
            'INSERT INTO move_logs (item_id, from_school_id, from_location, to_school_id, to_location, moved_by, note)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $id,
            $item['school_id'],
            $item['location'],
            $to_school_id,
            $to_location,
            get_operator(),
            $note ?: null,
        ]);
        // 備品の校舎・場所を更新
        $pdo->prepare('UPDATE items SET school_id=?, location=? WHERE id=?')
            ->execute([$to_school_id, $to_location, $id]);
        log_operation($id, '移動', $item['school_name'] . '/' . $item['location'] . ' → ' . $to_location);
        header('Location: item_detail.php?id=' . $id);
        exit;
    }
}

layout_head('移動登録');
layout_nav();
?>
<div class="max-w-xl mx-auto px-4 py-6">
  <h2 class="text-xl font-bold mb-6">移動登録</h2>
  <div class="bg-blue-50 rounded p-4 mb-6 text-sm">
    <p class="font-medium"><?= h($item['name']) ?> <span class="text-gray-500"><?= h($item['code']) ?></span></p>
    <p class="text-gray-600 mt-1">現在の場所：<?= h($item['school_name']) ?> / <?= h($item['location']) ?></p>
  </div>
  <?php if ($errors): ?>
  <div class="bg-red-50 text-red-700 rounded p-3 mb-4 text-sm">
    <?php foreach ($errors as $e): ?><p><?= h($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <form method="post" class="bg-white rounded shadow p-6 space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">移動先校舎 <span class="text-red-500">*</span></label>
      <select name="to_school_id" class="w-full border rounded px-3 py-2 text-sm" required>
        <option value="0">選択してください</option>
        <?php foreach ($schools as $s): ?>
        <option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">移動先場所 <span class="text-red-500">*</span></label>
      <input type="text" name="to_location" required placeholder="例：2F教室"
             class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">備考</label>
      <textarea name="note" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea>
    </div>
    <div class="flex gap-3 pt-2">
      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">移動を登録する</button>
      <a href="item_detail.php?id=<?= $id ?>" class="px-6 py-2 rounded border text-sm hover:bg-gray-50">キャンセル</a>
    </div>
  </form>
</div>
<?php layout_foot(); ?>
