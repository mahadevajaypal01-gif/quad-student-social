<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle Profile Updates (Name, Username, Bio, DP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $bio = trim($_POST['bio']);
    
    // Check if username is taken by another user
    $check_user = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $check_user->execute([$username, $user_id]);
    
    if ($check_user->rowCount() > 0) {
        $error_message = "Username is already taken by another user.";
    } else {
        // Profile Picture Upload Handling
        $profile_pic_path = null;
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_pic']['tmp_name'];
            $file_name = $_FILES['profile_pic']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $new_file_name = "dp_" . $user_id . "_" . time() . "." . $ext;
                $target_dir = "uploads/";
                
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $target_path = $target_dir . $new_file_name;
                if (move_uploaded_file($file_tmp, $target_path)) {
                    $profile_pic_path = $target_path;
                }
            } else {
                $error_message = "Invalid image format. Allowed: JPG, PNG, GIF, WEBP.";
            }
        }

        if (empty($error_message)) {
            if ($profile_pic_path) {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, bio = ?, profile_pic = ? WHERE id = ?");
                $stmt->execute([$username, $full_name, $bio, $profile_pic_path, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, bio = ? WHERE id = ?");
                $stmt->execute([$username, $full_name, $bio, $user_id]);
            }
            $success_message = "Profile updated successfully!";
        }
    }
}

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$user_name = !empty($user['full_name']) ? $user['full_name'] : $user['username'];
$user_dp = !empty($user['profile_pic']) && file_exists($user['profile_pic']) ? $user['profile_pic'] : null;

// Fetch user's posts
$posts_stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$posts_stmt->execute([$user_id]);
$user_posts = $posts_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quad - Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #ffffff; border-right: 1px solid #e4e6eb; display: flex; flex-direction: column; justify-content: space-between; padding: 24px 20px; position: sticky; top: 0; height: 100vh; }
        .content-area { flex: 1; padding: 30px 20px; display: flex; justify-content: center; overflow-y: auto; }
        .feed-container { width: 100%; max-width: 780px; display: flex; flex-direction: column; gap: 20px; }
        .nav-link-custom { display: flex; align-items: center; gap: 14px; padding: 12px 18px; color: #4b5563; border-radius: 12px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; }
        .nav-link-custom:hover, .nav-link-custom.active { background-color: #eef2ff; color: #4f46e5; }
        .custom-card { border: none; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); background: #ffffff; }
        .avatar-lg { width: 90px; height: 90px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 2.2rem; flex-shrink: 0; object-fit: cover; }
        .avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.1rem; flex-shrink: 0; object-fit: cover; }
        .user-profile-link { border-radius: 12px; padding: 8px; transition: background-color 0.2s ease; }
        .user-profile-link:hover { background-color: #eef2ff; }
        
        .avatar-container { position: relative; display: inline-block; cursor: pointer; }
        .edit-badge {
            position: absolute;
            bottom: 2px;
            right: 2px;
            background-color: #4f46e5;
            color: #ffffff;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ffffff;
            font-size: 0.75rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        .avatar-container:hover .edit-badge {
            transform: scale(1.1);
            background-color: #4338ca;
        }
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
                <a href="about.php" class="nav-link-custom"><i class="fa-solid fa-circle-info fs-5"></i> About</a>
                <a href="contact.php" class="nav-link-custom"><i class="fa-solid fa-envelope fs-5"></i> Contact</a>
            </nav>
        </div>

        <div>
            <a href="profile.php" class="d-flex align-items-center gap-3 mb-3 px-2 text-decoration-none user-profile-link bg-light">
                <?php if ($user_dp): ?>
                    <img src="<?php echo htmlspecialchars($user_dp); ?>" class="avatar" alt="DP">
                <?php else: ?>
                    <div class="avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <?php endif; ?>
                <div class="overflow-hidden">
                    <div class="fw-bold text-dark text-truncate"><?php echo htmlspecialchars($user_name); ?></div>
                    <small class="text-primary fw-semibold">Editing Profile</small>
                </div>
            </a>
            <a href="logout.php" class="btn btn-outline-danger w-100 rounded-pill"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
        </div>
    </aside>

    <main class="content-area">
        <div class="feed-container">
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success rounded-3"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger rounded-3"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card custom-card p-4">
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <div class="d-flex align-items-center gap-4 mb-4">
                        <label for="profile_pic" class="avatar-container m-0" title="Click to change profile picture">
                            <?php if ($user_dp): ?>
                                <img src="<?php echo htmlspecialchars($user_dp); ?>" class="avatar-lg" alt="DP">
                            <?php else: ?>
                                <div class="avatar-lg"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                            <?php endif; ?>
                            <div class="edit-badge">
                                <i class="fa-solid fa-pencil"></i>
                            </div>
                        </label>
                        <input type="file" name="profile_pic" id="profile_pic" class="d-none" accept="image/*">

                        <div>
                            <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($user_name); ?></h4>
                            <p class="text-muted mb-0"><i class="fa-solid fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25">

                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-user-gear text-primary me-2"></i> Account Details</h5>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Full Name</label>
                            <input type="text" name="full_name" class="form-control rounded-3 p-2 px-3" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="e.g. Mahadev Ajay Pal">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Username</label>
                            <input type="text" name="username" class="form-control rounded-3 p-2 px-3" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">Bio / About Me</label>
                        <textarea name="bio" class="form-control rounded-3 bg-light border-0 p-3" rows="3" placeholder="Write something about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary rounded-pill px-4 fw-bold">Save Changes</button>
                </form>
            </div>

            <h4 class="fw-bold text-dark mt-2 mb-0"><i class="fa-solid fa-newspaper text-primary me-2"></i> My Posts</h4>
            <div class="d-flex flex-column gap-3">
                <?php if (count($user_posts) > 0): ?>
                    <?php foreach ($user_posts as $post): ?>
                        <div class="card custom-card p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted"><?php echo date('M d, Y • h:i A', strtotime($post['created_at'])); ?></small>
                            </div>
                            <p class="text-secondary mb-0"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted card custom-card">
                        <p class="mb-0">You haven't posted anything yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="sidebar_notifications.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>