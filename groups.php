<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Create Group
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_group'])) {
    $group_name = trim($_POST['group_name']);
    $description = trim($_POST['description']);

    if (!empty($group_name)) {
        $stmt = $pdo->prepare("INSERT INTO groups (group_name, description, created_by, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$group_name, $description, $user_id]);
        $group_id = $pdo->lastInsertId();

        // Automatically add creator as member
        $mem_stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())");
        $mem_stmt->execute([$group_id, $user_id]);

        header("Location: groups.php");
        exit();
    }
}

// Handle Join/Leave Group
if (isset($_GET['action'], $_GET['group_id'])) {
    $group_id = intval($_GET['group_id']);
    $action = $_GET['action'];

    if ($action == 'join') {
        $chk = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
        $chk->execute([$group_id, $user_id]);
        if ($chk->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())");
            $stmt->execute([$group_id, $user_id]);
        }
    } elseif ($action == 'leave') {
        $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$group_id, $user_id]);
    }
    header("Location: groups.php");
    exit();
}

// Fetch all groups with creator info and member count
$groups_stmt = $pdo->prepare("SELECT groups.*, users.username, users.full_name, (SELECT COUNT(*) FROM group_members WHERE group_members.group_id = groups.id) as member_count, (SELECT COUNT(*) FROM group_members WHERE group_members.group_id = groups.id AND group_members.user_id = ?) as is_joined FROM groups JOIN users ON groups.created_by = users.id ORDER BY groups.id DESC");
$groups_stmt->execute([$user_id]);
$groups = $groups_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quad - Study Groups</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #ffffff; border-right: 1px solid #e4e6eb; display: flex; flex-direction: column; justify-content: space-between; padding: 24px 20px; position: sticky; top: 0; height: 100vh; }
        .content-area { flex: 1; padding: 30px; overflow-y: auto; max-width: 850px; margin: 0 auto; }
        .custom-card { border: none; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); background: #ffffff; margin-bottom: 20px; }
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
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="fw-bold m-0">Study Groups</h4>
            <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#createGroupModal"><i class="fa-solid fa-plus me-2"></i>Create Group</button>
        </div>

        <div class="row g-3">
            <?php foreach ($groups as $g): ?>
                <?php $creator_name = !empty($g['full_name']) ? $g['full_name'] : $g['username']; ?>
                <div class="col-md-6">
                    <div class="card custom-card p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($g['group_name']); ?></h5>
                            <small class="text-muted d-block mb-3">Created by <?php echo htmlspecialchars($creator_name); ?> • <?php echo $g['member_count']; ?> Members</small>
                            <p class="text-secondary small mb-3"><?php echo nl2br(htmlspecialchars($g['description'])); ?></p>
                        </div>
                        <div>
                            <?php if ($g['is_joined'] > 0): ?>
                                <a href="groups.php?action=leave&group_id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-4 fw-semibold">Leave Group</a>
                            <?php else: ?>
                                <a href="groups.php?action=join&group_id=<?php echo $g['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold">Join Group</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="groups.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create New Study Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Group Name</label>
                        <input type="text" name="group_name" class="form-control rounded-pill px-3" required autocomplete="off" placeholder="e.g. B.Sc. IT Semester 3 Discussion">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control rounded-4 p-3" rows="3" placeholder="What is this group about?" style="resize: none;"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="create_group" class="btn btn-primary rounded-pill px-4 fw-bold">Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>