<?php
session_start();

require_once 'db_connect.php';


// エラーメッセージを初期化
$error_message = '';

// フォームが送信されたかチェック
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ユーザー名とパスワードを取得
    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password'];

    // 簡単な入力チェック
    if (!empty($username_or_email) && !empty($password)) {
        try {
            // データベースからユーザーを取得
            $stmt = $pdo->prepare("SELECT user_id, password_hash FROM users WHERE username = :input OR email = :input");
            $stmt->execute(['input' => $username_or_email]);
            $user = $stmt->fetch();

            session_unset();

            // パスワードを検証
            if ($user && password_verify($password, $user['password_hash'])) {
                // --- 認証成功 ---
                
                // セッションハイジャック対策：ログイン時にIDを新しく作り直す
                session_regenerate_id(true);
                
                // データベースから取得した「その人固有のID」をセッションに保存
                $_SESSION['user_id'] = $user['user_id']; 
                
                header('Location: document_list.php');
                exit;
            } else {
                // --- 認証失敗 ---
                $error_message = 'ユーザー名またはパスワードが正しくありません。';
            }

        } catch (PDOException $e) {
            $error_message = 'エラーが発生しました。しばらくしてからやり直してください。';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <!-- CSSファイルを読み込む -->
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h2>ログイン</h2>
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <input type="text" name="username_or_email" placeholder="ユーザー名またはメールアドレス" required>
            <input type="password" name="password" placeholder="パスワード" required>
            <button type="submit">ログイン</button>
        </form>
        
        <div class="links">
            <p><a href="signup.php">新規登録</a></p>
        </div>
    </div>
</body>
</html>

