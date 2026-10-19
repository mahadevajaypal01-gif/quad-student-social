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

// Handle File Deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $user_id]);
    $note_to_delete = $stmt->fetch();
    
    if ($note_to_delete) {
        if (!empty($note_to_delete['file_path']) && file_exists($note_to_delete['file_path'])) {
            unlink($note_to_delete['file_path']);
        }
        
        $del_stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ? AND user_id = ?");
        $del_stmt->execute([$delete_id, $user_id]);
        
        header("Location: assignments.php");
        exit();
    }
}

// Handle File Upload
$upload_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_note'])) {
    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    
    if (!empty($title) && !empty($subject) && isset($_FILES['note_file'])) {
        $file = $_FILES['note_file'];
        $filename = time() . '_' . basename($file['name']);
        $target_dir = "uploads/";
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $target_file = $target_dir . $filename;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'png', 'jpg', 'jpeg'];
        
        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $ins = $pdo->prepare("INSERT INTO assignments (user_id, title, subject, file_path, created_at) VALUES (?, ?, ?, ?, NOW())");
                $ins->execute([$user_id, $title, $subject, $target_file]);
                header("Location: assignments.php");
                exit();
            } else {
                $upload_error = "Failed to upload file.";
            }
        } else {
            $upload_error = "Invalid file type. Only PDF, DOC, PPT, TXT, and Images are allowed.";
        }
    }
}

// Fetch all assignments with uploader names
$query = "SELECT assignments.*, users.username, users.full_name FROM assignments 
          JOIN users ON assignments.user_id = users.id 
          ORDER BY assignments.created_at DESC";
$assignments = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quad - Study Assignments & Notes</title>
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
                <a href="dashboard.php" class="nav-link-custom"><i class="fa-solid fa-house fs-5"></i> Home</a>
                <a href="chat.php" class="nav-link-custom justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-comments fs-5"></i> Messages
                    </div>
                    <span class="badge bg-primary rounded-pill" id="sidebarUnreadBadge" style="display: none;">0</span>
                </a>
                <a href="assignments.php" class="nav-link-custom active"><i class="fa-solid fa-book-open fs-5"></i> Study Notes</a>
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
            
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="fw-bold text-dark m-0"><i class="fa-solid fa-book-bookmark text-primary me-2"></i> Study Assignments & Notes</h4>
                <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fa-solid fa-upload me-1"></i> Upload Note
                </button>
            </div>

            <?php if (!empty($upload_error)): ?>
                <div class="alert alert-danger rounded-3"><?php echo $upload_error; ?></div>
            <?php endif; ?>

            <div class="d-flex flex-column gap-3">
                <?php if (count($assignments) > 0): ?>
                    <?php foreach ($assignments as $note): ?>
                        <?php 
                            $note_author_name = !empty($note['full_name']) ? $note['full_name'] : $note['username'];
                            $ext = strtolower(pathinfo($note['file_path'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                                $view_icon = 'fa-solid fa-image';
                            } elseif ($ext === 'pdf') {
                                $view_icon = 'fa-solid fa-file-pdf';
                            } elseif (in_array($ext, ['doc', 'docx'])) {
                                $view_icon = 'fa-solid fa-file-word';
                            } elseif (in_array($ext, ['ppt', 'pptx'])) {
                                $view_icon = 'fa-solid fa-file-powerpoint';
                            } else {
                                $view_icon = 'fa-solid fa-file-lines';
                            }
                        ?>
                        <div class="card custom-card p-4">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div class="d-flex gap-3">
                                    <div class="avatar bg-success">
                                        <i class="fa-solid fa-file-lines text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($note['title']); ?></h5>
                                        <span class="badge bg-light text-primary border mb-2"><?php echo htmlspecialchars($note['subject']); ?></span>
                                        <div class="small text-muted">Shared by <strong><?php echo htmlspecialchars($note_author_name); ?></strong> • <?php echo date('M d, Y • h:i A', strtotime($note['created_at'])); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 align-items-center flex-wrap">
                                    <a href="<?php echo htmlspecialchars($note['file_path']); ?>" target="_blank" class="btn btn-outline-primary rounded-pill px-3 fw-bold">
                                        <i class="<?php echo $view_icon; ?> me-1"></i> View
                                    </a>
                                    <a href="<?php echo htmlspecialchars($note['file_path']); ?>" class="btn btn-outline-primary rounded-pill px-3 fw-bold" download>
                                        <i class="fa-solid fa-download me-1"></i> Download
                                    </a>
                                    <?php if ($note['user_id'] == $user_id): ?>
                                        <a href="assignments.php?delete_id=<?php echo $note['id']; ?>" class="btn btn-outline-danger rounded-pill px-3 fw-bold" onclick="return confirm('Are you sure you want to delete this note?');">
                                            <i class="fa-solid fa-trash me-1"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted card custom-card">
                        <i class="fa-solid fa-folder-open fs-1 mb-3 text-secondary"></i>
                        <p class="mb-0 fs-5">No study notes uploaded yet. Be the first to share!</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Upload Study Note / Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="assignments.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Note / Assignment Title</label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="e.g., Data Structures Unit 1 Notes" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject Name</label>
                        <input type="text" name="subject" class="form-control rounded-3" placeholder="e.g., B.Sc. IT - Sem 3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select File (PDF, DOCX, PPT, Image)</label>
                        <input type="file" name="note_file" class="form-control rounded-3" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_note" class="btn btn-primary rounded-pill px-4 fw-bold">Upload File</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="sidebar_notifications.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>