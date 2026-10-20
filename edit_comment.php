<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['comment_id'], $_POST['content'])) {
    $comment_id = intval($_POST['comment_id']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    // Update using 'comment' column
    $stmt = $pdo->prepare("UPDATE comments SET comment = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$content, $comment_id, $user_id]);
}

header("Location: dashboard.php");
exit();
?>