<?php
require_once 'db_connect.php'; 
echo "<h1>データベース接続テスト</h1>";
if (isset($pdo)) {
    echo "<p style='color: green;'>接続成功！</p>";
} else {
    echo "<p style='color: red;'>接続失敗。db_connect.phpまたはMySQLの設定を確認してください。</p>";
}
// 既存のユーザー情報を表示してみる
try {
    $stmt = $pdo->query("SELECT user_id, username, email, created_at FROM users LIMIT 1");
    $user = $stmt->fetch();
    if ($user) {
        echo "<h2>既存ユーザーデータ (usersテーブル):</h2>";
        echo "<pre>" . print_r($user, true) . "</pre>";
    } else {
        echo "<p>usersテーブルは存在するが、データがありません。</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>usersテーブルが見つからないか、クエリに失敗しました。テーブル名を確認してください。</p>";
}
?>