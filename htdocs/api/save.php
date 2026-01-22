<?php
require_once '../db_connect.php'; 
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$doc_id = $data['doc_id'] ?? null;
$title = $data['title'] ?? '無題のドキュメント';
$content = $data['content'] ?? '';

try {
    $pdo->beginTransaction();

    if ($doc_id) {
        // 更新前に現在の内容をバックアップ
        $stmt_old = $pdo->prepare("SELECT content, author_id FROM documents WHERE doc_id = :doc_id");
        $stmt_old->execute(['doc_id' => $doc_id]);
        $old_data = $stmt_old->fetch();

        if ($old_data && $old_data['author_id'] == $user_id) {
            // 履歴に保存
            $stmt_ver = $pdo->prepare("INSERT INTO document_versions (doc_id, content) VALUES (:doc_id, :content)");
            $stmt_ver->execute(['doc_id' => $doc_id, 'content' => $old_data['content']]);

            // 最新版を更新
            $stmt = $pdo->prepare("UPDATE documents SET title = :title, content = :content, last_updated_at = NOW() WHERE doc_id = :doc_id");
            $stmt->execute(['title' => $title, 'content' => $content, 'doc_id' => $doc_id]);
        }
    } else {
        // 新規作成
        $stmt = $pdo->prepare("INSERT INTO documents (title, content, author_id) VALUES (:title, :content, :author_id)");
        $stmt->execute(['title' => $title, 'content' => $content, 'author_id' => $user_id]);
        $doc_id = $pdo->lastInsertId();
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'doc_id' => $doc_id]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}