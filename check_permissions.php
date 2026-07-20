<?php
/**
 * check_permissions.php
 * members.js の書き込み権限まわりの状態を確認するための診断スクリプトです。
 * 問題解決後は、セキュリティのためこのファイルを削除してください。
 */

header('Content-Type: text/plain; charset=utf-8');

$TARGET_FILE = __DIR__ . '/members.js';
$DIR = __DIR__;

echo "==== 診断結果 ====\n\n";

echo "このスクリプトの場所: $DIR\n";
echo "対象ファイル: $TARGET_FILE\n\n";

// PHPの実行ユーザー
if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
    $user = posix_getpwuid(posix_geteuid());
    echo "PHP実行ユーザー: " . $user['name'] . " (uid=" . $user['uid'] . ")\n";
} else {
    echo "PHP実行ユーザー: 取得不可（posix拡張が無効）\n";
}
echo "whoami相当: " . (function_exists('get_current_user') ? get_current_user() : '不明') . "\n\n";

// フォルダの状態
if (is_dir($DIR)) {
    echo "フォルダの存在: OK\n";
    echo "フォルダの権限: " . substr(sprintf('%o', fileperms($DIR)), -4) . "\n";
    echo "フォルダの所有者: " . (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($DIR))['name'] : fileowner($DIR)) . "\n";
    echo "フォルダへの書き込み: " . (is_writable($DIR) ? "可能" : "不可 ← 問題あり") . "\n\n";
} else {
    echo "フォルダが見つかりません。\n\n";
}

// ファイルの状態
if (file_exists($TARGET_FILE)) {
    echo "members.js の存在: OK\n";
    echo "members.js の権限: " . substr(sprintf('%o', fileperms($TARGET_FILE)), -4) . "\n";
    echo "members.js の所有者: " . (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($TARGET_FILE))['name'] : fileowner($TARGET_FILE)) . "\n";
    echo "members.js への書き込み: " . (is_writable($TARGET_FILE) ? "可能" : "不可 ← 問題あり") . "\n\n";
} else {
    echo "members.js が見つかりません（パスを確認してください）。\n\n";
}

// 実際に試し書きしてみる
echo "==== 試し書きテスト ====\n";
$testFile = $DIR . '/_write_test_' . time() . '.tmp';
$result = @file_put_contents($testFile, 'test');
if ($result !== false) {
    echo "フォルダへの新規ファイル作成: 成功\n";
    @unlink($testFile);
} else {
    echo "フォルダへの新規ファイル作成: 失敗 ← サーバーの書き込み権限設定に問題があります\n";
}

if (file_exists($TARGET_FILE)) {
    $backup = @file_get_contents($TARGET_FILE);
    $writeResult = @file_put_contents($TARGET_FILE, $backup);
    if ($writeResult !== false) {
        echo "members.js への上書きテスト: 成功\n";
    } else {
        echo "members.js への上書きテスト: 失敗 ← このファイル自体の権限を見直してください\n";
    }
}

echo "\n==== 対処方法の目安 ====\n";
echo "1. 上記で「フォルダへの書き込み: 不可」の場合 → members.js があるフォルダのパーミッションを 755 → 775 or 777 に変更してみてください（FTPソフトやレンタルサーバーのファイルマネージャーから変更可能）。\n";
echo "2. 「members.js への書き込み: 不可」の場合 → members.js 自体のパーミッションを 644 → 664 or 666 に変更してみてください。\n";
echo "3. 所有者がPHP実行ユーザーと異なる場合 → FTPでアップロードしたファイルの所有者と、PHPが動くユーザー（Webサーバーのユーザー、例: www-data や nobody）が違うことが原因のことが多いです。レンタルサーバーの管理画面から「パーミッション変更」機能を使うか、サポートに問い合わせてください。\n";
echo "4. どうしても解決しない場合は、契約しているレンタルサーバー名を教えていただければ、その環境向けの具体的な手順をご案内します。\n";