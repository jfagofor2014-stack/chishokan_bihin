<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_login();

$pdo = get_pdo();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 単体チェック（詳細画面から）
if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_checked = isset($_POST['checked']) ? 1 : 0;
    $now         = date('Y-m-d H:i:s');
    $pdo->prepare(
        'UPDATE items SET checked=?, checked_at=?, checked_by=? WHERE id=?'
    )->execute([$new_checked, $now, get_operator(), $id]);
    log_operation($id, 'チェック', $new_checked ? 'チェック済にした' : 'チェックを解除した');
    header('Location: item_detail.php?id=' . $id);
    exit;
}

// 一覧チェック（校舎絞り込みで複数一括）
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
$schools   = $pdo->query('SELECT * FROM schools ORDER BY id')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk'])) {
    $checked_ids = array_map('intval', $_POST['checked_ids'] ?? []);
    // 送信されたIDリストのみチェック済に、それ以外（同校舎）はチェック解除
    if ($school_id > 0) {
        $pdo->prepare('UPDATE items SET checked=0, checked_at=NULL, checked_by="" WHERE school_id=?')
            ->execute([$school_id]);
    }
    $now = date('Y-m-d H:i:s');
    foreach ($checked_ids as $cid) {
        $pdo->prepare('UPDATE items SET checked=1, checked_at=?, checked_by=? WHERE id=?')
            ->execute([$now, get_operator(), $cid]);
        log_operation($cid, 'チェック', 'チェック済にした');
    }
    header('Location: item_check.php?school_id=' . $school_id . '&saved=1');
    exit;
}

// 一覧取得
$where  = [];
$params = [];
if ($school_id > 0) {
    $where[]  = 'i.school_id = ?';
    $params[] = $school_id;
}
$sql = 'SELECT i.*, s.name AS school_name FROM items i
        JOIN schools s ON i.school_id = s.id
        WHERE i.is_disposed = 0'
    . ($where ? ' AND ' . implode(' AND ', $where) : '')
    . ' ORDER BY s.id, i.code';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

layout_head('在庫チェック');
layout_nav();
?>
<div class="max-w-5xl mx-auto px-4 py-6">
  <h2 class="text-xl font-bold mb-4">在庫チェック</h2>
  <?php if (isset($_GET['saved'])): ?>
  <p class="bg-green-100 text-green-700 rounded p-3 mb-4 text-sm">チェック内容を保存しました。</p>
  <?php endif; ?>

  <!-- 校舎絞り込み -->
  <form method="get" class="flex items-end gap-3 mb-6">
    <div>
      <label class="block text-xs font-medium mb-1">校舎で絞り込む</label>
      <select name="school_id" class="border rounded px-2 py-1 text-sm">
        <option value="0">すべて</option>
        <?php foreach ($schools as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $school_id == $s['id'] ? 'selected' : '' ?>>
          <?= h($s['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="bg-gray-600 text-white px-4 py-1 rounded text-sm hover:bg-gray-700">絞り込む</button>
  </form>

  <form method="post">
    <input type="hidden" name="bulk" value="1">
    <div class="mb-3 flex items-center gap-3">
      <button type="submit" class="bg-green-600 text-white px-4 py-1 rounded text-sm hover:bg-green-700">
        チェック状態を保存する
      </button>
      <span class="text-xs text-gray-500">チェックしたものが「チェック済」になります（同校舎のチェックはリセットされます）</span>
    </div>
    <div class="bg-white rounded shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-3 py-2 text-center w-10">
              <input type="checkbox" id="check_all" class="w-4 h-4">
            </th>
            <th class="px-3 py-2 text-left">番号</th>
            <th class="px-3 py-2 text-left">機器名</th>
            <th class="px-3 py-2 text-left">校舎</th>
            <th class="px-3 py-2 text-left">場所</th>
            <th class="px-3 py-2 text-left">最終チェック</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php foreach ($items as $item): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-3 py-2 text-center">
              <input type="checkbox" name="checked_ids[]" value="<?= $item['id'] ?>"
                     class="w-4 h-4" <?= $item['checked'] ? 'checked' : '' ?>>
            </td>
            <td class="px-3 py-2 font-mono"><?= h($item['code']) ?></td>
            <td class="px-3 py-2"><?= h($item['name']) ?></td>
            <td class="px-3 py-2"><?= h($item['school_name']) ?></td>
            <td class="px-3 py-2"><?= h($item['location']) ?></td>
            <td class="px-3 py-2 text-gray-400 text-xs">
              <?= $item['checked_at'] ? h($item['checked_at']) . ' ' . h($item['checked_by']) : '−' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>
</div>
<script>
document.getElementById('check_all').addEventListener('change', function() {
  document.querySelectorAll('input[name="checked_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
<?php layout_foot(); ?>
