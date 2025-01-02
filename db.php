<?php
// db.php
try {
    $db = new PDO('sqlite:' . __DIR__ . '/cfpable.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

function createCFPTable(PDO $db, string $tableName): void {
    $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        conf_name TEXT,
        section_type TEXT,
        section_title TEXT,
        section_body TEXT,
        note TEXT
    );";
    $db->exec($sql);
}

function tableExists(PDO $db, string $tableName): bool {
    $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:name");
    $stmt->bindValue(':name', $tableName, PDO::PARAM_STR);
    $stmt->execute();
    return (bool)$stmt->fetch();
}
