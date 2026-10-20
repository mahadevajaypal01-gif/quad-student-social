<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Follow / Unfollow actions
if (isset($_GET['action'], $_GET['id'])) {
    $target_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'follow' && $target_id != $user_id) {
        $chk = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
        $chk->execute([$user_id, $target_id]);
        if ($chk->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $target_id]);
        }
    } elseif ($action == 'unfollow') {
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$user_id, $target_id]);
    }
    header("Location: follows.php");
    exit();
}

// Fetch Users I Am Following
$following_stmt = $pdo->prepare("SELECT users.id, users.username, users.full_name, users.profile_pic FROM users JOIN follows ON follows.following_id = users.id WHERE follows.follower_id = ?");
$following_stmt->execute([$user_id]);
$following_list = $following_stmt->fetchAll();

// Fetch My Followers
$followers_stmt = $pdo->prepare("SELECT users.id, users.username, users.full_name, users.profile_pic FROM users JOIN follows ON follows.follower_id = users.id WHERE follows.following_id = ?");
$followers_stmt->execute([$user_id]);
$followers_list = $followers_stmt->fetchAll();

// Discover New Students to Follow
$discover_stmt = $pdo->prepare("SELECT * FROM users WHERE id != ? AND id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)");
$discover_stmt->execute([$user_id, $user_id]);
$discover_users = $discover_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quad - Follow Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #ffffff; border-right: 1px solid #e4e6eb; display: flex; flex-direction: column; justify-content: space-between; padding: 24px 20px; position: sticky; top: 0; height: 100vh; }
        .content-area { flex: 1; padding: 30px; overflow-y: auto; max-width: 850px; margin: 0 auto; }
        .custom-card { border: none; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); background: #ffffff; margin-bottom: 20px; }
        .avatar { width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0; object-fit: cover; }
        .nav-link-custom { display: flex; align-items: center; gap: 14px; padding: 12px 18px; color: #4b5563; border-radius: 12px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; }
        .nav-link-custom:hover, .nav-link-custom.active { background-color: #eef2ff; color: #4f46e5; }
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
            <a href="logout.php" class="btn btn-outline-danger w-100 rounded-pill"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="content-area">
        <h4 class="fw-bold mb-4">Follow & Network</h4>

        <!-- Following List -->
        <h5 class="fw-bold text-muted mb-3">Following (<?php echo count($following_list); ?>)</h5>
        <div class="row g-3 mb-4">
            <?php if (count($following_list) > 0): ?>
                <?php foreach ($following_list as $f): ?>
                    <?php 
                        $f_name = !empty($f['full_name']) ? $f['full_name'] : $f['username'];
                        $f_dp = !empty($f['profile_pic']) && file_exists($f['profile_pic']) ? $f['profile_pic'] : null;
                    ?>
                    <div class="col-md-6">
                        <div class="card custom-card p-3 d-flex flex-row align-items-center justify-content-between h-100">
                            <div class="d-flex align-items-center gap-3">
                                <?php if ($f_dp): ?>
                                    <img src="<?php echo htmlspecialchars($f_dp); ?>" class="avatar" alt="DP">
                                <?php else: ?>
                                    <div class="avatar"><?php echo strtoupper(substr($f_name, 0, 1)); ?></div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="fw-bold text-dark m-0"><?php echo htmlspecialchars($f_name); ?></h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($f['username']); ?></small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="chat.php?user_id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2"><i class="fa-solid fa-comment"></i></a>
                                <a href="follows.php?action=unfollow&id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">Unfollow</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><div class="text-muted">You are not following anyone yet.</div></div>
            <?php endif; ?>
        </div>

        <!-- Discover Students to Follow -->
        <h5 class="fw-bold text-muted mb-3">Discover Students to Follow</h5>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($discover_users as $du): ?>
                <?php 
                    $du_name = !empty($du['full_name']) ? $du['full_name'] : $du['username'];
                    $du_dp = !empty($du['profile_pic']) && file_exists($du['profile_pic']) ? $du['profile_pic'] : null;
                ?>
                <div class="card custom-card p-3 d-flex flex-row align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($du_dp): ?>
                            <img src="<?php echo htmlspecialchars($du_dp); ?>" class="avatar" alt="DP">
                        <?php else: ?>
                            <div class="avatar"><?php echo strtoupper(substr($du_name, 0, 1)); ?></div>
                        <?php endif; ?>
                        <div>
                            <h6 class="fw-bold text-dark m-0"><?php echo htmlspecialchars($du_name); ?></h6>
                            <small class="text-muted">@<?php echo htmlspecialchars($du['username']); ?></small>
                        </div>
                    </div>
                    <div>
                        <a href="follows.php?action=follow&id=<?php echo $du['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold">
                            <i class="fa-solid fa-user-plus me-1"></i> Follow
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>