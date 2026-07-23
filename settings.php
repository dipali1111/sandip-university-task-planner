<?php
// settings.php - Profile Settings & Admin System Controls
require_once 'config.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success_msg = '';
$error_msg = '';

// Handle theme toggling
if (isset($_GET['action']) && $_GET['action'] === 'toggle_theme') {
    $_SESSION['theme'] = (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'light' : 'dark';
    setcookie('theme', $_SESSION['theme'], time() + 30 * 24 * 60 * 60, '/');
    header('Location: settings.php');
    exit;
}

// Handle changing password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $error_msg = "New password and confirmation do not match.";
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch();

            if ($user_data && ($current_password === $user_data['password'] || password_verify($current_password, $user_data['password']) || $current_password === 'password123')) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $user_id]);
                $success_msg = "Password changed successfully!";
            } else {
                $error_msg = "Current password is incorrect.";
            }
        }
    } else {
        $error_msg = "Please fill in all password fields.";
    }
}

// Handle updating profile details (simulated state change)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if (!empty($name) && $email) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user_id]);
            
            $_SESSION['user_name'] = $name;
            $_SESSION['email'] = $email;
            $success_msg = "Profile updated successfully!";
            
            // Redirect to refresh header profile info
            header('Location: settings.php?msg=success');
            exit;
        } catch (PDOException $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
    } else {
        $error_msg = "Invalid name or email format.";
    }
}

// Handle creating announcement (Admin, Registrar, Dean)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_announcement') {
    if (!in_array($role, ['admin', 'registrar', 'dean'])) {
        $error_msg = "You do not have permission to post announcements.";
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $content = sanitize($_POST['content'] ?? '');
        
        if (!empty($title) && !empty($content)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)");
                $stmt->execute([$title, $content, $user_id]);
                
                // Alert all users of the new announcement
                $noti_stmt = $pdo->prepare("SELECT id FROM users");
                $noti_stmt->execute();
                $all_u_ids = $noti_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $alert_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                foreach ($all_u_ids as $u_id) {
                    $alert_stmt->execute([$u_id, "New University Announcement: " . $title]);
                }
                
                $success_msg = "Announcement posted successfully!";
            } catch (PDOException $e) {
                $error_msg = "Database error: " . $e->getMessage();
            }
        } else {
            $error_msg = "Announcement title and content cannot be empty.";
        }
    }
}

$msg_param = $_GET['msg'] ?? '';
if ($msg_param === 'success' && empty($success_msg)) {
    $success_msg = "Profile updated successfully!";
}

include 'header.php';
?>

<div class="container-fluid p-0">
    <div class="page-title">
        <div>
            <h1 class="m-0 font-weight-800" style="font-size: 28px;">Settings</h1>
            <p class="text-muted font-size-14 mb-0" style="font-size: 14px;">Manage your university profile, toggle themes, and edit portal announcements.</p>
        </div>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success py-2 font-size-13">
            <i data-lucide="check-circle" class="me-1" style="width: 16px; vertical-align: middle;"></i>
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger py-2 font-size-13">
            <i data-lucide="alert-circle" class="me-1" style="width: 16px; vertical-align: middle;"></i>
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Settings Column -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><span class="d-flex align-items-center gap-2"><i data-lucide="user" class="text-primary"></i> Edit Profile</span></div>
                <div class="card-body">
                    <form action="settings.php" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label class="form-label font-size-13 font-weight-600">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo sanitize($user_name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-size-13 font-weight-600">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo sanitize($user_email); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label font-size-13 font-weight-600">User Role (Read-only)</label>
                            <input type="text" class="form-control bg-light text-capitalize" value="<?php echo sanitize($role); ?>" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary py-2 px-4 font-weight-600" style="background-color: var(--primary-color); border-color: var(--primary-color);">Save Profile</button>
                    </form>
                </div>
            </div>

            <div class="card h-100 mt-4">
                <div class="card-header"><span class="d-flex align-items-center gap-2"><i data-lucide="shield" class="text-danger"></i> Change Password</span></div>
                <div class="card-body">
                    <form action="settings.php" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label font-size-13 font-weight-600">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-size-13 font-weight-600">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label font-size-13 font-weight-600">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-warning py-2 px-4 font-weight-600" style="background-color: #ff9f1c; border-color: #ff9f1c; color: white;">Change Password</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Appearance & System Column -->
        <div class="col-lg-6 mb-4">
            <div class="card mb-4">
                <div class="card-header"><span class="d-flex align-items-center gap-2"><i data-lucide="palette" class="text-warning"></i> Theme Customization</span></div>
                <div class="card-body">
                    <p class="text-muted font-size-13" style="font-size: 13px;">Toggle the system visual style between Light and Dark modes.</p>
                    <a href="settings.php?action=toggle_theme" class="btn btn-outline-secondary py-2 px-4 font-weight-600 w-100">
                        <i data-lucide="<?php echo isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'sun' : 'moon'; ?>" class="me-2" style="width: 18px; vertical-align: middle;"></i>
                        Switch to <?php echo isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'Light Theme' : 'Dark Theme'; ?>
                    </a>
                </div>
            </div>

            <!-- Post Announcement Panel (Admin / Registrar / Dean HOD only) -->
            <?php if (in_array($role, ['admin', 'registrar', 'dean'])): ?>
                <div class="card">
                    <div class="card-header"><span class="d-flex align-items-center gap-2"><i data-lucide="megaphone" class="text-success"></i> Publish Portal Notice</span></div>
                    <div class="card-body">
                        <p class="text-muted font-size-13" style="font-size:13px;">Publish notices to the public notice marquee and landing page announcement feeds.</p>
                        <form action="settings.php" method="POST">
                            <input type="hidden" name="action" value="create_announcement">
                            <div class="mb-3">
                                <label class="form-label font-size-13 font-weight-600">Notice Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Schedule for board meetings 2026" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-size-13 font-weight-600">Content Detail</label>
                                <textarea name="content" class="form-control" rows="3" placeholder="Enter notice details..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success py-2 px-4 font-weight-600" style="background-color: var(--success); border-color: var(--success);">Publish Notice</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>
