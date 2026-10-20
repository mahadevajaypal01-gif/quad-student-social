<?php
header('Content-Type: application/json');
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT sender_id, COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND is_read = 0 GROUP BY sender_id");
    $stmt->execute([$user_id]);
    $counts = [];

    while ($row = $stmt->fetch()) {
        $counts[$row['sender_id']] = (int)$row['unread_count'];
    }

    echo json_encode($counts);
} catch (Exception $e) {
    echo json_encode([]);
}
?>