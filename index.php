<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

start_session();

// すでにログイン済みなら一覧へ
if (!empty($_SESSION['operator'])) {
    header('Location: items.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $operator = trim($_POST['operator'] ?? '');
    if (hash_equals(APP_PASSWORD, $password) && $operator !== '') {
        session_regenerate_id(true);
        $_SESSION['operator']      = $operator;
        $_SESSION['last_activity'] = time();
        header('Location: items.php');
        exit;
    }
    $error = 'パスワードまたは名前が正しくありません。';
}

$timeout = isset($_GET['timeout']);
layout_head('ログイン');
?>
<div class="flex items-center justify-center min-h-screen">
  <div class="bg-white rounded-lg shadow p-8 w-full max-w-sm">
    <h1 class="text-2xl font-bold text-center mb-6">備品管理システム</h1>
    <?php if ($timeout): ?>
      <p class="text-yellow-700 bg-yellow-100 rounded p-2 mb-4 text-sm">セッションがタイムアウトしました。再度ログインしてください。</p>
    <?php endif; ?>
    <?php if ($error): ?>
      <p class="text-red-600 bg-red-50 rounded p-2 mb-4 text-sm"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post">
      <label class="block mb-1 text-sm font-medium">お名前</label>
      <input type="text" name="operator" required
             class="w-full border rounded px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-blue-400"
             placeholder="山田 太郎">
      <label class="block mb-1 text-sm font-medium">パスワード</label>
      <input type="password" name="password" required
             class="w-full border rounded px-3 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-blue-400">
      <button type="submit"
              class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-medium">
        ログイン
      </button>
    </form>
  </div>
</div>
<?php layout_foot(); ?>
