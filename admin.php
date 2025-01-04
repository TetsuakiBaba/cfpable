<?php
session_start(); // セッションを利用

require_once __DIR__ . '/db.php';

// 簡易パスワード（サンプル用）
define('ADMIN_PASS', 'EwXluyrtTxx+');

// すでにログイン済みの場合
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // ここにテーブル一覧取得ロジックを配置
    try {
        $stmt = $db->query("
            SELECT name 
            FROM sqlite_master 
            WHERE type='table'
              AND name NOT LIKE 'sqlite_%'
            ORDER BY name ASC
        ");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        die("テーブル一覧を取得できませんでした: " . $e->getMessage());
    }
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
      <meta charset="UTF-8">
      <title>CFPable - Admin</title>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
      <div class="container-fluid">
        <a class="navbar-brand" href="#">CFPable - Admin</a>
        <div class="d-flex">
          <!-- ログアウトボタン -->
          <form action="admin.php" method="post" class="m-0">
            <input type="hidden" name="logout" value="1">
            <button type="submit" class="btn btn-outline-light">Logout</button>
          </form>
        </div>
      </div>
    </nav>

    <div class="container">
      <h1 class="mb-4">CFPable Admin Page</h1>

      <!-- Create new CFP へのリンク（任意）-->
      <div class="mb-3">
        <a href="index.php" class="btn btn-success">Create new CFP</a>
      </div>

      <?php if (empty($tables)): ?>
        <div class="alert alert-info">登録されたテーブルがありません。</div>
      <?php else: ?>
        <table class="table table-bordered">
          <thead class="table-light">
            <tr>
              <th>Table Name</th>
              <th style="width:10rem;">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($tables as $tableName): ?>
            <tr>
              <td><?php echo htmlspecialchars($tableName, ENT_QUOTES); ?></td>
              <td>
                <!-- edit.phpへのリンク -->
                <a href="edit.php?key=<?php echo urlencode($tableName); ?>" class="btn btn-sm btn-primary">
                  Edit
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>

    <?php
    // ログイン済みの場合の出力はここまでで終了
    exit;
}

// ---------------------------------------------
// まだログインしていない or ログアウト処理
// ---------------------------------------------

// ログアウト処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $_SESSION['logged_in'] = false;
    session_destroy();
    header("Location: admin.php");
    exit;
}

// ログインフォームの送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $inputPass = $_POST['password'];
    if ($inputPass === ADMIN_PASS) {
        // パスワード一致 → ログイン成功
        $_SESSION['logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        // パスワードが違う → エラーメッセージ
        $error = "パスワードが違います。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>CFPable - Admin Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex align-items-center" style="min-height: 100vh; background-color: #f7f7f7;">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-4 bg-white p-4 shadow-sm rounded">
      <h2 class="mb-4 text-center">Admin Login</h2>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
      <?php endif; ?>
      <form action="admin.php" method="post">
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
