<?php
require_once 'db_connect.php'; // セッションスタートを含む

// 認証ガード：セッションにuser_idがなければログイン画面へ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$documents = [];

try {
    // ログインユーザーが作成したドキュメントのみを取得
    $stmt = $pdo->prepare("SELECT doc_id, title, created_at, last_updated_at FROM documents WHERE author_id = :user_id ORDER BY last_updated_at DESC");
    $stmt->execute(['user_id' => $user_id]);
    $documents = $stmt->fetchAll();

    // ユーザー名を取得（ヘッダー表示用）
    $user_stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = :user_id");
    $user_stmt->execute(['user_id' => $user_id]);
    $current_user = $user_stmt->fetchColumn();

} catch (PDOException $e) {
    error_log('Document fetch error: ' . $e->getMessage());
    $error = 'ドキュメントの取得中にエラーが発生しました。';
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ドキュメント一覧</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <h1>ドキュメント一覧</h1>
            <div>
                <span>ようこそ、<?php echo htmlspecialchars($current_user ?? 'ユーザー'); ?>さん</span>
                <a href="logout.php">ログアウト</a> | 
                <a href="document_edit.php">新規ドキュメント作成</a>
            </div>
        </header>

        <table>
            <thead>
                <tr>
                    <th>タイトル</th>
                    <th>最終更新日時</th>
                    <th>アクション</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr><td colspan="3">ドキュメントがありません。</td></tr>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doc['title']); ?></td>
                            <td><?php echo htmlspecialchars($doc['last_updated_at']); ?></td>
                            <td>
                                <a href="document_edit.php?doc_id=<?php echo $doc['doc_id']; ?>">編集</a>
                                <a href="api/delete.php?doc_id=<?php echo $doc['doc_id']; ?>" 
                                   onclick="return confirm('本当に削除しますか？');">削除</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>