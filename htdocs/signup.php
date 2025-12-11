<?php
session_start();

// エラーメッセージを初期化
$errors = [];

// フォームが送信されたかチェック
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ユーザー名、メールアドレス、パスワードを取得
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // 入力チェックとバリデーション
    if (empty($username)) {
        $errors[] = 'ユーザー名を入力してください。';
    }
    if (empty($email)) {
        $errors[] = 'メールアドレスを入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '有効なメールアドレスを入力してください。';
    }
    if (empty($password)) {
        $errors[] = 'パスワードを入力してください。';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'パスワードが一致しません。';
    }
    
    // バリデーションに成功した場合
    if (empty($errors)) {
        // ここにデータベースへの新規ユーザー登録処理を記述
        // 実際には以下のような処理が必要:
        // 1. MySQLに接続
        require_once 'db_connect.php';

        // 2. メールアドレスやユーザー名が既に存在しないかチェック
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = 'そのユーザー名またはメールアドレスは既に使用されています。';
        }

        // 重複チェックに問題がなければ登録処理
        if (empty($errors)) {

            // 3. password_hash() でパスワードをハッシュ化
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // 4. SQLクエリで新規ユーザーを登録
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
            $result = $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash
            ]);

            if ($result) {
                header('Location: login.php?signup=success');
                exit;
            } else {
                $errors[] = 'ユーザー登録中にエラーが発生しました。';
            }
        }
    }
        
        // シミュレーション: 登録成功後、ログインページへリダイレクト
        // 実際には、登録完了メッセージを表示してからリダイレクトするなど、ユーザーへのフィードバックを考慮
        header('Location: login.php?signup=success');
        exit;
    }
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録</title>
    <!-- style.cssを読み込む -->
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h2>新規登録</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="signup.php" method="POST">
            <input type="text" name="username" placeholder="ユーザー名" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            <input type="email" name="email" placeholder="メールアドレス" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            <input type="password" name="password" placeholder="パスワード" required>
            <input type="password" name="password_confirm" placeholder="パスワード（確認用）" required>
            <button type="submit">登録</button>
        </form>
        
        <div class="links">
            <p><a href="login.php">ログイン画面に戻る</a></p>
        </div>
    </div>
</body>
</html>