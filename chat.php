<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$my_id = $_SESSION['user_id'];
$receiver_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$receiver_id) {
    header("Location: dashboard.php");
    exit;
}

// Samne wale user ki details nikalna
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$receiver_id]);
$receiver = $stmt->fetch();

// Message send hone par database mein save karna
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message'])) {
    $msg_text = trim($_POST['message']);
    if (!empty($msg_text)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$my_id, $receiver_id, $msg_text]);
        header("Location: chat.php?user_id=" . $receiver_id);
        exit;
    }
}

// Dono ke beech ke messages fetch karna
$msg_stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
$msg_stmt->execute([$my_id, $receiver_id, $receiver_id, $my_id]);
$messages = $msg_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with @<?php echo htmlspecialchars($receiver['username']); ?> - Quad</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Quad Chat</div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Back to Dashboard</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="chat-container">
            <h2>Chat with @<?php echo htmlspecialchars($receiver['username']); ?></h2>
            
            <div class="chat-box">
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $m): ?>
                        <div class="message <?php echo ($m['sender_id'] == $my_id) ? 'sent' : 'received'; ?>">
                            <p><?php echo htmlspecialchars($m['message']); ?></p>
                            <span class="msg-time"><?php echo $m['created_at']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #777;">Abhi koi baat nahi hui hai. Pehla message bhejein!</p>
                <?php endif; ?>
            </div>

            <form method="POST" class="chat-form">
                <input type="text" name="message" placeholder="Apna message yahan likhein..." required autocomplete="off">
                <button type="submit">Bhejo</button>
            </form>
        </div>
    </div>
</body>
</html>