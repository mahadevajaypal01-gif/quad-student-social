<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['receiver_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];
$receiver_id = intval($_GET['receiver_id']);

// Mark messages as read when chat is opened
$update_stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
$update_stmt->execute([$receiver_id, $user_id]);

// Fetch messages
$stmt = $pdo->prepare("SELECT * FROM messages 
                       WHERE (sender_id = ? AND receiver_id = ?) 
                          OR (sender_id = ? AND receiver_id = ?) 
                       ORDER BY created_at ASC");
$stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
$messages = $stmt->fetchAll();

foreach ($messages as $msg) {
    $class = ($msg['sender_id'] == $user_id) ? 'msg-sent' : 'msg-received';
    $time = date('h:i A', strtotime($msg['created_at']));
    echo '<div class="msg-bubble ' . $class . '">';
    echo '<div>' . htmlspecialchars($msg['message']) . '</div>';
    echo '<small class="d-block text-end mt-1 opacity-75" style="font-size: 0.68rem;">' . $time . '</small>';
    echo '</div>';
}
?>