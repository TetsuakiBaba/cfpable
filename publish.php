<?php
require_once __DIR__  . '/crypto.php'; // 暗号化関数を読み込み
require_once __DIR__ . '/db.php';     // データベース接続
require_once __DIR__ . '/utils.php';

// トークンを取得
$token = $_GET['token'] ?? '';
if ($token === '') {
    die("Invalid token.");
}

// トークンを復号してテーブルIDを取得
$tableId = decryptTableId($token);
if ($tableId === null || !tableExists($db, $tableId)) {
    die("Invalid or expired token.");
}

// 該当テーブルからデータを取得
$stmt = $db->query("SELECT * FROM {$tableId} ORDER BY sort_order ASC, id ASC");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cfp_string = generateCFPString($records);

// プレビューの出力
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  </head>
    <title>CFP Publish</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
  <div class="container-fluid">
  <svg width="200" height="60" viewBox="0 0 200 60" xmlns="http://www.w3.org/2000/svg">
  <!-- シンプルなアイコン（円＋四角の組み合わせなど） -->
  <circle cx="10" cy="30" r="10" fill="#0275d8"/>
  <rect x="25" y="20" width="10" height="20" fill="#0275d8"/>
</svg>
    <a class="navbar-brand" href="#">CFPable</a>
  </div>
</nav>
    <div class="container">
        <div class="row mb-4">
            <div class="card">                
                <div class="card-body">
                    <pre style="white-space: pre-wrap;"><?php echo autoLink($cfp_string); ?></pre>
                </div>
            </div>
        <div>
        <div class="row mt-4">
            <footer class="text-center small text-muted">
            <p>&copy; 2025 <a href="https://github.com/TetsuakiBaba/cfpable" targe="_blank">CFPable</a> by <a href="https://github.com/TetsuakiBaba" target="_blank">Tetsuaki Baba</a></p>
            </footer>
        </div>
    
    </div>

    
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>
</html>
