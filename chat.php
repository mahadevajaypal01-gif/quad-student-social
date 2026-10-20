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

// Selected chat partner
$receiver_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$receiver_user = null;

if ($receiver_id) {
    $r_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $r_stmt->execute([$receiver_id]);
    $receiver_user = $r_stmt->fetch();
}

// Fetch all other users
$users_stmt = $pdo->prepare("SELECT * FROM users WHERE id != ? ORDER BY id DESC");
$users_stmt->execute([$user_id]);
$other_users = $users_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quad - Direct Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #ffffff; border-right: 1px solid #e4e6eb; display: flex; flex-direction: column; justify-content: space-between; padding: 24px 20px; position: sticky; top: 0; height: 100vh; }
        .content-area { flex: 1; padding: 20px; display: flex; gap: 20px; height: 100vh; overflow: hidden; }
        .nav-link-custom { display: flex; align-items: center; gap: 14px; padding: 12px 18px; color: #4b5563; border-radius: 12px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; }
        .nav-link-custom:hover, .nav-link-custom.active { background-color: #eef2ff; color: #4f46e5; }
        .custom-card { border: none; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); background: #ffffff; }
        .avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0; object-fit: cover; }
        .user-profile-link { border-radius: 12px; padding: 8px; transition: background-color 0.2s ease; }
        .user-profile-link:hover { background-color: #eef2ff; }
        
        .chat-users-list { width: 320px; overflow-y: auto; }
        .chat-main-area { flex: 1; display: flex; flex-direction: column; justify-content: space-between; height: 100%; }
        .messages-box { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 12px; }
        
        .msg-bubble { max-width: 70%; padding: 10px 16px; border-radius: 18px; font-size: 0.95rem; }
        .msg-sent { background: #4f46e5; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
        .msg-received { background: #eef2ff; color: #1f2937; align-self: flex-start; border-bottom-left-radius: 4px; }
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

    <main class="content-area">
        <!-- Users List Column -->
        <div class="card custom-card p-3 chat-users-list">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-comments text-primary me-2"></i> Chats</h5>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($other_users as $u): ?>
                    <?php 
                        $u_name = !empty($u['full_name']) ? $u['full_name'] : $u['username'];
                        $u_dp = !empty($u['profile_pic']) && file_exists($u['profile_pic']) ? $u['profile_pic'] : null;
                        $is_active = ($receiver_id == $u['id']) ? 'bg-light border-start border-4 border-primary' : '';
                    ?>
                    <a href="chat.php?user_id=<?php echo $u['id']; ?>" class="user-chat-item d-flex align-items-center justify-content-between p-2 rounded-3 text-decoration-none text-dark <?php echo $is_active; ?>" data-userid="<?php echo $u['id']; ?>">
                        <div class="d-flex align-items-center gap-3 overflow-hidden">
                            <?php if ($u_dp): ?>
                                <img src="<?php echo htmlspecialchars($u_dp); ?>" class="avatar" alt="DP">
                            <?php else: ?>
                                <div class="avatar"><?php echo strtoupper(substr($u_name, 0, 1)); ?></div>
                            <?php endif; ?>
                            <div class="overflow-hidden">
                                <div class="fw-bold text-truncate"><?php echo htmlspecialchars($u_name); ?></div>
                                <small class="text-muted text-truncate d-block">@<?php echo htmlspecialchars($u['username']); ?></small>
                            </div>
                        </div>
                        <span class="badge bg-primary rounded-pill unread-badge" style="display: none;">0</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Active Chat Window Area -->
        <div class="card custom-card chat-main-area">
            <?php if ($receiver_user): ?>
                <?php 
                    $rec_name = !empty($receiver_user['full_name']) ? $receiver_user['full_name'] : $receiver_user['username'];
                    $rec_dp = !empty($receiver_user['profile_pic']) && file_exists($receiver_user['profile_pic']) ? $receiver_user['profile_pic'] : null;
                ?>
                <div class="p-3 border-bottom d-flex align-items-center gap-3">
                    <?php if ($rec_dp): ?>
                        <img src="<?php echo htmlspecialchars($rec_dp); ?>" class="avatar" alt="DP">
                    <?php else: ?>
                        <div class="avatar"><?php echo strtoupper(substr($rec_name, 0, 1)); ?></div>
                    <?php endif; ?>
                    <div>
                        <h6 class="fw-bold m-0"><?php echo htmlspecialchars($rec_name); ?></h6>
                        <small class="text-muted">@<?php echo htmlspecialchars($receiver_user['username']); ?></small>
                    </div>
                </div>

                <div class="messages-box" id="messagesBox"></div>

                <form id="chatForm" class="p-3 border-top d-flex gap-2">
                    <input type="hidden" id="receiver_id" value="<?php echo $receiver_id; ?>">
                    <input type="text" id="message_text" class="form-control rounded-pill bg-light border-0 px-3" placeholder="Type a message..." required autocomplete="off">
                    <button type="submit" class="btn btn-primary rounded-circle px-3"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            <?php else: ?>
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                    <i class="fa-regular fa-paper-plane fs-1 mb-3 text-primary"></i>
                    <h5>Select a student to start chatting</h5>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="sidebar_notifications.js"></script>
<script>
    const receiverId = $('#receiver_id').val();

    function fetchMessages() {
        if (!receiverId) return;
        $.get('fetch_messages.php', { receiver_id: receiverId }, function(data) {
            $('#messagesBox').html(data);
        });
    }

    function updateChatListBadges() {
        $.getJSON('fetch_unread.php', function(data) {
            $('.user-chat-item').each(function() {
                const uid = $(this).data('userid');
                const badge = $(this).find('.unread-badge');
                if (data[uid] && data[uid] > 0) {
                    badge.text(data[uid]).show();
                } else {
                    badge.text('').hide();
                }
            });
        });
    }

    updateChatListBadges();
    setInterval(updateChatListBadges, 2000);

    if (receiverId) {
        fetchMessages();
        setInterval(fetchMessages, 2000);

        $('#chatForm').on('submit', function(e) {
            e.preventDefault();
            const message = $('#message_text').val().trim();
            if (message === '') return;

            $.post('send_message.php', { receiver_id: receiverId, message: message }, function() {
                $('#message_text').val('');
                fetchMessages();
                setTimeout(() => {
                    const msgBox = document.getElementById('messagesBox');
                    msgBox.scrollTop = msgBox.scrollHeight;
                }, 300);
            });
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>