<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_login();

$pdo     = get_pdo();
$schools = $pdo->query('SELECT * FROM schools ORDER BY id')->fetchAll();
$result  = null;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
    $school_id = (int)($_POST['school_id'] ?? 0);
    if ($school_id === 0) {
        $errors[] = 'インポート先の校舎を選択してください。';
    } elseif ($_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'ファイルのアップロードに失敗しました。';
    } else {
        $file    = $_FILES['csv']['tmp_name'];
        $handle  = fopen($file, 'r');
        $headers = fgetcsv($handle);
        // BOM除去
        if ($headers) {
            $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
        }

        $count   = 0;
        $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) { $skipped++; continue; }
            $data = array_combine($headers, $row);

            // 廃棄フラグの変換
            $is_disposed = 0;
            if (isset($data['廃棄'])) {
                $val = strtolower(trim($data['廃棄']));
                $is_disposed = in_array($val, ['true', '1', 'yes', '廃棄'], true) ? 1 : 0;
            }

            // 購入日の変換（2024.9.7 → 2024-09-07）
            $purchased_at = null;
            if (!empty($data['購入日'])) {
                $d = preg_replace('/[.\\/]/', '-', trim($data['購入日']));
                $dt = date_create($d);
                if ($dt) $purchased_at = date_format($dt, 'Y-m-d');
            }

            $pdo->prepare(
                'INSERT INTO items (school_id, code, category, name, maker, serial, purchased_at, location, set_count, is_disposed)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $school_id,
                $data['番号']           ?? '',
                $data['種類']           ?? '',
                $data['機器名']         ?? '',
                $data['メーカー名']     ?? '',
                $data['製造番号']       ?? '',
                $purchased_at,
                $data['保管・使用場所'] ?? '',
                (int)($data['セット備品数'] ?? 0),
                $is_disposed,
            ]);
            $count++;
        }
        fclose($handle);
        log_operation(null, 'CSVインポート', $count . '件インポート（校舎ID:' . $school_id . '）');
        $result = ['count' => $count, 'skipped' => $skipped];
    }
}

layout_head('CSVインポート');
layout_nav();
?>
<div class="max-w-xl mx-auto px-4 py-6">
  <h2 class="text-xl font-bold mb-2">CSVインポート</h2>
  <p class="text-sm text-gray-500 mb-6">
    GoogleスプレッドシートをCSV形式でダウンロードして、こちらからインポートしてください。
  </p>

  <?php if ($result): ?>
  <div class="bg-green-50 text-green-700 rounded p-4 mb-6 text-sm">
    <p class="font-medium">インポート完了</p>
    <p><?= (int)$result['count'] ?> 件のデータをインポートしました。</p>
    <?php if ($result['skipped'] > 0): ?>
    <p class="text-yellow-700"><?= (int)$result['skipped'] ?> 件をスキップしました（列数不一致）。</p>
    <?php endif; ?>
    <a href="items.php" class="underline mt-2 inline-block">備品一覧を確認する</a>
  </div>
  <?php endif; ?>

  <?php if ($errors): ?>
  <div class="bg-red-50 text-red-700 rounded p-3 mb-4 text-sm">
    <?php foreach ($errors as $e): ?><p><?= h($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="bg-white rounded shadow p-6 space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">インポート先校舎 <span class="text-red-500">*</span></label>
      <select name="school_id" required class="w-full border rounded px-3 py-2 text-sm">
        <option value="0">選択してください</option>
        <?php foreach ($schools as $s): ?>
        <option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">CSVファイル <span class="text-red-500">*</span></label>
      <input type="file" name="csv" accept=".csv" required
             class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div class="text-xs text-gray-500 bg-gray-50 rounded p-3">
      <p class="font-medium mb-1">CSVの列名（必須）：</p>
      <p>番号、種類、機器名、メーカー名、製造番号、購入日、保管・使用場所、セット備品数、廃棄</p>
      <p class="mt-1">※ 文字コードはUTF-8またはBOM付きUTF-8に対応しています。</p>
    </div>
    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">
      インポートする
    </button>
  </form>
</div>
<?php layout_foot(); ?>
