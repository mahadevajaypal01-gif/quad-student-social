<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search_query = "";
$users = [];

if (isset($_GET['query'])) {
    $search_query = trim($_GET['query']);
    if (!empty($search_query)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username LIKE ? OR full_name LIKE ?) AND id != ?");
        $stmt->execute(["%$search_query%", "%$search_query%", $user_id]);
        $users = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quad - Search Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #ffffff; border-right: 1px solid #e4e6eb; display: flex; flex-direction: column; justify-content: space-between; padding: 24px 20px; position: sticky; top: 0; height: 100vh; }
        .content-area { flex: 1; padding: 30px; overflow-y: auto; max-width: 850px; margin: 0 auto; }
        .custom-card { border: none; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); background: #ffffff; margin-bottom: 20px; }
        .avatar { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0; object-fit: cover; }
        .nav-link-custom { display: flex; align-items: center; gap: 14px; padding: 12px 18px; color: #4b5563; border-radius: 12px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; }
        .nav-link-custom:hover, .nav-link-custom.active { background-color: #eef2ff; color: #4f46e5; }
        #suggestionsContainer { position: absolute; width: calc(100% - 48px); z-index: 1000; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); background: #ffffff; margin-top: 5px; }
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
        <div class="card custom-card p-4 mb-4 position-relative">
            <h4 class="fw-bold mb-3">Find Students</h4>
            <form action="search.php" method="GET" class="d-flex align-items-center gap-2 position-relative">
    <div class="flex-grow-1 position-relative">
        <input type="text" id="searchInput" name="query" class="form-control bg-light border-0 px-3 rounded-pill" placeholder="Search by name or username..." value="<?php echo htmlspecialchars($search_query); ?>" required autocomplete="off" style="height: 48px;">
        <div id="suggestionsContainer" style="display: none;"></div>
    </div>
    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold d-flex align-items-center justify-content-center gap-2 text-nowrap" style="height: 48px; min-width: 120px;">
        <i class="fa-solid fa-magnifying-glass"></i> Search
    </button>
</form>
        </div>

        <div class="d-flex flex-column gap-3">
            <?php if (isset($_GET['query'])): ?>
                <?php if (count($users) > 0): ?>
                    <h5 class="fw-bold text-muted mb-2">Search Results</h5>
                    <?php foreach ($users as $u): ?>
                        <?php 
                            $u_name = !empty($u['full_name']) ? $u['full_name'] : $u['username'];
                            $u_dp = !empty($u['profile_pic']) && file_exists($u['profile_pic']) ? $u['profile_pic'] : null;
                        ?>
                        <div class="card custom-card p-3 d-flex flex-row align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <?php if ($u_dp): ?>
                                    <img src="<?php echo htmlspecialchars($u_dp); ?>" class="avatar" alt="DP">
                                <?php else: ?>
                                    <div class="avatar"><?php echo strtoupper(substr($u_name, 0, 1)); ?></div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="fw-bold text-dark m-0"><?php echo htmlspecialchars($u_name); ?></h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($u['username']); ?></small>
                                </div>
                            </div>
                            <div>
                                <a href="chat.php?user_id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                                    <i class="fa-solid fa-comment me-1"></i> Message
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">No students found matching "<?php echo htmlspecialchars($search_query); ?>".</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#searchInput').on('keyup', function() {
            let query = $(this).val().trim();
            if (query.length > 0) {
                $.ajax({
                    url: 'search_ajax.php',
                    method: 'GET',
                    data: { query: query },
                    success: function(data) {
                        $('#suggestionsContainer').html(data).show();
                    }
                });
            } else {
                $('#suggestionsContainer').hide();
            }
        });

        // Hide suggestions when clicking outside
        $(document).click(function(e) {
            if (!$(e.target).closest('#searchInput, #suggestionsContainer').length) {
                $('#suggestionsContainer').hide();
            }
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>