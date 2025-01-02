<?php
require_once __DIR__ . '/db.php';

$tableKey = $_GET['key'] ?? '';
if ($tableKey === '' || !tableExists($db, $tableKey)) {
    die("不正なアクセスです。テーブルが見つかりません。");
}

// ----------------------------------
// テーブル削除ボタン押下時の処理
// ----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_table'])) {
    $db->exec("DROP TABLE IF EXISTS {$tableKey}");
    header("Location: index.php");
    exit;
}

// -------------------------------------------------
// Saveボタン押下（既存レコード更新/削除, 新規レコード追加）
// -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_table'])) {
    // 既存レコード
    if (isset($_POST['existing'])) {
        foreach ($_POST['existing'] as $row) {
            $id            = $row['id']            ?? '';
            $section_type  = $row['section_type']  ?? '';
            $section_title = $row['section_title'] ?? '';
            $section_body  = $row['section_body']  ?? '';
            $delete_flag   = isset($row['delete']);

            if ($delete_flag) {
                $delStmt = $db->prepare("DELETE FROM {$tableKey} WHERE id=:id");
                $delStmt->bindValue(':id', $id, PDO::PARAM_INT);
                $delStmt->execute();
            } else {
                $updStmt = $db->prepare("UPDATE {$tableKey}
                                         SET section_type=:st, section_title=:ttl, section_body=:body
                                         WHERE id=:id");
                $updStmt->bindValue(':st',   $section_type,  PDO::PARAM_STR);
                $updStmt->bindValue(':ttl',  $section_title, PDO::PARAM_STR);
                $updStmt->bindValue(':body', $section_body,  PDO::PARAM_STR);
                $updStmt->bindValue(':id',   $id,            PDO::PARAM_INT);
                $updStmt->execute();
            }
        }
    }

    // 新規レコード
    if (!empty($_POST['new_section_type']) ||
        !empty($_POST['new_section_title']) ||
        !empty($_POST['new_section_body'])) {

        $new_section_type  = $_POST['new_section_type']  ?? '';
        $new_section_title = $_POST['new_section_title'] ?? '';
        $new_section_body  = $_POST['new_section_body']  ?? '';

        if ($new_section_type !== '' ||
            $new_section_title !== '' ||
            $new_section_body  !== '') {

            $insStmt = $db->prepare("INSERT INTO {$tableKey} (section_type, section_title, section_body)
                                     VALUES (:st, :ttl, :body)");
            $insStmt->bindValue(':st',   $new_section_type,  PDO::PARAM_STR);
            $insStmt->bindValue(':ttl',  $new_section_title, PDO::PARAM_STR);
            $insStmt->bindValue(':body', $new_section_body,  PDO::PARAM_STR);
            $insStmt->execute();
        }
    }
}

// ----------------------------------
// 編集フォーム用のレコード読み込み
// ----------------------------------
$stmt = $db->query("SELECT * FROM {$tableKey} ORDER BY id ASC");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>CFPable - Edit</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
  <svg width="200" height="60" viewBox="0 0 200 60" xmlns="http://www.w3.org/2000/svg">
  <!-- シンプルなアイコン（円＋四角の組み合わせなど） -->
  <circle cx="10" cy="30" r="10" fill="#0275d8"/>
  <rect x="25" y="20" width="10" height="20" fill="#0275d8"/>
</svg>
    <a class="navbar-brand" href="#">CFPable - Edit</a>
  </div>
</nav>

<div class="container mb-5">
  <div class="row">
    <div class="col">
      <h2 class="mb-3">Editing Table: <?php echo htmlspecialchars($tableKey, ENT_QUOTES); ?></h2>

  

      <!-- 編集テーブル -->
      <form method="post">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:4rem;">Delete</th>
              <th style="width:12rem;">Section Type</th>
              <th style="width:20rem;">Section Title</th>
              <th>Section Body</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td class="text-center">
                <input class="form-check-input" type="checkbox"
                       name="existing[<?php echo $r['id']; ?>][delete]" value="1">
              </td>
              <td>
                <input type="text" class="form-control"
                       name="existing[<?php echo $r['id']; ?>][section_type]"
                       value="<?php echo htmlspecialchars($r['section_type'], ENT_QUOTES); ?>">
              </td>
              <td>
                <input type="text" class="form-control"
                       name="existing[<?php echo $r['id']; ?>][section_title]"
                       value="<?php echo htmlspecialchars($r['section_title'], ENT_QUOTES); ?>">
              </td>
              <td>
                <textarea class="form-control" rows="3"
                          name="existing[<?php echo $r['id']; ?>][section_body]"><?php
                    echo htmlspecialchars($r['section_body'], ENT_QUOTES);
                ?></textarea>
              </td>
            </tr>
            <input type="hidden" name="existing[<?php echo $r['id']; ?>][id]" value="<?php echo $r['id']; ?>">
          <?php endforeach; ?>

          <!-- 新規入力行 -->
          <tr class="table-info">
            <td class="text-center">New</td>
            <td>
              <input type="text" class="form-control" name="new_section_type">
            </td>
            <td>
              <input type="text" class="form-control" name="new_section_title">
            </td>
            <td>
              <textarea class="form-control" rows="2" name="new_section_body"></textarea>
            </td>
          </tr>

          </tbody>
        </table>

        <div class="mb-3">
          <button type="submit" class="btn btn-primary">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#previewModal">
            Preview
          </button>
        </div>
      </form>
    </div>
    
  </div>
  <hr>
  <div class="row">
  <div class="col">
        <!-- テーブル削除ボタン -->
        <form method="post" class="mb-3">
        <input type="hidden" name="delete_table" value="1">
        <button type="submit" class="btn btn-danger"
                onclick="return confirm('このテーブルを削除しますか？ この操作は取り消せません。')">
          Delete This Table
        </button>
      </form>

      <!-- Create new CFPボタン -->
      <form action="index.php" class="mb-3">
        <button type="submit" class="btn btn-success">
          Create new CFP
        </button>
      </form>

      <!-- 現在のリンクコピー用ボタン -->
      <div class="mb-4">
        <button type="button" class="btn btn-info" id="copyUrlBtn">
          Copy This Page Link
        </button>
      </div>
      </div>
    </div>
</div>

<!-- Preview用モーダル -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">CFP Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
      </div>
      <div class="modal-body" id="previewContent">
        <?php
        $previewData = '';
        foreach ($records as $r) {
            $previewData .= "==== [{$r['section_type']}] {$r['section_title']} ====\n";
            $previewData .= $r['section_body'] . "\n\n";
        }
        echo nl2br(htmlspecialchars($previewData, ENT_QUOTES));
        ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="copyTxtBtn">Copy as Text</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="cfp.js"></script>
</body>
</html>
