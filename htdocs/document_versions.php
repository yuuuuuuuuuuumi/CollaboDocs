<?php
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$doc_id = $_GET['doc_id'] ?? null;

if (!$doc_id) {
    die("ドキュメントIDが指定されていません。");
}

try {
    // ドキュメント情報の取得と権限チェック
    $stmt_doc = $pdo->prepare("SELECT title, author_id FROM documents WHERE doc_id = :doc_id");
    $stmt_doc->execute(['doc_id' => $doc_id]);
    $doc = $stmt_doc->fetch();

    if (!$doc || $doc['author_id'] != $user_id) {
        die("閲覧権限がないか、ドキュメントが存在しません。");
    }
    $document_title = $doc['title'];

    // 履歴データの取得
    $stmt_ver = $pdo->prepare("SELECT version_id, saved_at FROM document_versions WHERE doc_id = :doc_id ORDER BY saved_at DESC");
    $stmt_ver->execute(['doc_id' => $doc_id]);
    $versions = $stmt_ver->fetchAll();

} catch (PDOException $e) {
    die("DBエラー: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>履歴 - <?php echo htmlspecialchars($document_title); ?></title>
    <link href="style.css" rel="stylesheet">
    <link href="history.css" rel="stylesheet">
</head>
<body>
    <div class="history-container">
        <h2>「<?php echo htmlspecialchars($document_title); ?>」の履歴一覧</h2>
        
        <table class="history-table">
            <thead>
                <tr>
                    <th>保存日時</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <tr class="current-row">
                    <td>現在編集中の内容</td>
                    <td><span class="current-label">最新版</span></td>
                </tr>

                <?php if (empty($versions)): ?>
                    <tr>
                        <td colspan="2" style="text-align:center; padding:20px;">履歴はまだありません。</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($versions as $v): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($v['saved_at']); ?></td>
                        <td>
                            <a href="api/restore.php?doc_id=<?php echo $doc_id; ?>&version_id=<?php echo $v['version_id']; ?>" 
                               class="restore-button" 
                               onclick="return confirm('この時点の内容に戻しますか？');">
                               この版に戻す
                            </a>

                            <a href="document_diff.php?doc_id=<?php echo $doc_id; ?>&version_id=<?php echo $v['version_id']; ?>" 
                               class="restore-button" style="background-color: #007bff;">
                               変更を確認して戻す
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 20px; text-align: right;">
            <a href="document_edit.php?doc_id=<?php echo $doc_id; ?>" class="back-link">← ドキュメント編集に戻る</a>
        </div>
    </div>
</body>
</html>