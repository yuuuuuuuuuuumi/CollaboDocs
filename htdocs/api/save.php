<?php
require_once '../db_connect.php'; // 接続ファイルへのパスを調整

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$doc_id = $data['doc_id'] ?? null;
$title = $data['title'] ?? '無題のドキュメント';
$content = $data['content'] ?? '';

try {
    if ($doc_id) {
        // U: ドキュメント編集・更新
        // 権限チェックを兼ねた更新クエリ
        $stmt = $pdo->prepare("UPDATE documents SET title = :title, content = :content, last_updated_at = NOW() WHERE doc_id = :doc_id AND author_id = :user_id");
        $result = $stmt->execute([
            'title' => $title,
            'content' => $content,
            'doc_id' => $doc_id,
            'user_id' => $user_id
        ]);
        
        if ($stmt->rowCount() == 0) {
            // 権限がない、またはdoc_idが存在しない
            throw new Exception("更新する権限がないか、ドキュメントが存在しません。");
        }
        $message = "ドキュメントを更新しました。";

    } else {
        // C: ドキュメント作成
        $stmt = $pdo->prepare("INSERT INTO documents (title, content, author_id) VALUES (:title, :content, :author_id)");
        $stmt->execute([
            'title' => $title,
            'content' => $content,
            'author_id' => $user_id
        ]);
        $doc_id = $pdo->lastInsertId(); // 新しいIDを取得
        $message = "ドキュメントを作成しました。";
    }

    echo json_encode(['success' => true, 'message' => $message, 'doc_id' => $doc_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>