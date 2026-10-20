<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['post_id'], $_POST['content'])) {
    $post_id = intval($_POST['post_id']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$content, $post_id, $user_id]);
}

header("Location: dashboard.php");
exit();
?>