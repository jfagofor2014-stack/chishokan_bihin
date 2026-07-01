<?php
require_once __DIR__ . '/auth.php';

function layout_head(string $title): void {
    echo '<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . h($title) . ' - 備品管理</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">';
}

function layout_nav(): void {
    $operator = h(get_operator());
    echo '
<nav class="bg-blue-700 text-white px-4 py-3 flex items-center justify-between">
  <a href="items.php" class="font-bold text-lg">備品管理</a>
  <div class="flex items-center gap-4 text-sm">
    <span>' . $operator . '</span>
    <a href="item_new.php" class="bg-white text-blue-700 px-3 py-1 rounded hover:bg-blue-50">＋新規登録</a>
    <a href="schools.php" class="hover:underline">校舎管理</a>
    <a href="import.php" class="hover:underline">CSVインポート</a>
    <a href="logout.php" class="hover:underline">ログアウト</a>
  </div>
</nav>';
}

function layout_foot(): void {
    echo '</body></html>';
}
