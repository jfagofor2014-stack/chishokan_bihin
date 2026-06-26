<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_login();

$pdo = get_pdo();

// 検索条件の取得
$school_id   = isset($_GET['school_id'])   ? (int)$_GET['school_id']   : 0;
$category    = trim($_GET['category']    ?? '');
$keyword     = trim($_GET['keyword']     ?? '');
$is_disposed = $_GET['is_disposed'] ?? '';   // '' | '0' | '1'
$checked     = $_GET['checked']     ?? '';   // '' | '0' | '1'

// 校舎一覧取得
$schools = $pdo->query('SELECT * FROM schools ORDER BY id')->fetchAll();

// 備品一覧クエリ構築
$where  = [];
$params = [];

if ($school_id > 0) {
    $where[]  = 'i.school_id = ?';
    $params[] = $school_id;
}
if ($category !== '') {
    $where[]  = 'i.category = ?';
    $params[] = $category;
}
if ($keyword !== '') {
    $where[]  = '(i.name LIKE ? OR i.maker LIKE ? OR i.code LIKE ? OR i.serial LIKE ?)';
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($is_disposed === '0') {
    $where[] = 'i.is_disposed = 0';
} elseif ($is_disposed === '1') {
    $where[] = 'i.is_disposed = 1';
}
if ($checked === '0') {
    $where[] = 'i.checked = 0';
} elseif ($checked === '1') {
    $where[] = 'i.checked = 1';
}

$sql = 'SELECT i.*, s.name AS school_name FROM items i
        JOIN schools s ON i.school_id = s.id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY s.id, i.code';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// 種類一覧（絞り込み用）
$categories = $pdo->query('SELECT DISTINCT category FROM items WHERE category != "" ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

layout_head('備品一覧');
layout_nav();
?>
<div class="max-w-7xl mx-auto px-4 py-6">
  <h2 class="text-xl font-bold mb-4">備品一覧</h2>

  <!-- 検索フォーム -->
  <form method="get" class="bg-white rounded shadow p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
      <label class="block text-xs font-medium mb-1">校舎</label>
      <select name="school_id" class="border rounded px-2 py-1 text-sm">
        <option value="0">すべて</option>
        <?php foreach ($schools as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $school_id == $s['id'] ? 'selected' : '' ?>>
            <?= h($s['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-medium mb-1">種類</label>
      <select name="category" class="border rounded px-2 py-1 text-sm">
        <option value="">すべて</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= h($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= h($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-medium mb-1">キーワード（機器名・メーカー・番号・製造番号）</label>
      <input type="text" name="keyword" value="<?= h($keyword) ?>"
             class="border rounded px-2 py-1 text-sm w-48" placeholder="DELL, ノートPC, ...">
    </div>
    <div>
      <label class="block text-xs font-medium mb-1">廃棄</label>
      <select name="is_disposed" class="border rounded px-2 py-1 text-sm">
        <option value=""  <?= $is_disposed === ''  ? 'selected' : '' ?>>すべて</option>
        <option value="0" <?= $is_disposed === '0' ? 'selected' : '' ?>>未廃棄</option>
        <option value="1" <?= $is_disposed === '1' ? 'selected' : '' ?>>廃棄済</option>
      </select>
    </div>
    <div>
      <label class="block text-xs font-medium mb-1">チェック</label>
      <select name="checked" class="border rounded px-2 py-1 text-sm">
        <option value=""  <?= $checked === ''  ? 'selected' : '' ?>>すべて</option>
        <option value="1" <?= $checked === '1' ? 'selected' : '' ?>>済</option>
        <option value="0" <?= $checked === '0' ? 'selected' : '' ?>>未</option>
      </select>
    </div>
    <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded text-sm hover:bg-blue-700">検索</button>
    <a href="items.php" class="text-sm text-gray-500 hover:underline self-center">リセット</a>
  </form>

  <p class="text-sm text-gray-600 mb-2"><?= count($items) ?> 件</p>

  <!-- 備品テーブル -->
  <div class="bg-white rounded shadow overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-3 py-2 text-left">番号</th>
          <th class="px-3 py-2 text-left">機器名</th>
          <th class="px-3 py-2 text-left">メーカー</th>
          <th class="px-3 py-2 text-left">校舎</th>
          <th class="px-3 py-2 text-left">場所</th>
          <th class="px-3 py-2 text-left">購入日</th>
          <th class="px-3 py-2 text-center">廃棄</th>
          <th class="px-3 py-2 text-center">チェック</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php foreach ($items as $item): ?>
        <tr class="hover:bg-gray-50 <?= $item['is_disposed'] ? 'text-gray-400' : '' ?>">
          <td class="px-3 py-2 font-mono"><?= h($item['code']) ?></td>
          <td class="px-3 py-2"><?= h($item['name']) ?></td>
          <td class="px-3 py-2"><?= h($item['maker']) ?></td>
          <td class="px-3 py-2"><?= h($item['school_name']) ?></td>
          <td class="px-3 py-2"><?= h($item['location']) ?></td>
          <td class="px-3 py-2"><?= $item['purchased_at'] ? h($item['purchased_at']) : '' ?></td>
          <td class="px-3 py-2 text-center"><?= $item['is_disposed'] ? '✓' : '' ?></td>
          <td class="px-3 py-2 text-center">
            <?php if ($item['checked']): ?>
              <span class="text-green-600" title="<?= h($item['checked_by']) ?> (<?= h((string)$item['checked_at']) ?>)">✓</span>
            <?php endif; ?>
          </td>
          <td class="px-3 py-2">
            <a href="item_detail.php?id=<?= $item['id'] ?>" class="text-blue-600 hover:underline">詳細</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
        <tr><td colspan="9" class="px-3 py-6 text-center text-gray-400">該当する備品がありません</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout_foot(); ?>
