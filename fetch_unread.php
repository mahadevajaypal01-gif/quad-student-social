<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT sender_id, COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND is_read = 0 GROUP BY sender_id");
$stmt->execute([$user_id]);
$counts = [];

while ($row = $stmt->fetch()) {
    $counts[$row['sender_id']] = $row['unread_count'];
}

echo json_encode($counts);
?>