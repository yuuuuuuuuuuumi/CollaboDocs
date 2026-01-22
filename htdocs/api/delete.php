<?php
require_once '../db_connect.php'; // 接続ファイルへのパスを調整

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$doc_id = $_GET['doc_id'] ?? null;

if (!$doc_id) {
    header('Location: ../document_list.php'); // IDがない場合は一覧へ戻す
    exit;
}

try {
    // ドキュメント削除
    // 権限チェックを兼ねた削除クエリ
    $stmt = $pdo->prepare("DELETE FROM documents WHERE doc_id = :doc_id AND author_id = :user_id");
    $stmt->execute(['doc_id' => $doc_id, 'user_id' => $user_id]);

    header('Location: ../document_list.php?status=deleted');
    exit;

} catch (PDOException $e) {
    error_log('Document delete error: ' . $e->getMessage());
    // エラーハンドリング（一覧画面に戻してエラーメッセージを表示するなど）
    header('Location: ../document_list.php?status=error');
    exit;
}
?>