<?php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$doc_id = $_GET['doc_id'] ?? null;
$version_id = $_GET['version_id'] ?? null;

try {
    $stmt_curr = $pdo->prepare("SELECT title, content FROM documents WHERE doc_id = :doc_id");
    $stmt_curr->execute(['doc_id' => $doc_id]);
    $current = $stmt_curr->fetch();

    $stmt_ver = $pdo->prepare("SELECT content FROM document_versions WHERE version_id = :v_id");
    $stmt_ver->execute(['v_id' => $version_id]);
    $version = $stmt_ver->fetch();

    if (!$current || !$version) {
        die("データが見つかりません。");
    }

    function getCleanLines($html) {
        $text = str_replace(['</p>', '</div>', '<br>', '<br/>'], "\n", $html);
        $text = strip_tags($text);
        $lines = explode("\n", $text);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') { $cleanLines[] = $trimmed; }
        }
        return $cleanLines;
    }

    $oldLines = getCleanLines($version['content']);
    $newLines = getCleanLines($current['content']);

} catch (PDOException $e) {
    die("エラー: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>変更箇所の確認</title>
    <style>
        body { background-color: #f0f2f5; font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif; }
        .diff-container { max-width: 600px; margin: 30px auto; padding: 40px; background: #fff; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .diff-box { border: 1px solid #eef0f2; border-radius: 12px; overflow: hidden; margin-bottom: 30px; }
        .line { padding: 12px 18px; line-height: 1.6; border-bottom: 1px solid #f8f9fa; font-size: 16px; min-height: 1.5em; word-break: break-all; }
        .line:last-child { border-bottom: none; }
        .deleted { background-color: #fff1f2; color: #be123c; text-decoration: line-through; }
        .added { background-color: #f0fdf4; color: #15803d; }
        
        .action-bar { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .btn-back { color: #3b82f6; text-decoration: none; font-size: 16px; display: flex; align-items: center; }
        .btn-back:hover { text-decoration: underline; }
        .btn-restore { 
            background: #22c55e; color: white; border: none; padding: 14px 24px; 
            border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 18px;
            flex-grow: 1; text-align: center; text-decoration: none; transition: background 0.2s;
        }
        .btn-restore:hover { background: #16a34a; }
    </style>
</head>
<body>
    <div class="diff-container">
        <h2 style="margin-top: 0; margin-bottom: 25px; font-size: 26px; font-weight: 800;">変更内容の確認</h2>
        
        <div class="diff-box">
            <?php
            $i = 0; // 旧インデックス
            $j = 0; // 新インデックス
            
            while ($i < count($oldLines) || $j < count($newLines)) {
                // 1. 両方の行が存在し、一致する場合
                if (isset($oldLines[$i]) && isset($newLines[$j]) && $oldLines[$i] === $newLines[$j]) {
                    echo "<div class='line'>" . htmlspecialchars($oldLines[$i]) . "</div>";
                    $i++; $j++;
                } 
                // 2. 変更がある場合（ペアで表示）
                else {
                    // 片方または両方の行を「変更ペア」として表示
                    if (isset($oldLines[$i]) && isset($newLines[$j])) {
                        // 次に一致する場所があるかチェック
                        $foundMatch = false;
                        for($k = 1; $k < 5; $k++) { // 5行先まで一致を探す
                            if (isset($oldLines[$i+$k]) && isset($newLines[$j]) && $oldLines[$i+$k] === $newLines[$j]) {
                                $foundMatch = "old_moved"; break;
                            }
                            if (isset($newLines[$j+$k]) && isset($oldLines[$i]) && $oldLines[$i] === $newLines[$j+$k]) {
                                $foundMatch = "new_moved"; break;
                            }
                        }

                        if (!$foundMatch) {
                            // 明らかなペアとして表示
                            echo "<div class='line deleted'>" . htmlspecialchars($oldLines[$i]) . "</div>";
                            echo "<div class='line added'>" . htmlspecialchars($newLines[$j]) . "</div>";
                            $i++; $j++;
                        } else if ($foundMatch === "old_moved") {
                            echo "<div class='line deleted'>" . htmlspecialchars($oldLines[$i]) . "</div>";
                            $i++;
                        } else {
                            echo "<div class='line added'>" . htmlspecialchars($newLines[$j]) . "</div>";
                            $j++;
                        }
                    } 
                    // 3. 旧データのみ残っている場合
                    else if (isset($oldLines[$i])) {
                        echo "<div class='line deleted'>" . htmlspecialchars($oldLines[$i]) . "</div>";
                        $i++;
                    } 
                    // 4. 新データのみ残っている場合
                    else if (isset($newLines[$j])) {
                        echo "<div class='line added'>" . htmlspecialchars($newLines[$j]) . "</div>";
                        $j++;
                    }
                }
            }
            ?>
        </div>

        <div class="action-bar">
            <a href="document_versions.php?doc_id=<?= $doc_id ?>" class="btn-back">← 戻る</a>
            <a href="api/restore.php?doc_id=<?= $doc_id ?>&version_id=<?= $version_id ?>" 
               class="btn-restore" 
               onclick="return confirm('この時の内容に復元しますか？');">この時の内容に復元する</a>
        </div>
    </div>
</body>
</html>