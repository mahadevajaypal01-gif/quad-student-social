<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

$user_name = !empty($current_user['full_name']) ? $current_user['full_name'] : ($current_user['username'] ?? 'Student');
$user_dp = !empty($current_user['profile_pic']) && file_exists($current_user['profile_pic']) ? $current_user['profile_pic'] : null;

// Handle New Post Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $content]);
        header("Location: dashboard.php");
        exit();
    }
}

// Handle Like / Unlike
if (isset($_GET['like_post'])) {
    $post_id = $_GET['like_post'];
    $check = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
    $check->execute([$user_id, $post_id]);
    if ($check->rowCount() > 0) {
        $del = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $del->execute([$user_id, $post_id]);
    } else {
        $ins = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $ins->execute([$user_id, $post_id]);
    }
    header("Location: dashboard.php");
    exit();
}

// Handle Comment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $post_id = $_POST['post_id'];
    $comment_text = trim($_POST['comment_text']);
    if (!empty($comment_text)) {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $post_id, $comment_text]);
        header("Location: dashboard.php");
        exit();
    }
}

// Fetch all posts with user info
$posts_query = "SELECT posts.*, users.username, users.full_name, users.profile_pic FROM posts 
                JOIN users ON posts.user_id = users.id 
                ORDER BY posts.created_at DESC";
$posts = $pdo->query($posts_query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quad - Home Feed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #ffffff; border-right: 1px solid #e4e6eb; display: flex; flex-direction: column; justify-content: space-between; padding: 24px 20px; position: sticky; top: 0; height: 100vh; }
        .content-area { flex: 1; padding: 30px 20px; display: flex; justify-content: center; overflow-y: auto; }
        .feed-container { width: 100%; max-width: 680px; display: flex; flex-direction: column; gap: 20px; }
        .nav-link-custom { display: flex; align-items: center; gap: 14px; padding: 12px 18px; color: #4b5563; border-radius: 12px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; }
        .nav-link-custom:hover, .nav-link-custom.active { background-color: #eef2ff; color: #4f46e5; }
        .custom-card { border: none; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); background: #ffffff; }
        .avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.1rem; flex-shrink: 0; object-fit: cover; }
        .user-profile-link { border-radius: 12px; padding: 8px; transition: background-color 0.2s ease; }
        .user-profile-link:hover { background-color: #eef2ff; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div>
            <div class="d-flex align-items-center gap-2 mb-4 px-2">
                <i class="fa-solid fa-graduation-cap text-primary fs-3"></i>
                <h3 class="fw-bold text-primary m-0">Quad</h3>
            </div>
            
            <nav class="d-flex flex-column gap-2">
                <a href="dashboard.php" class="nav-link-custom active"><i class="fa-solid fa-house fs-5"></i> Home</a>
                <a href="chat.php" class="nav-link-custom justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-comments fs-5"></i> Messages
                    </div>
                    <span class="badge bg-primary rounded-pill" id="sidebarUnreadBadge" style="display: none;">0</span>
                </a>
                <a href="assignments.php" class="nav-link-custom"><i class="fa-solid fa-book-open fs-5"></i> Study Notes</a>
                <a href="about.php" class="nav-link-custom"><i class="fa-solid fa-circle-info fs-5"></i> About</a>
                <a href="contact.php" class="nav-link-custom"><i class="fa-solid fa-envelope fs-5"></i> Contact</a>
            </nav>
        </div>

        <div>
            <a href="profile.php" class="d-flex align-items-center gap-3 mb-3 px-2 text-decoration-none user-profile-link">
                <?php if ($user_dp): ?>
                    <img src="<?php echo htmlspecialchars($user_dp); ?>" class="avatar" alt="DP">
                <?php else: ?>
                    <div class="avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <?php endif; ?>
                <div class="overflow-hidden">
                    <div class="fw-bold text-dark text-truncate"><?php echo htmlspecialchars($user_name); ?></div>
                    <small class="text-muted">View Profile & Bio</small>
                </div>
            </a>
            <a href="logout.php" class="btn btn-outline-danger w-100 rounded-pill"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
        </div>
    </aside>

    <!-- Content Area -->
    <main class="content-area">
        <div class="feed-container">
            <div class="card custom-card p-4">
                <form action="dashboard.php" method="POST">
                    <div class="mb-3">
                        <textarea name="content" class="form-control border-0 bg-light rounded-4 p-3" rows="3" placeholder="What's on your mind, student?" required style="resize: none;"></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="create_post" class="btn btn-primary rounded-pill px-4 fw-bold">Post Update</button>
                    </div>
                </form>
            </div>

            <div class="d-flex flex-column gap-3">
                <?php foreach ($posts as $post): ?>
                    <?php 
                        $post_author_name = !empty($post['full_name']) ? $post['full_name'] : $post['username'];
                        $post_author_dp = !empty($post['profile_pic']) && file_exists($post['profile_pic']) ? $post['profile_pic'] : null;

                        $likes_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
                        $likes_stmt->execute([$post['id']]);
                        $likes_count = $likes_stmt->fetch()['count'];

                        $user_liked_stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
                        $user_liked_stmt->execute([$user_id, $post['id']]);
                        $is_liked = $user_liked_stmt->rowCount() > 0;

                        $com_stmt = $pdo->prepare("SELECT comments.*, users.username, users.full_name FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY comments.created_at ASC");
                        $com_stmt->execute([$post['id']]);
                        $comments = $com_stmt->fetchAll();
                    ?>
                    <div class="card custom-card p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <?php if ($post_author_dp): ?>
                                <img src="<?php echo htmlspecialchars($post_author_dp); ?>" class="avatar" alt="DP">
                            <?php else: ?>
                                <div class="avatar"><?php echo strtoupper(substr($post_author_name, 0, 1)); ?></div>
                            <?php endif; ?>
                            <div>
                                <h6 class="fw-bold text-dark m-0"><?php echo htmlspecialchars($post_author_name); ?></h6>
                                <small class="text-muted"><?php echo date('M d, Y • h:i A', strtotime($post['created_at'])); ?></small>
                            </div>
                        </div>
                        
                        <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        
                        <div class="d-flex gap-3 mb-3">
                            <a href="dashboard.php?like_post=<?php echo $post['id']; ?>" class="btn btn-sm <?php echo $is_liked ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-3 fw-bold">
                                <i class="fa-solid fa-thumbs-up me-1"></i> Like (<?php echo $likes_count; ?>)
                            </a>
                        </div>

                        <div class="border-top pt-3">
                            <div class="d-flex flex-column gap-2 mb-3">
                                <?php foreach ($comments as $comment): ?>
                                    <?php $c_name = !empty($comment['full_name']) ? $comment['full_name'] : $comment['username']; ?>
                                    <div class="bg-light p-2 rounded-3 px-3">
                                        <span class="fw-bold text-dark small"><?php echo htmlspecialchars($c_name); ?>:</span>
                                        <span class="text-secondary small"><?php echo htmlspecialchars($comment['comment']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <form action="dashboard.php" method="POST" class="d-flex gap-2">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <input type="text" name="comment_text" class="form-control form-control-sm rounded-pill bg-light border-0 px-3" placeholder="Write a comment..." required>
                                <button type="submit" name="add_comment" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">Send</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="sidebar_notifications.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>