<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $comment_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Delete comment only if it belongs to the logged-in user
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $user_id]);
}

header("Location: dashboard.php");
exit();
?>