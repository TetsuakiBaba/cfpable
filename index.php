<?php
require_once __DIR__ . '/db.php';
// 1. manifest.json を読み込み、配列に変換
$manifestPath = __DIR__ . '/manifest.json';
$manifestData = [];
if (file_exists($manifestPath)) {
  $json = file_get_contents($manifestPath);
  $manifestData = json_decode($json, true) ?? [];
}

// 2. バージョンを取得（未設定の場合は 'unknown'）
$version = $manifestData['version'] ?? 'unknown';

?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CFPable - Top</title>
  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

  <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
    <div class="container-fluid">
      <svg width="200" height="60" viewBox="0 0 200 60" xmlns="http://www.w3.org/2000/svg">
        <!-- シンプルなアイコン（円＋四角の組み合わせなど） -->
        <circle cx="10" cy="30" r="10" fill="#0275d8" />
        <rect x="25" y="20" width="10" height="20" fill="#0275d8" />
      </svg>
      <a class="navbar-brand" href="#">CFPable</a>
    </div>
  </nav>

  <div class="container">



    <h1 class="mb-3">CFPable - Top</h1>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $cfpName = trim($_POST['cfp_name'] ?? '');
      if ($cfpName === '') {
        echo "<div class='alert alert-danger'>カンファレンス名を入力してください。</div>";
      } else {
        $uniqueTableName = 'table_' . uniqid();
        createCFPTable($db, $uniqueTableName);

        $stmt = $db->prepare("INSERT INTO {$uniqueTableName} (conf_name, section_type, section_title, section_body)
                                  VALUES (:cn, 'introduction', 'Conference Name', :body)");
        $stmt->bindValue(':cn', $cfpName, PDO::PARAM_STR);
        $stmt->bindValue(':body', $cfpName, PDO::PARAM_STR);
        $stmt->execute();


        echo "<div class='alert alert-success'>テーブルを作成しました。{$uniqueTableName}へ リダイレクトします...</div>";
        // PHPで処理を行う
        $uniqueTableName = "{$uniqueTableName}";
        echo '<script>';
        echo 'window.location.href = "edit.php?key=' . htmlspecialchars($uniqueTableName, ENT_QUOTES) . '";';
        echo '</script>';
        exit;
      }
    }
    ?>

    <form method="post" class="row g-3">
      <div class="col-auto">
        <label for="cfp_name" class="visually-hidden">カンファレンス名</label>
        <input type="text" class="form-control" name="cfp_name" id="cfp_name" placeholder="Enter Conference Name" required>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary">Begin</button>
      </div>
    </form>

    <!-- フッターでコピーライトとバージョンを表示 -->
    <footer style="margin-top: 2em; border-top: 1px solid #ccc; padding-top: 1em;">
      <div class="text-center">
        &copy; <?php echo date('Y'); ?> CFPable | Version: <?php echo htmlspecialchars($version, ENT_QUOTES); ?>
      </div>
    </footer>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>