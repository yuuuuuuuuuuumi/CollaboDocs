<?php
// session_start() は認証を使う全てのファイルで必要なので、ここで実行
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// docker-compose.ymlのサービス名と環境変数を使用
define('DB_HOST', 'mysql'); 
define('DB_NAME', 'your_actual_database_name'); // ★実際のDB名に置き換え
define('DB_USER', 'root'); 
define('DB_PASS', 'password'); // docker-compose.ymlのMYSQL_ROOT_PASSWORD

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    exit('データベース接続エラーが発生しました。');
}
?>