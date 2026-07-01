<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_login();

$pdo    = get_pdo();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $errors[] = '校舎名を入力してください。';
        } else {
            $pdo->prepare('INSERT INTO schools (name) VALUES (?)')->execute([$name]);
            log_operation(null, '校舎追加', $name . ' を追加');
            header('Location: schools.php');
            exit;
        }
    } elseif ($action === 'update') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $errors[] = '校舎名を入力してください。';
        } else {
            $pdo->prepare('UPDATE schools SET name = ? WHERE id = ?')->execute([$name, $id]);
            log_operation(null, '校舎編集', $name . ' に変更（id=' . $id . '）');
            header('Location: schools.php');
            exit;
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE school_id = ?');
        $count_stmt->execute([$id]);

        if ((int)$count_stmt->fetchColumn() > 0) {
            $errors[] = 'この校舎には備品が登録されているため削除できません。先に備品を他の校舎へ移動してください。';
        } else {
            $name_stmt = $pdo->prepare('SELECT name FROM schools WHERE id = ?');
            $name_stmt->execute([$id]);
            $name = $name_stmt->fetchColumn();

            $pdo->prepare('DELETE FROM schools WHERE id = ?')->execute([$id]);
            log_operation(null, '校舎削除', ($name !== false ? $name : ('id=' . $id)) . ' を削除');
            header('Location: schools.php');
            exit;
        }
    }
}

$schools = $pdo->query(
    'SELECT s.*, COUNT(i.id) AS item_count
     FROM schools s
     LEFT JOIN items i ON i.school_id = s.id
     GROUP BY s.id
     ORDER BY s.id'
)->fetchAll();

layout_head('校舎管理');
layout_nav();
?>
<div class="max-w-3xl mx-auto px-4 py-6">
  <h2 class="text-xl font-bold mb-6">校舎管理</h2>

  <?php if ($errors): ?>
  <div class="bg-red-50 text-red-700 rounded p-3 mb-4 text-sm">
    <?php foreach ($errors as $e): ?><p><?= h($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="bg-white rounded shadow mb-6 overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-3 py-2 text-left">校舎名</th>
          <th class="px-3 py-2 text-center">備品数</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php foreach ($schools as $s): ?>
        <tr>
          <td class="px-3 py-2">
            <form method="post" class="flex gap-2">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= $s['id'] ?>">
              <input type="text" name="name" value="<?= h($s['name']) ?>" required
                     class="border rounded px-2 py-1 text-sm w-full">
              <button type="submit"
                      class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 flex-shrink-0">保存</button>
            </form>
          </td>
          <td class="px-3 py-2 text-center"><?= (int)$s['item_count'] ?></td>
          <td class="px-3 py-2 text-right whitespace-nowrap">
            <form method="post" onsubmit="return confirm('「<?= h($s['name']) ?>」を削除しますか？');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $s['id'] ?>">
              <button type="submit" <?= (int)$s['item_count'] > 0 ? 'disabled title="備品が登録されているため削除できません"' : '' ?>
                class="px-3 py-1 rounded text-xs border <?= (int)$s['item_count'] > 0 ? 'text-gray-300 border-gray-200 cursor-not-allowed' : 'text-red-600 border-red-300 hover:bg-red-50' ?>">削除</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($schools)): ?>
        <tr><td colspan="3" class="px-3 py-6 text-center text-gray-400">校舎がありません</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="bg-white rounded shadow p-5">
    <h3 class="font-bold text-gray-700 mb-3">校舎を追加</h3>
    <form method="post" class="flex gap-3">
      <input type="hidden" name="action" value="add">
      <input type="text" name="name" required placeholder="校舎名"
             class="border rounded px-3 py-2 text-sm flex-1">
      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">追加</button>
    </form>
  </div>
</div>
<?php layout_foot(); ?>
