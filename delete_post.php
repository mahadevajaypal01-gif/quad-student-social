<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $post_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Sirf wahi post delete hogi jo logged-in user ki apni hai
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
}

header("Location: dashboard.php");
exit();
?>