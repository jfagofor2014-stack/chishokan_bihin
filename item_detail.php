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

// 移動履歴
$logs = $pdo->prepare(
    'SELECT m.*, sf.name AS from_school_name, st.name AS to_school_name
     FROM move_logs m
     JOIN schools sf ON m.from_school_id = sf.id
     JOIN schools st ON m.to_school_id   = st.id
     WHERE m.item_id = ?
     ORDER BY m.moved_at DESC'
);
$logs->execute([$id]);
$move_logs = $logs->fetchAll();

// 操作ログ
$oplogs = $pdo->prepare(
    'SELECT * FROM operation_logs WHERE item_id = ? ORDER BY operated_at DESC LIMIT 20'
);
$oplogs->execute([$id]);
$op_logs = $oplogs->fetchAll();

// 写真
$photos_stmt = $pdo->prepare(
    'SELECT * FROM item_photos WHERE item_id = ? ORDER BY uploaded_at DESC'
);
$photos_stmt->execute([$id]);
$photos = $photos_stmt->fetchAll();

layout_head('備品詳細');
layout_nav();
?>
<div class="max-w-4xl mx-auto px-4 py-6">
  <div class="flex items-center gap-3 mb-6">
    <a href="items.php" class="text-blue-600 hover:underline text-sm">← 一覧へ戻る</a>
    <h2 class="text-xl font-bold"><?= h($item['name']) ?> <span class="text-gray-400 font-mono text-base"><?= h($item['code']) ?></span></h2>
  </div>

  <!-- 基本情報 -->
  <div class="bg-white rounded shadow p-5 mb-6">
    <div class="flex justify-between items-start mb-4">
      <h3 class="font-bold text-gray-700">基本情報</h3>
      <div class="flex gap-2">
        <a href="item_edit.php?id=<?= $item['id'] ?>"
           class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-200">編集</a>
        <a href="item_move.php?id=<?= $item['id'] ?>"
           class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">移動登録</a>
        <a href="item_check.php?id=<?= $item['id'] ?>"
           class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">在庫チェック</a>
      </div>
    </div>
    <dl class="grid grid-cols-2 gap-3 text-sm">
      <?php
      $fields = [
          '種類'           => $item['category'],
          'メーカー'       => $item['maker'],
          '製造番号'       => $item['serial'],
          '購入日'         => $item['purchased_at'] ?? '',
          '校舎'           => $item['school_name'],
          '保管・使用場所' => $item['location'],
          'セット備品数'   => (string)$item['set_count'],
          '廃棄'           => $item['is_disposed'] ? '廃棄済' : '−',
      ];
      foreach ($fields as $label => $val): ?>
      <div class="flex">
        <dt class="w-32 text-gray-500 flex-shrink-0"><?= h($label) ?></dt>
        <dd><?= h($val) ?></dd>
      </div>
      <?php endforeach; ?>
      <!-- チェック行: 個別出力（二重エスケープ防止） -->
      <div class="flex">
        <dt class="w-32 text-gray-500 flex-shrink-0">チェック</dt>
        <dd><?php if ($item['checked']): ?>済（<?= h($item['checked_by']) ?> / <?= h((string)$item['checked_at']) ?>）<?php else: ?>未<?php endif; ?></dd>
      </div>
    </dl>
  </div>

  <!-- 写真 -->
  <div class="bg-white rounded shadow p-5 mb-6">
    <div class="flex justify-between items-center mb-3">
      <h3 class="font-bold text-gray-700">写真（最新状態）</h3>
      <a href="item_photo_upload.php?id=<?= $item['id'] ?>"
         class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">＋写真を追加</a>
    </div>
    <?php if ($photos): ?>
    <div class="mb-4">
      <div class="relative inline-block">
        <img src="uploads/items/<?= (int)$item['id'] ?>/<?= h($photos[0]['filename']) ?>"
             class="max-w-full max-h-96 rounded border" alt="最新の写真">
        <form method="post" action="item_photo_delete.php"
              onsubmit="return confirm('この写真を削除しますか？');" class="absolute top-2 right-2">
          <input type="hidden" name="photo_id" value="<?= $photos[0]['id'] ?>">
          <button type="submit" class="bg-red-600 text-white text-xs px-2 py-1 rounded hover:bg-red-700">削除</button>
        </form>
      </div>
      <p class="text-xs text-gray-500 mt-1">
        <?= h((string)$photos[0]['uploaded_at']) ?> ・ <?= h($photos[0]['uploaded_by']) ?>
      </p>
    </div>
    <?php if (count($photos) > 1): ?>
    <p class="text-xs text-gray-500 mb-2">過去の写真</p>
    <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
      <?php foreach (array_slice($photos, 1) as $p): ?>
      <div class="relative">
        <img src="uploads/items/<?= (int)$item['id'] ?>/<?= h($p['filename']) ?>"
             class="w-full h-20 object-cover rounded border">
        <p class="text-[10px] text-gray-400 mt-0.5"><?= h(substr((string)$p['uploaded_at'], 0, 10)) ?></p>
        <form method="post" action="item_photo_delete.php"
              onsubmit="return confirm('この写真を削除しますか？');" class="absolute top-0 right-0">
          <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
          <button type="submit" class="bg-red-600 text-white text-xs w-5 h-5 rounded-full leading-none">×</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <p class="text-gray-400 text-sm">写真はまだありません。</p>
    <?php endif; ?>
  </div>

  <!-- 移動履歴 -->
  <div class="bg-white rounded shadow p-5 mb-6">
    <h3 class="font-bold text-gray-700 mb-3">移動履歴</h3>
    <?php if ($move_logs): ?>
    <table class="w-full text-sm">
      <thead class="text-gray-500 border-b">
        <tr>
          <th class="text-left py-1">日時</th>
          <th class="text-left py-1">移動元</th>
          <th class="text-left py-1">移動先</th>
          <th class="text-left py-1">担当</th>
          <th class="text-left py-1">備考</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php foreach ($move_logs as $log): ?>
        <tr>
          <td class="py-1"><?= h($log['moved_at']) ?></td>
          <td class="py-1"><?= h($log['from_school_name']) ?> / <?= h($log['from_location']) ?></td>
          <td class="py-1"><?= h($log['to_school_name']) ?> / <?= h($log['to_location']) ?></td>
          <td class="py-1"><?= h($log['moved_by']) ?></td>
          <td class="py-1 text-gray-400"><?= h((string)$log['note']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p class="text-gray-400 text-sm">移動履歴はありません。</p>
    <?php endif; ?>
  </div>

  <!-- 操作ログ -->
  <div class="bg-white rounded shadow p-5">
    <h3 class="font-bold text-gray-700 mb-3">操作ログ（直近20件）</h3>
    <?php if ($op_logs): ?>
    <table class="w-full text-sm">
      <thead class="text-gray-500 border-b">
        <tr>
          <th class="text-left py-1">日時</th>
          <th class="text-left py-1">操作</th>
          <th class="text-left py-1">担当</th>
          <th class="text-left py-1">内容</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php foreach ($op_logs as $log): ?>
        <tr>
          <td class="py-1"><?= h($log['operated_at']) ?></td>
          <td class="py-1"><?= h($log['action']) ?></td>
          <td class="py-1"><?= h($log['operator']) ?></td>
          <td class="py-1 text-gray-500"><?= h((string)$log['detail']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p class="text-gray-400 text-sm">操作ログはありません。</p>
    <?php endif; ?>
  </div>
</div>
<?php layout_foot(); ?>
