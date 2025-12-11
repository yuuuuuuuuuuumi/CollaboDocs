<?php
// DB接続とセッション開始
require_once 'db_connect.php'; 

// --- 認証ガードとデータの読み込み ---

// 認証ガード：セッションにuser_idがなければログイン画面へ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// URLパラメータからdoc_idを取得
$doc_id = $_GET['doc_id'] ?? null;
$document_data = [
    'doc_id' => $doc_id, // 新規作成時はnull
    'title' => '新規ドキュメント', 
    'content' => '' // 新規作成時は空
];

try {
    if ($doc_id) {
        // 編集モードの場合：ドキュメントの読み込みと権限チェック
        $stmt = $pdo->prepare("SELECT title, content, author_id FROM documents WHERE doc_id = :doc_id");
        $stmt->execute(['doc_id' => $doc_id]);
        $fetched_data = $stmt->fetch();

        // 権限チェック：データが存在しない、または作成者IDがログインユーザーIDと一致しない場合
        if (!$fetched_data || $fetched_data['author_id'] != $user_id) {
            // 権限なし、またはドキュメントが存在しない
            header('Location: document_list.php?error=unauthorized'); 
            exit;
        }

        // データを$document_dataにセット
        $document_data['title'] = $fetched_data['title'];
        $document_data['content'] = $fetched_data['content'];
    }
} catch (PDOException $e) {
    error_log("Database error on document load: " . $e->getMessage());
    header('Location: document_list.php?error=db');
    exit;
}

// ユーザー名を取得（ヘッダー表示用）
$user_stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = :user_id");
$user_stmt->execute(['user_id' => $user_id]);
$current_user = $user_stmt->fetchColumn();

// --- HTMLとJavaScriptの出力 ---
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ドキュメント編集: <?php echo htmlspecialchars($document_data['title']); ?></title>
    <link href="style.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .title-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 24px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-controls">
            <div>
                <a href="document_list.php">← ドキュメント一覧へ戻る</a>
            </div>
            <div>
                <span>ようこそ、<?php echo htmlspecialchars($current_user ?? 'ユーザー'); ?>さん</span>
            </div>
        </div>

        <input type="text" id="documentTitle" class="title-input" 
               placeholder="ドキュメントのタイトルを入力" 
               value="<?php echo htmlspecialchars($document_data['title']); ?>">

        <div id="editor"></div>
        
        <div class="button-container">
            <button id="saveButton">保存</button>
            <span id="saveStatus" style="margin-left: 20px; color: green;"></span>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <script>
        // PHPからドキュメントIDとコンテンツを取得
        const DOC_ID = <?php echo json_encode($document_data['doc_id']); ?>;
        const INITIAL_CONTENT = <?php echo json_encode($document_data['content']); ?>;

        // Quill.jsエディタを初期化
        var quill = new Quill('#editor', {
            theme: 'snow', // スノーテーマを使用
            placeholder: 'ドキュメントの内容をここに入力...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link']
                ]
            }
        });

        // 既存のコンテンツをエディタにセット (HTML形式と想定)
        if (INITIAL_CONTENT) {
            // HTMLをDelta形式に変換してセットするか、直接HTMLをセットする
            // Quill.jsはDelta形式を推奨しますが、ここではシンプルなHTML挿入の例
            const delta = quill.clipboard.convert(INITIAL_CONTENT);
            quill.setContents(delta);
        }

        const saveButton = document.getElementById('saveButton');
        const saveStatus = document.getElementById('saveStatus');
        const titleInput = document.getElementById('documentTitle');

        // 保存ボタンがクリックされた時の処理
        saveButton.addEventListener('click', async function() {
            saveStatus.textContent = '保存中...';
            saveButton.disabled = true;

            // エディタの内容をHTML形式で取得
            const content = quill.root.innerHTML; 
            const title = titleInput.value.trim() || '無題のドキュメント';

            const dataToSend = {
                doc_id: DOC_ID, // 新規作成時はnull、編集時はID
                title: title,
                content: content 
            };

            try {
                const response = await fetch('api/save.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dataToSend)
                });

                const result = await response.json();

                if (result.success) {
                    saveStatus.textContent = '保存済み (' + new Date().toLocaleTimeString() + ')';
                    // 新規作成が成功したら、URLをdoc_id付きに更新して編集モードへ移行 (重要)
                    if (!DOC_ID && result.doc_id) {
                        window.history.pushState({}, '', `document_edit.php?doc_id=${result.doc_id}`);
                        // ページのリロードなしで、DOC_IDの値を更新する処理が必要だが、
                        // シンプルにするため、ここではリロードしないまま続行する
                    }
                } else {
                    saveStatus.textContent = '保存エラー: ' + result.error;
                    saveStatus.style.color = 'red';
                }
            } catch (error) {
                saveStatus.textContent = 'ネットワークエラーが発生しました。';
                saveStatus.style.color = 'red';
                console.error('Save failed:', error);
            } finally {
                saveButton.disabled = false;
            }
        });
    </script>
</body>
</html>