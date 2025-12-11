<?php
session_start();

// エラーメッセージを初期化
$error_message = '';

// フォームが送信されたかチェック
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ユーザー名とパスワードを取得
    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password'];

    // 簡単な入力チェック
    if (empty($username_or_email) || empty($password)) {
        $error_message = 'ユーザー名とパスワードを入力してください。';
    } else {
        // ここにデータベース接続と認証処理を記述
        // 例: データベースからユーザーを取得し、パスワードを検証
        // この例では、認証成功をシミュレート
        
        // 実際には以下のような処理が必要:
        // 1. MySQLに接続
        require_once 'db_connect.php';

        // 2. SQLクエリでユーザーを取得
        $stmt = $pdo->prepare("SELECT user_id, password_hash FROM users WHERE username = :input OR email = :input");
        $stmt->execute(['input' => $username_or_email]);
        $user = $stmt->fetch();

        // 3. password_verify() でハッシュ化されたパスワードを検証
        if ($user && password_verify($password, $user['password_hash'])) {
            // 認証成功
            $_SESSION['user_id'] = $user['user_id']; // ユーザーIDをセッションに保存
            header('Location: document_list.php'); // ドキュメント一覧画面へリダイレクト
            exit;
        } else {
            $error_message = 'ユーザー名またはパスワードが正しくありません。';
        }
        // シミュレーション: 認証が成功した場合
        if ($username_or_email === 'test' && $password === 'password') {
            $_SESSION['user_id'] = 1; // ユーザーIDをセッションに保存
            header('Location: document_list.php'); // ドキュメント一覧画面へリダイレクト
            exit;
        } else {
            $error_message = 'ユーザー名またはパスワードが正しくありません。';
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
            <p><a href="#">パスワードを忘れた場合(おまけ)</a></p>
            <p><a href="signup.php">新規登録</a></p>
        </div>
    </div>
</body>
</html>

