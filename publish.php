<?php
require_once __DIR__  . '/crypto.php'; // 暗号化関数を読み込み
require_once __DIR__ . '/db.php';     // データベース接続
require_once __DIR__ . '/utils.php';
// manifest.json からバージョンを読み込み
$manifestPath = __DIR__ . '/manifest.json';
$manifestData = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
$version = $manifestData['version'] ?? 'unknown';

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
// 表示モードを取得（text or html）。デフォルトはtext
$disp = $_GET['disp'] ?? 'text';
// CFP文字列を生成
$cfp_string = generateCFPString($records);
// HTML表示用コンテンツ生成
$cfp_html = generateCFPHtml($records);

// APIリクエスト判定: GETパラメータ raw=true の場合のみ
$isApiRequest = (isset($_GET['raw']) && $_GET['raw'] === 'true');
if ($isApiRequest) {
    header('Access-Control-Allow-Origin: *');
    if ($disp === 'html') {
        header('Content-Type: text/html; charset=UTF-8');
        echo $cfp_html;
    } else {
        header('Content-Type: text/plain; charset=UTF-8');
        echo trim(strip_tags($cfp_string));
    }
    exit;
}

// プレビューの出力
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <meta property="og:title" content="CFP Publish">
    <meta property="og:description" content="Preview and publish your CFP data with ease.">
    <meta property="og:image" content="https://tetsuakibaba.jp/project/cfpable/opg.png">
    <meta property="og:url" content="https://tetsuakibaba.jp/project/cfpable/publish.php?token=<?php echo urlencode($token); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Call for Papers">
    <meta name="twitter:description" content="Preview and publish your CFP data with ease.">
    <meta name="twitter:image" content="https://tetsuakibaba.jp/project/cfpable/ogp.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.12.1/font/bootstrap-icons.min.css">
</head>
<title>CFP Publish</title>
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
        <div class="row mb-4">
            <div class="card">
                <div class="card-body">
                    <?php if ($disp === 'html'): ?>
                        <div id="cfpContent"><?php echo $cfp_html; ?></div>
                    <?php else: /* disp=text */ ?>
                        <pre id="cfpContent" style="white-space: pre-wrap;"><?php echo autoLink(trim(strip_tags($cfp_string))); ?></pre>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-grid gap-2 col-4 mx-auto">
                <button type="button" id="copyBtn" class="btn btn-outline-primary mt-2 mb-2">
                    <i class="bi bi-clipboard"></i>
                    <?php echo ($disp === 'html') ? 'Copy CFP as HTML' : 'Copy CFP as text'; ?>
                </button>
            </div>
            <div class="row mt-4">
                <footer class="text-center small text-muted">
                    <p>&copy; <?php echo date('Y'); ?> <a href="https://github.com/TetsuakiBaba/cfpable" target="_blank">CFPable</a> by <a href="https://github.com/TetsuakiBaba" target="_blank">Tetsuaki Baba</a> | Version: <?php echo htmlspecialchars($version, ENT_QUOTES); ?></p>
                </footer>
            </div>

        </div>


    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <script>
        // クリップボードコピー機能（dispモードに応じてHTMLかテキストをコピー）
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('copyBtn');
            const content = document.getElementById('cfpContent');
            const isHtmlMode = '<?php echo $disp; ?>' === 'html';
            btn.addEventListener('click', function() {
                const toCopy = isHtmlMode ? content.innerHTML : content.innerText;
                navigator.clipboard.writeText(toCopy).then(function() {
                    alert(isHtmlMode ? 'CFP HTML copied to clipboard!' : 'CFP text copied to clipboard!');
                });
            });
        });
    </script>
</body>

</html>