<?php
/**
 * 全てログイン画面 (login.php) へリダイレクトする
 */

// リダイレクト先
$redirect_url = 'login.php';

// ヘッダー情報を使ってリダイレクトを実行
header('Location: ' . $redirect_url);
exit;
?>