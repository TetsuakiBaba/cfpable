<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/utils.php';

// 1) keyパラメータ取得 & テーブル存在チェック
$tableKey = $_GET['key'] ?? '';
if ($tableKey === '' || !tableExists($db, $tableKey)) {
    die("不正なアクセスです。テーブルが見つかりません。");
}

// 暗号化トークンを生成
$encryptedToken = encryptTableId($tableKey);

// 公開用URLを生成
// サーバーのプロトコルとホストを取得
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST']; // ドメイン名（例: yourdomain.com）

// 完全な公開用URLを生成
$publishUrl = $protocol . $host . "/publish.php?token=" . urlencode($encryptedToken);


/**
 * ----------------------------------------------------------------
 * (A) Ajax: 並び替え処理 (Drag & Dropでソート順を更新)
 *     fetch('edit.php?key=xxx&ajax=sort') で JSONを受け取り、sort_orderを更新
 * ----------------------------------------------------------------
 */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'sort') {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    $orderArr = $data['order'] ?? [];

    if (!is_array($orderArr)) {
        echo "invalid data";
        exit;
    }

    // 並び順に応じて sort_order=0,1,2... と更新
    try {
        $sortVal = 0;
        foreach ($orderArr as $id) {
            $id = (int)$id;
            $stmt = $db->prepare("UPDATE {$tableKey} SET sort_order=:s WHERE id=:id");
            $stmt->bindValue(':s',  $sortVal, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id,       PDO::PARAM_INT);
            $stmt->execute();
            $sortVal++;
        }
        echo "OK";
    } catch (Exception $e) {
        echo "error: " . $e->getMessage();
    }
    exit;
}

/**
 * ----------------------------------------------------------------
 * (B) テーブル削除ボタン
 * ----------------------------------------------------------------
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_table'])) {
    $db->exec("DROP TABLE IF EXISTS {$tableKey}");
    header("Location: index.php");
    exit;
}

/**
 * ----------------------------------------------------------------
 * (C) “このCFPを複製” ボタン押下
 * ----------------------------------------------------------------
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clone_cfp'])) {
    // 1) 新しいユニークテーブル名を生成
    $newTableKey = 'table_' . uniqid();

    // 2) テーブル構造を作成 (元と同一のカラム構成)
    $sqlCreate = "
        CREATE TABLE IF NOT EXISTS {$newTableKey} (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            conf_name TEXT,
            section_type TEXT,
            section_title TEXT,
            section_body TEXT,
            note TEXT,
            sort_order INTEGER DEFAULT 0
        );
    ";
    $db->exec($sqlCreate);

    // 3) 元テーブルから全レコード取得
    $stmtOld = $db->query("SELECT * FROM {$tableKey} ORDER BY sort_order ASC, id ASC");
    $oldRecords = $stmtOld->fetchAll(PDO::FETCH_ASSOC);

    // 4) 新テーブルにINSERT
    $insertStmt = $db->prepare("
        INSERT INTO {$newTableKey}
          (conf_name, section_type, section_title, section_body, note, sort_order)
        VALUES (:cn, :st, :ttl, :body, :nt, :sort)
    ");
    foreach ($oldRecords as $rec) {
        $insertStmt->execute([
            ':cn'   => $rec['conf_name']     ?? '',
            ':st'   => $rec['section_type']  ?? '',
            ':ttl'  => $rec['section_title'] ?? '',
            ':body' => $rec['section_body']  ?? '',
            ':nt'   => $rec['note']          ?? '',
            ':sort' => $rec['sort_order']    ?? 0,
        ]);
    }

    // 5) 新テーブルのeditページへリダイレクト
    header("Location: edit.php?key={$newTableKey}");
    exit;
}

/**
 * ----------------------------------------------------------------
 * (D) Saveボタン押下 (既存レコード編集・削除 + 新規レコード追加)
 * ----------------------------------------------------------------
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_POST['delete_table'])
    && !isset($_POST['clone_cfp']))
{
    // 既存レコード
    if (isset($_POST['existing'])) {
        foreach ($_POST['existing'] as $row) {
            $id            = $row['id']            ?? '';
            $section_type  = $row['section_type']  ?? '';
            $section_title = $row['section_title'] ?? '';
            $section_body  = $row['section_body']  ?? '';
            $delete_flag   = isset($row['delete']);

            if ($delete_flag) {
                // レコード削除
                $delStmt = $db->prepare("DELETE FROM {$tableKey} WHERE id=:id");
                $delStmt->bindValue(':id', $id, PDO::PARAM_INT);
                $delStmt->execute();
            } else {
                // レコード更新
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
        !empty($_POST['new_section_body']))
    {
        $new_section_type  = $_POST['new_section_type']  ?? '';
        $new_section_title = $_POST['new_section_title'] ?? '';
        $new_section_body  = $_POST['new_section_body']  ?? '';

        if ($new_section_type !== '' ||
            $new_section_title !== '' ||
            $new_section_body  !== '')
        {
            // 既存の最大sort_orderを求め、末尾に配置
            $maxSort = $db->query("SELECT MAX(sort_order) FROM {$tableKey}")->fetchColumn();
            $maxSort = ($maxSort === null) ? 0 : (int)$maxSort + 1;

            $insStmt = $db->prepare("
                INSERT INTO {$tableKey}
                  (section_type, section_title, section_body, sort_order)
                VALUES (:st, :ttl, :body, :sorder)
            ");
            $insStmt->bindValue(':st',     $new_section_type,  PDO::PARAM_STR);
            $insStmt->bindValue(':ttl',    $new_section_title, PDO::PARAM_STR);
            $insStmt->bindValue(':body',   $new_section_body,  PDO::PARAM_STR);
            $insStmt->bindValue(':sorder', $maxSort,           PDO::PARAM_INT);
            $insStmt->execute();
        }
    }
}

// ----------------------------------------------------------
// (E) DBからレコードを並び順（sort_order, id）で取得
// ----------------------------------------------------------
$stmt = $db->query("SELECT * FROM {$tableKey} ORDER BY sort_order ASC, id ASC");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CFPable - Edit</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    /* ドラッグ中の行を半透明にする */
    tr.dragging {
      opacity: 0.5;
    }
    /* ドロップ位置ガイド */
    tr.drop-above {
      border-top: 2px solid #0d6efd;
    }
    tr.drop-below {
      border-bottom: 2px solid #0d6efd;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">CFPable - Edit</a>
  </div>
</nav>

<div class="container mb-5">
  <h2 class="mb-3">Editing Table: 
    <span class="text-primary"><?php echo htmlspecialchars($tableKey, ENT_QUOTES); ?></span>
  </h2>

  

  <!-- セクション編集フォーム -->
  <form method="post">
    <table class="table table-bordered align-middle" id="sortable-table">
      <thead class="table-light">
        <tr>
          <th style="width:5%;">Delete</th>
          <th style="width:5%;">Drag</th>
          <th style="width:20%;">Section Type</th>
          <th style="width:20%;">Section Title</th>
          <th style="width:50%;">Section Body</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r): ?>
        <tr draggable="true"  data-id="<?php echo $r['id']; ?>">
          <!-- Delete -->
          <td class="text-center">
            <input class="form-check-input" type="checkbox"
                   name="existing[<?php echo $r['id']; ?>][delete]" value="1">
          </td>
          <!-- Drag handle -->
          <td class="text-center"style="cursor: move;">&#9776;</td>

          <!-- Section Type -->
          <td>
            <input type="text" class="form-control"
                   name="existing[<?php echo $r['id']; ?>][section_type]"
                   value="<?php echo htmlspecialchars($r['section_type'], ENT_QUOTES); ?>">
          </td>

          <!-- Section Title -->
          <td>
            <input type="text" class="form-control"
                   name="existing[<?php echo $r['id']; ?>][section_title]"
                   value="<?php echo htmlspecialchars($r['section_title'], ENT_QUOTES); ?>">
          </td>

          <!-- Section Body -->
          <td>
            <textarea class="form-control" rows="3"
                      name="existing[<?php echo $r['id']; ?>][section_body]"><?php
               echo htmlspecialchars($r['section_body'], ENT_QUOTES);
            ?></textarea>
          </td>
        </tr>
        <!-- hiddenでIDを持たせる -->
        <input type="hidden" name="existing[<?php echo $r['id']; ?>][id]" value="<?php echo $r['id']; ?>">
        <?php endforeach; ?>

        <!-- 新規作成行 -->
        <tr class="table-info">
          <td class="text-center">New</td>
          <td></td>
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
      <button type="submit" class="btn btn-success"><i class="bi bi-floppy2"></i> Apply</button>
      <!-- Previewボタン（モーダル表示） -->
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#previewModal">
      <i class="bi bi-eye"></i> Preview
      </button>      
    </div>
  </form>

  <hr>
  <div class="row">
  <div class="input-group mb-2">
    <!-- 新しいCFP作成 (index.phpへ) -->
    <form action="index.php" class="me-2  mb-2">
      <button type="submit" class="btn btn-secondary">
      <i class="bi bi-cloud-plus"></i> Create new CFP
      </button>
    </form>
    
  

    <!-- (C) このCFPを複製 -->
    <form method="post" class="me-2 mb-2">
      <input type="hidden" name="clone_cfp" value="1">
      <button type="submit" class="btn btn-secondary"
              onclick="return confirm('このCFPを複製しますか？')">
              <i class="bi bi-copy"></i> Duplicate This CFP
      </button>
    </form>

    <!-- 現在のリンクをコピー -->
    <div class="me-2  mb-2">
      <button type="button" class="btn btn-secondary" id="copyUrlBtn">
      <i class="bi bi-share"></i> Copy This Page Link
      </button>
    </div>
       

         <!-- (B) テーブル削除 -->
    <form method="post" class="me-2 mb-2">
      <input type="hidden" name="delete_table" value="1">
      <button type="submit" class="btn btn-danger"
              onclick="return confirm('本当にこのテーブルを削除しますか？')">
              <i class="bi bi-trash"></i> Delete This Table
      </button>
    </form>
    </div>
        </div>
</div>

<!-- Preview用モーダル -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">CFP Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
      </div>
      <div class="modal-body" id="previewContent">
        <pre style="white-space:pre-wrap"><?php
        // プレビュー用テキストを一括生成
        $previewData = generateCFPString($records);        
        echo htmlspecialchars($previewData, ENT_QUOTES);
        // echo $previewData;
        ?>
        </pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="copyTxtBtn">
        <i class="bi bi-clipboard"></i> <span id="button_copy_as_text">Copy as Text</span>
        </button>
        <button type="button" class="btn btn-success">
        <i class="bi bi-link"></i> <span id="button_copy_link">Copy Link</span>
        <script>
          document.getElementById('button_copy_link').addEventListener('click', () => {
            const currentUrl = window.location.href;
            <?php echo "const url = '{$publishUrl}';"?>
            navigator.clipboard.writeText(url)
              .then(() => {
                document.getElementById('button_copy_link').textContent = 'Copied!';
                setTimeout(() => {
                  document.getElementById('button_copy_link').textContent = 'Copy Link';
                }, 1000);
              })
              .catch(err => {
                
              });
          });
          </script>
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // 1) Previewテキストコピー
  const copyTxtBtn = document.getElementById('copyTxtBtn');
  const previewContent = document.getElementById('previewContent');
  if (copyTxtBtn && previewContent) {
    copyTxtBtn.addEventListener('click', () => {
      const textToCopy = previewContent.innerText+"\n\nCreated by cfpable: https://github.com/TetsuakiBaba/cfpable\n";


      navigator.clipboard.writeText(textToCopy)
      const copy_button = document.getElementById('button_copy_as_text');
      navigator.clipboard.writeText(textToCopy)
        .then(() => {
          // copy as textのボタンを1秒間 Copied に変更
          copy_button.textContent = 'Copied!';
          setTimeout(() => {
            copy_button.textContent = 'Copy as Text';
          }, 1000);

        })
        .catch(err => {
          // copy as textのボタンを1秒間 Copied に変更
          copy_button.textContent = 'Copy failed';
          setTimeout(() => {
            copy_button.textContent = 'Copy as Text';
          }, 1000);
          
        });
    });
  }

  // 2) 現在のページURLをコピー
  const copyUrlBtn = document.getElementById('copyUrlBtn');
  if (copyUrlBtn) {
    copyUrlBtn.addEventListener('click', () => {
      const currentUrl = window.location.href;
      navigator.clipboard.writeText(currentUrl)
        .then(() => {
          alert('URLをコピーしました！');
        })
        .catch(err => {
          console.error('URLコピー失敗:', err);
        });
    });
  }

  // 3) Drag & Drop で行を並び替え
  initDragAndDrop();
});

function initDragAndDrop() {
  const tableBody = document.querySelector('#sortable-table tbody');
  if (!tableBody) return;

  let dragSrcRow = null;

  // tbody内すべての<tr>にドラッグイベントを付与
  tableBody.querySelectorAll('tr').forEach(row => {
    row.addEventListener('dragstart', handleDragStart);
    row.addEventListener('dragend', handleDragEnd);
    row.addEventListener('dragover', handleDragOver);
    row.addEventListener('dragleave', handleDragLeave);
    row.addEventListener('drop', handleDrop);
  });

  function handleDragStart(e) {
    dragSrcRow = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    // Firefox等で必須
    e.dataTransfer.setData('text/plain', this.dataset.id);
  }

  function handleDragEnd(e) {
    this.classList.remove('dragging');
    tableBody.querySelectorAll('tr').forEach(r => {
      r.classList.remove('drop-above', 'drop-below');
    });
    dragSrcRow = null;
  }

  function handleDragOver(e) {
    e.preventDefault();
    if (!dragSrcRow || dragSrcRow === this) return;

    const rect = this.getBoundingClientRect();
    const offset = e.clientY - rect.top;
    const half = rect.height / 2;

    this.classList.remove('drop-above', 'drop-below');
    if (offset < half) {
      this.classList.add('drop-above');
    } else {
      this.classList.add('drop-below');
    }
  }

  function handleDragLeave(e) {
    this.classList.remove('drop-above', 'drop-below');
  }

  function handleDrop(e) {
    e.preventDefault();
    if (!dragSrcRow || dragSrcRow === this) return;

    const rect = this.getBoundingClientRect();
    const offset = e.clientY - rect.top;
    const half = rect.height / 2;

    if (offset < half) {
      tableBody.insertBefore(dragSrcRow, this);
    } else {
      tableBody.insertBefore(dragSrcRow, this.nextSibling);
    }
    this.classList.remove('drop-above', 'drop-below');
    dragSrcRow.classList.remove('dragging');

    // 並び替え結果をサーバーに送信
    updateSortOrder();
  }

  // 新しい順序をサーバーに送って sort_order を更新
  function updateSortOrder() {
    const rows = tableBody.querySelectorAll('tr');
    const order = Array.from(rows).map(r => r.dataset.id);

    // 同じ edit.php に対して ?ajax=sort を付けてPOSTする
    fetch(location.pathname + '?key=<?php echo urlencode($tableKey); ?>&ajax=sort', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order })
    })
    .then(res => res.text())
    .then(text => {
      console.log('Sort update response:', text);
    })
    .catch(err => {
      console.error('Sort update error:', err);
    });
  }
}
</script>

</body>
</html>
