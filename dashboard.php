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

// Handle new post creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_post'])) {
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
    $post_id = intval($_GET['like_post']);
    
    // Check if already liked
    $check_stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
    $check_stmt->execute([$user_id, $post_id]);
    
    if ($check_stmt->rowCount() > 0) {
        // Unlike
        $del_stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $del_stmt->execute([$user_id, $post_id]);
    } else {
        // Like
        $ins_stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $ins_stmt->execute([$user_id, $post_id]);
    }
    header("Location: dashboard.php");
    exit();
}

// Handle new comment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    $post_id = intval($_POST['post_id']);
    $comment_content = trim($_POST['comment_content']);
    if (!empty($comment_content)) {
        // Updated column name to 'comment'
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$post_id, $user_id, $comment_content]);
        header("Location: dashboard.php");
        exit();
    }
}

// Fetch all posts with user info
$posts_stmt = $pdo->prepare("SELECT posts.*, users.username, users.full_name, users.profile_pic FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.id DESC");
$posts_stmt->execute();
$posts = $posts_stmt->fetchAll();
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
        .content-area { flex: 1; padding: 30px; overflow-y: auto; max-width: 850px; margin: 0 auto; }
        .nav-link-custom { display: flex; align-items: center; gap: 14px; padding: 12px 18px; color: #4b5563; border-radius: 12px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; }
        .nav-link-custom:hover, .nav-link-custom.active { background-color: #eef2ff; color: #4f46e5; }
        .custom-card { border: none; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); background: #ffffff; margin-bottom: 20px; }
        .avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0; object-fit: cover; }
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
           <?php
// Agar messages count ya koi aur count fetch karna ho toh yeh queries use kar sakte hain:
$unread_msg_count = 0; // Agar messages table se count lana ho
?>
<nav class="d-flex flex-column gap-2">
    <a href="dashboard.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-house fs-5"></i> Home
    </a>
    <a href="search.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'search.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-magnifying-glass fs-5"></i> Search Users
    </a>
    <a href="follows.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'follows.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-plus fs-5"></i> Following
    </a>
    <a href="groups.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'groups.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-users-rectangle fs-5"></i> Study Groups
    </a>
    <a href="chat.php" class="nav-link-custom d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'chat.php') ? 'active' : ''; ?>">
        <span><i class="fa-solid fa-comments fs-5 me-3"></i> Messages</span>
        <?php if (!empty($unread_msg_count) && $unread_msg_count > 0): ?>
            <span class="badge bg-primary rounded-pill"><?php echo $unread_msg_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="assignments.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'assignments.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-book-open fs-5"></i> Study Notes
    </a>
    <a href="about.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-circle-info fs-5"></i> About
    </a>
    <a href="contact.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-envelope fs-5"></i> Contact
    </a>
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

    <!-- Main Feed Content Area -->
    <main class="content-area">
        <!-- Create Post Card -->
        <div class="card custom-card p-4">
            <form action="dashboard.php" method="POST">
                <div class="mb-3">
                    <textarea name="content" class="form-control border-0 bg-light p-3" rows="3" placeholder="What's on your mind, student?" required style="resize: none; border-radius: 12px;"></textarea>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" name="create_post" class="btn btn-primary rounded-pill px-4 fw-bold">Post Update</button>
                </div>
            </form>
        </div>

        <!-- Feed List -->
        <div class="d-flex flex-column gap-3">
            <?php foreach ($posts as $post): ?>
                <?php 
                    $post_author_name = !empty($post['full_name']) ? $post['full_name'] : $post['username'];
                    $post_author_dp = !empty($post['profile_pic']) && file_exists($post['profile_pic']) ? $post['profile_pic'] : null;

                    // Fetch likes count
                    $likes_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
                    $likes_stmt->execute([$post['id']]);
                    $likes_count = $likes_stmt->fetch()['count'];

                    // Check if current user liked this post
                    $user_liked_stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
                    $user_liked_stmt->execute([$user_id, $post['id']]);
                    $is_liked = $user_liked_stmt->rowCount() > 0;

                    // Fetch comments for this post
                    $com_stmt = $pdo->prepare("SELECT comments.*, users.username, users.full_name FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY comments.id ASC");
                    $com_stmt->execute([$post['id']]);
                    $comments = $com_stmt->fetchAll();
                ?>
                <div class="card custom-card p-4">
                    <!-- Post Header -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-3">
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

                        <!-- Post Action Buttons (Professional Pill Style) -->
<?php if ($post['user_id'] == $user_id): ?>
    <div class="dropdown">
        <button class="btn btn-sm text-muted" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button>
        <ul class="dropdown-menu dropdown-menu-end p-2 border-0 shadow-sm" style="border-radius: 12px; min-width: 140px;">
            <li class="mb-1">
                <button type="button" class="dropdown-item btn btn-outline-primary btn-sm rounded-pill d-flex align-items-center gap-2 edit-post-btn text-primary fw-semibold px-3 py-1" data-postid="<?php echo $post['id']; ?>" data-content="<?php echo htmlspecialchars($post['content']); ?>">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>
            </li>
            <li>
                <a href="delete_post.php?id=<?php echo $post['id']; ?>" class="dropdown-item btn btn-outline-danger btn-sm rounded-pill d-flex align-items-center gap-2 text-danger fw-semibold px-3 py-1" onclick="return confirm('Are you sure you want to delete this post?');">
                    <i class="fa-solid fa-trash-can"></i> Delete
                </a>
            </li>
        </ul>
    </div>
<?php endif; ?>
                    </div>

                    <!-- Post Content -->
                    <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

                    <!-- Post Footer (Likes & Comments count) -->
                    <div class="d-flex gap-3 mb-3 border-top border-bottom py-2">
                        <a href="dashboard.php?like_post=<?php echo $post['id']; ?>" class="btn btn-sm <?php echo $is_liked ? 'btn-primary' : 'btn-light'; ?> rounded-pill px-3">
                            <i class="fa-solid fa-thumbs-up me-1"></i> Like (<?php echo $likes_count; ?>)
                        </a>
                        <button class="btn btn-sm btn-light rounded-pill px-3 text-muted">
                            <i class="fa-solid fa-comment me-1"></i> Comments (<?php echo count($comments); ?>)
                        </button>
                    </div>

                    <!-- Comments Section -->
<div class="d-flex flex-column gap-2 mb-3">
    <?php foreach ($comments as $com): ?>
        <?php 
            // Check column name for comment text dynamically
            $comment_body = $com['content'] ?? $com['comment_text'] ?? $com['comment'] ?? '';
        ?>
        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light">
            <div>
                <strong class="text-dark small"><?php echo htmlspecialchars(!empty($com['full_name']) ? $com['full_name'] : $com['username']); ?>:</strong>
                <span class="text-secondary small"><?php echo htmlspecialchars($comment_body); ?></span>
            </div>
            
            <!-- Comment Action Dropdown with Professional Buttons -->
            <?php if ($com['user_id'] == $user_id): ?>
                <div class="dropdown">
                    <button class="btn btn-sm text-muted py-0" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end p-2 border-0 shadow-sm" style="border-radius: 12px; min-width: 130px;">
                        <li class="mb-1">
                            <button type="button" class="dropdown-item btn btn-outline-primary btn-sm rounded-pill d-flex align-items-center gap-2 edit-comment-btn text-primary fw-semibold px-3 py-1" data-commentid="<?php echo $com['id']; ?>" data-content="<?php echo htmlspecialchars($comment_body); ?>">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                        </li>
                        <li>
                            <a href="delete_comment.php?id=<?php echo $com['id']; ?>" class="dropdown-item btn btn-outline-danger btn-sm rounded-pill d-flex align-items-center gap-2 text-danger fw-semibold px-3 py-1" onclick="return confirm('Are you sure you want to delete this comment?');">
                                <i class="fa-solid fa-trash-can"></i> Delete
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

                    <!-- Add Comment Form -->
                    <form action="dashboard.php" method="POST" class="d-flex gap-2">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <input type="text" name="comment_content" class="form-control form-control-sm rounded-pill bg-light border-0 px-3" placeholder="Write a comment..." required autocomplete="off">
                        <button type="submit" name="add_comment" class="btn btn-sm btn-primary rounded-circle px-3"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<!-- Edit Post Modal -->
<div class="modal fade" id="editPostModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="edit_post.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="post_id" id="edit_post_id">
                    <textarea name="content" id="edit_post_content" class="form-control" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Comment Modal -->
<div class="modal fade" id="editCommentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="edit_comment.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="comment_id" id="edit_comment_id">
                    <textarea name="content" id="edit_comment_content" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Comment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Sidebar real-time unread counter
    function updateGlobalUnreadCount() {
        $.getJSON('fetch_unread.php', function(data) {
            let totalUnread = 0;
            for (let uid in data) {
                totalUnread += parseInt(data[uid]);
            }
            const sidebarBadge = $('#sidebarUnreadBadge');
            if (sidebarBadge.length) {
                if (totalUnread > 0) {
                    sidebarBadge.text(totalUnread).css('display', 'inline-block');
                } else {
                    sidebarBadge.text('0').css('display', 'none');
                }
            }
        });
    }

    $(document).ready(function() {
        updateGlobalUnreadCount();
        setInterval(updateGlobalUnreadCount, 2000);

        // Edit Post Modal Trigger
        $(document).on('click', '.edit-post-btn', function() {
            const postId = $(this).data('postid');
            const content = $(this).data('content');
            $('#edit_post_id').val(postId);
            $('#edit_post_content').val(content);
            new bootstrap.Modal(document.getElementById('editPostModal')).show();
        });

        // Edit Comment Modal Trigger
        $(document).on('click', '.edit-comment-btn', function() {
            const commentId = $(this).data('commentid');
            const content = $(this).data('content');
            $('#edit_comment_id').val(commentId);
            $('#edit_comment_content').val(content);
            new bootstrap.Modal(document.getElementById('editCommentModal')).show();
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>