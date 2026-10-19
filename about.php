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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quad - About Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #ffffff; border-right: 1px solid #e4e6eb; display: flex; flex-direction: column; justify-content: space-between; padding: 24px 20px; position: sticky; top: 0; height: 100vh; }
        .content-area { flex: 1; padding: 30px 20px; display: flex; justify-content: center; overflow-y: auto; }
        .feed-container { width: 100%; max-width: 780px; display: flex; flex-direction: column; gap: 20px; }
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
    <aside class="sidebar">
        <div>
            <div class="d-flex align-items-center gap-2 mb-4 px-2">
                <i class="fa-solid fa-graduation-cap text-primary fs-3"></i>
                <h3 class="fw-bold text-primary m-0">Quad</h3>
            </div>
            
            <nav class="d-flex flex-column gap-2">
                <a href="dashboard.php" class="nav-link-custom"><i class="fa-solid fa-house fs-5"></i> Home</a>
                <a href="chat.php" class="nav-link-custom justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-comments fs-5"></i> Messages
                    </div>
                    <span class="badge bg-primary rounded-pill" id="sidebarUnreadBadge" style="display: none;">0</span>
                </a>
                <a href="assignments.php" class="nav-link-custom"><i class="fa-solid fa-book-open fs-5"></i> Study Notes</a>
                <a href="about.php" class="nav-link-custom active"><i class="fa-solid fa-circle-info fs-5"></i> About</a>
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

    <main class="content-area">
        <div class="feed-container">
            <h4 class="fw-bold text-dark m-0"><i class="fa-solid fa-circle-info text-primary me-2"></i> About Quad</h4>
            <div class="card custom-card p-4">
                <h5 class="fw-bold text-dark">Welcome to Quad!</h5>
                <p class="text-secondary">Quad is a dedicated social collaboration platform for students to share posts, exchange study materials, and connect with peers seamlessly.</p>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="sidebar_notifications.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>