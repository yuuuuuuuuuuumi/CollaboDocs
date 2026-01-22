<?php
require_once '../db_connect.php'; 

if (!isset($_SESSION['user_id'])) die("Unauthorized");

$doc_id = $_GET['doc_id'] ?? null;
$version_id = $_GET['version_id'] ?? null;

try {
    $pdo->beginTransaction();

    // 権限チェック
    $stmt_check = $pdo->prepare("SELECT author_id FROM documents WHERE doc_id = :doc_id");
    $stmt_check->execute(['doc_id' => $doc_id]);
    $doc = $stmt_check->fetch();

    if (!$doc || $doc['author_id'] != $_SESSION['user_id']) throw new Exception("権限なし");

    // 過去内容の取得
    $stmt_v = $pdo->prepare("SELECT content FROM document_versions WHERE version_id = :v_id AND doc_id = :d_id");
    $stmt_v->execute(['v_id' => $version_id, 'd_id' => $doc_id]);
    $v_data = $stmt_v->fetch();

    if ($v_data) {
        // 現在のドキュメントを過去の内容で上書き
        $stmt_res = $pdo->prepare("UPDATE documents SET content = :content, last_updated_at = NOW() WHERE doc_id = :doc_id");
        $stmt_res->execute(['content' => $v_data['content'], 'doc_id' => $doc_id]);
    }

    $pdo->commit();
    header("Location: ../document_edit.php?doc_id=$doc_id&status=restored");
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die($e->getMessage());
}