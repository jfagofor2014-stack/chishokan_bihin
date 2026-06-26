<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_login();

$pdo    = get_pdo();
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt   = $pdo->prepare('SELECT * FROM items WHERE id = ?');
$stmt->execute([$id]);
$item   = $stmt->fetch();
if (!$item) { header('Location: items.php'); exit; }

$schools = $pdo->query('SELECT * FROM schools ORDER BY id')->fetchAll();
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [
        'school_id'    => (int)($_POST['school_id'] ?? 0),
        'code'         => trim($_POST['code']         ?? ''),
        'category'     => trim($_POST['category']     ?? ''),
        'name'         => trim($_POST['name']         ?? ''),
        'maker'        => trim($_POST['maker']        ?? ''),
        'serial'       => trim($_POST['serial']       ?? ''),
        'purchased_at' => trim($_POST['purchased_at'] ?? ''),
        'location'     => trim($_POST['location']     ?? ''),
        'set_count'    => trim($_POST['set_count']    ?? '0'),
        'is_disposed'  => isset($_POST['is_disposed']) ? '1' : '0',
    ];

    if ($values['school_id'] === 0) $errors[] = '校舎を選択してください。';
    if ($values['name'] === '')     $errors[] = '機器名を入力してください。';

    if (empty($errors)) {
        $pdo->prepare(
            'UPDATE items SET school_id=?, code=?, category=?, name=?, maker=?, serial=?,
             purchased_at=?, location=?, set_count=?, is_disposed=? WHERE id=?'
        )->execute([
            $values['school_id'], $values['code'], $values['category'],
            $values['name'], $values['maker'], $values['serial'],
            $values['purchased_at'] ?: null, $values['location'],
            (int)$values['set_count'], $values['is_disposed'], $id,
        ]);
        log_operation($id, '編集', $values['name'] . ' を編集');
        header('Location: item_detail.php?id=' . $id);
        exit;
    }
    $item = array_merge($item, $values);
}

layout_head('備品編集');
layout_nav();

function field(string $label, string $name, string $type, $value, bool $required = false, array $options = []): void {
    $req = $required ? '<span class="text-red-500 ml-1">*</span>' : '';
    echo '<div>';
    echo '<label class="block text-sm font-medium mb-1">' . h($label) . $req . '</label>';
    if ($type === 'select') {
        echo '<select name="' . h($name) . '" class="w-full border rounded px-3 py-2 text-sm">';
        foreach ($options as $v => $l) {
            $sel = ((string)$value === (string)$v) ? ' selected' : '';
            echo '<option value="' . h((string)$v) . '"' . $sel . '>' . h($l) . '</option>';
        }
        echo '</select>';
    } elseif ($type === 'checkbox') {
        $chk = $value ? ' checked' : '';
        echo '<input type="checkbox" name="' . h($name) . '" value="1"' . $chk . ' class="w-4 h-4">';
    } else {
        echo '<input type="' . h($type) . '" name="' . h($name) . '" value="' . h((string)$value) . '"'
            . ($required ? ' required' : '')
            . ' class="w-full border rounded px-3 py-2 text-sm">';
    }
    echo '</div>';
}
?>
<div class="max-w-2xl mx-auto px-4 py-6">
  <h2 class="text-xl font-bold mb-6">備品編集</h2>
  <?php if ($errors): ?>
  <div class="bg-red-50 text-red-700 rounded p-3 mb-4 text-sm">
    <?php foreach ($errors as $e): ?><p><?= h($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <form method="post" class="bg-white rounded shadow p-6 space-y-4">
    <?php
    $school_options = [0 => '選択してください'];
    foreach ($schools as $s) $school_options[$s['id']] = $s['name'];

    field('校舎',           'school_id',    'select',   $item['school_id'],    true, $school_options);
    field('備品番号',       'code',         'text',     $item['code']);
    field('種類',           'category',     'text',     $item['category']);
    field('機器名',         'name',         'text',     $item['name'],         true);
    field('メーカー',       'maker',        'text',     $item['maker']);
    field('製造番号',       'serial',       'text',     $item['serial']);
    field('購入日',         'purchased_at', 'date',     $item['purchased_at'] ?? '');
    field('保管・使用場所', 'location',     'text',     $item['location']);
    field('セット備品数',   'set_count',    'number',   $item['set_count']);
    field('廃棄済',         'is_disposed',  'checkbox', (bool)$item['is_disposed']);
    ?>
    <div class="flex gap-3 pt-2">
      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">更新する</button>
      <a href="item_detail.php?id=<?= $id ?>" class="px-6 py-2 rounded border text-sm hover:bg-gray-50">キャンセル</a>
    </div>
  </form>
</div>
<?php layout_foot(); ?>
