<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Like handle karna
if (isset($_GET['like_post_id'])) {
    $post_id = $_GET['like_post_id'];
    
    // Check karo ki pehle se like kiya hai ya nahi
    $stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    
    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
    } else {
        // Agar pehle se like hai toh unlike kar do
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
    }
    header("Location: dashboard.php");
    exit;
}

// Comment handle karna
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id'], $_POST['comment'])) {
    $post_id = $_POST['post_id'];
    $comment = trim($_POST['comment']);
    
    if (!empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $post_id, $comment]);
    }
    header("Location: dashboard.php");
    exit;
}
?>