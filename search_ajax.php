<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (!empty($query)) {
    $stmt = $pdo->prepare("SELECT id, username, full_name, profile_pic FROM users WHERE (username LIKE ? OR full_name LIKE ?) AND id != ? LIMIT 5");
    $stmt->execute(["%$query%", "%$query%", $user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) > 0) {
        foreach ($users as $u) {
            $name = !empty($u['full_name']) ? $u['full_name'] : $u['username'];
            $dp = !empty($u['profile_pic']) && file_exists($u['profile_pic']) ? $u['profile_pic'] : null;
            
            echo '<a href="chat.php?user_id=' . $u['id'] . '" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 border-0 border-bottom">';
            if ($dp) {
                echo '<img src="' . htmlspecialchars($dp) . '" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">';
            } else {
                echo '<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px; font-size: 14px;">' . strtoupper(substr($name, 0, 1)) . '</div>';
            }
            echo '<div>';
            echo '<h6 class="mb-0 text-dark fw-bold" style="font-size: 14px;">' . htmlspecialchars($name) . '</h6>';
            echo '<small class="text-muted" style="font-size: 12px;">@' . htmlspecialchars($u['username']) . '</small>';
            echo '</div>';
            echo '</a>';
        }
    } else {
        echo '<div class="p-3 text-muted text-center small">No user found</div>';
    }
}
?>