<?php
// keys.php を読み込み
require_once __DIR__ . '/keys.php';

/**
 * テーブルIDを暗号化
 * @param string $tableId
 * @return string 暗号化されたトークン
 */
function encryptTableId(string $tableId): string {
    $encrypted = openssl_encrypt(
        $tableId,                 // 暗号化対象
        'AES-256-CBC',            // 暗号化アルゴリズム
        ENCRYPTION_KEY,           // 暗号化キー
        0,                        // オプション（通常は0）
        ENCRYPTION_IV             // 初期化ベクトル
    );
    return base64_encode($encrypted); // URLで安全に利用できるようエンコード
}

/**
 * トークンを復号してテーブルIDを取得
 * @param string $token
 * @return string|null 復号されたテーブルID（失敗時はnull）
 */
function decryptTableId(string $token): ?string {
    $decoded = base64_decode($token); // base64デコード
    if ($decoded === false) {
        return null;
    }

    $decrypted = openssl_decrypt(
        $decoded,                 // 暗号化されたデータ
        'AES-256-CBC',            // 暗号化アルゴリズム
        ENCRYPTION_KEY,           // 暗号化キー
        0,                        // オプション（通常は0）
        ENCRYPTION_IV             // 初期化ベクトル
    );
    return $decrypted ?: null; // 復号に失敗した場合はnullを返す
}
?>
