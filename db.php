<?php
// db.php

// データベースファイル名は任意。ここでは "cfpable.db" としている。
$dbFile = __DIR__ . '/cfpable.db';

try {
    // PDO で SQLite に接続
    $db = new PDO('sqlite:' . $dbFile);
    // エラー発生時に例外を投げるように設定
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

/**
 * 指定したテーブル名が存在するかどうかをチェック
 *
 * @param PDO    $db
 * @param string $tableName
 * @return bool  テーブルが存在すれば true, なければ false
 */
function tableExists(PDO $db, string $tableName): bool {
    $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=:name LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':name', $tableName, PDO::PARAM_STR);
    $stmt->execute();
    return (bool)$stmt->fetch();
}

/**
 * CFPテーブルを作成するサンプル関数
 * 例: createCFPTable($db, 'table_XXXX');
 *
 * @param PDO    $db
 * @param string $tableName
 * @return void
 */
function createCFPTable(PDO $db, string $tableName): void {
    // 既に存在しない場合のみ作成
    $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        conf_name TEXT,
        section_type TEXT,
        section_title TEXT,
        section_body TEXT,
        note TEXT,
        sort_order INTEGER DEFAULT 0
    )";
    $db->exec($sql);
}
