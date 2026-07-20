<?php
/**
 * save_members.php
 * member_add.html から送信された内容で、同じフォルダの members.js を上書き保存します。
 *
 * 【設置前に必ずやること】
 * 1. 下の $ADMIN_TOKEN を、他人に推測されない好きな文字列に変更してください。
 *    （member_add.html の「管理用パスコード」欄に、ここで設定した文字列と同じものを入力して使います）
 * 2. このファイルと member_add.html、members.js を同じフォルダに置いてください。
 * 3. members.js が置かれているフォルダに、サーバー（PHP実行ユーザー）から書き込み権限が
 *    必要です。書き込みに失敗する場合は members.js とそのフォルダの権限（例: 664 / 775）を
 *    見直してください。
 * 4. 可能であれば、このフォルダ自体をベーシック認証（.htaccess等）で保護し、
 *    第三者が member_add.html や save_members.php に直接アクセスできないようにしてください。
 */

// ---- 設定 ----
$ADMIN_TOKEN = 'link_playground'; // ← 必ず変更してください
$TARGET_FILE = __DIR__ . '/members.js';

header('Content-Type: application/json; charset=utf-8');

// ---- POSTメソッドのみ許可 ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POSTメソッドのみ許可されています。']);
    exit;
}

// ---- トークン確認 ----
$headers = function_exists('getallheaders') ? getallheaders() : [];
$token = $headers['X-Auth-Token'] ?? ($_SERVER['HTTP_X_AUTH_TOKEN'] ?? '');

if (!is_string($token) || $token === '' || !hash_equals($ADMIN_TOKEN, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'パスコードが正しくありません。']);
    exit;
}

if ($ADMIN_TOKEN === 'CHANGE_ME_TO_YOUR_OWN_SECRET') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'サーバー側の設定が未完了です（save_members.php の $ADMIN_TOKEN を変更してください）。']);
    exit;
}

// ---- 本文取得 ----
$content = file_get_contents('php://input');
if ($content === false || trim($content) === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '送信内容が空です。']);
    exit;
}

// ---- 簡易バリデーション（members.js らしい内容かだけ確認）----
if (strpos($content, 'MAP_MEMBERS_DATA') === false || strpos($content, 'LIFE_PLAY_MEMBERS_DATA') === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '内容が members.js の形式と一致しません。']);
    exit;
}

// ---- 上書き前にバックアップを1世代だけ残す ----
if (file_exists($TARGET_FILE)) {
    @copy($TARGET_FILE, $TARGET_FILE . '.bak');
}

// ---- 書き込み ----
$bytes = @file_put_contents($TARGET_FILE, $content, LOCK_EX);
if ($bytes === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => '書き込みに失敗しました。ファイル/フォルダの書き込み権限を確認してください。']);
    exit;
}

echo json_encode(['ok' => true, 'bytes' => $bytes]);