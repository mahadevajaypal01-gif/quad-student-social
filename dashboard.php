<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $content]);
        $msg = "Post successfully publish ho gayi!";
    }
}

// Posts fetch karna
$stmt = $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quad - Feed Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Quad</div>
        <ul class="nav-links">
            <li><a href="dashboard.php" class="active">Home</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="feed-container">
            <h2>Welcome to Quad Feed, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h2>
            
            <!-- Students List Sidebar Component -->
            <div class="users-list-sidebar">
                <h3>Quad Students (Chat karein)</h3>
                <?php
                $all_users = $pdo->prepare("SELECT * FROM users WHERE id != ?");
                $all_users->execute([$_SESSION['user_id']]);
                $users = $all_users->fetchAll();
                ?>
                <?php if (count($users) > 0): ?>
                    <ul>
                        <?php foreach ($users as $u): ?>
                            <li>
                                <span>@<?php echo htmlspecialchars($u['username']); ?></span>
                                <a href="chat.php?user_id=<?php echo $u['id']; ?>" class="chat-link">Chat Karo</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: #777; font-size: 13px;">Abhi koi aur student registered nahi hai.</p>
                <?php endif; ?>
            </div>

            <!-- Post Creation Box -->
            <div class="post-box">
                <form method="POST">
                    <textarea name="content" placeholder="Aapke mann mein kya hai, student?" required></textarea>
                    <button type="submit">Post Karo</button>
                </form>
                <?php if($msg): ?>
                    <p style="color: green; font-size: 14px; margin-top: 5px;"><?php echo $msg; ?></p>
                <?php endif; ?>
            </div>

            <!-- Posts Feed Display -->
            <div class="posts-feed">
                <h3>Recent Student Posts</h3>
                <?php if (count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card">
                            <h4>@<?php echo htmlspecialchars($post['username']); ?></h4>
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            <span class="post-time"><?php echo $post['created_at']; ?></span>

                            <?php
                            // Likes count
                            $like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
                            $like_stmt->execute([$post['id']]);
                            $likes_count = $like_stmt->fetchColumn();

                            // Check current user liked
                            $user_like_stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
                            $user_like_stmt->execute([$_SESSION['user_id'], $post['id']]);
                            $is_liked = $user_like_stmt->rowCount() > 0;
                            ?>

                            <div class="post-actions">
                                <a href="like_comment.php?like_post_id=<?php echo $post['id']; ?>" class="like-btn <?php echo $is_liked ? 'liked' : ''; ?>">
                                    ❤️ Like (<?php echo $likes_count; ?>)
                                </a>
                            </div>

                            <!-- Comments Section -->
                            <div class="comments-section">
                                <h5>Comments</h5>
                                <?php
                                $comm_stmt = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY comments.created_at ASC");
                                $comm_stmt->execute([$post['id']]);
                                $comments = $comm_stmt->fetchAll();
                                ?>

                                <?php foreach ($comments as $comm): ?>
                                    <div class="comment-item">
                                        <strong>@<?php echo htmlspecialchars($comm['username']); ?>:</strong> 
                                        <span><?php echo htmlspecialchars($comm['comment']); ?></span>
                                    </div>
                                <?php endforeach; ?>

                                <form action="like_comment.php" method="POST" class="comment-form">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="text" name="comment" placeholder="Write a comment..." required>
                                    <button type="submit">Send</button>
                                </form>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #777;">Abhi koi post nahi hai. Pehli post aap karo!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>