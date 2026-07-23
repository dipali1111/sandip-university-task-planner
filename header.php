<?php
// header.php - Sandip University Planner Common Header
require_once 'config.php';
check_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'];
$user_email = $_SESSION['email'];

// Get notifications
$noti_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8");
$noti_stmt->execute([$user_id]);
$notifications = $noti_stmt->fetchAll();

$unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_stmt->execute([$user_id]);
$unread_count = $unread_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sandip University Internal Planner</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- ChartJS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FullCalendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
</head>
<?php
$theme = $_SESSION['theme'] ?? ($_COOKIE['theme'] ?? '');
?>
<body class="<?php echo $theme === 'dark' ? 'dark-theme' : ''; ?>">
    <div id="app-container">
        
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="sidebar-toggle-btn" id="toggle-sidebar">
                        <i data-lucide="menu"></i>
                    </button>
                    <div class="header-search">
                        <form action="tasks.php" method="GET">
                            <i data-lucide="search"></i>
                            <input type="text" name="search" placeholder="Search tasks, meetings...">
                        </form>
                    </div>
                </div>

                <div class="header-right">
                    <!-- Notification Bell -->
                    <div class="notification-bell-container">
                        <button class="notification-bell-btn" id="noti-toggle">
                            <i data-lucide="bell"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="notification-dropdown" id="noti-dropdown">
                            <div class="notification-dropdown-header">
                                <span>Notifications</span>
                                <?php if ($unread_count > 0): ?>
                                    <a href="alerts.php?action=mark_all_read" class="text-decoration-none text-warning font-size-11" style="font-size: 11px;">Mark all read</a>
                                <?php endif; ?>
                            </div>
                            <div class="notification-list">
                                <?php if (empty($notifications)): ?>
                                    <div class="p-3 text-center text-muted font-size-12" style="font-size: 12px;">No notifications</div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $n): ?>
                                        <div class="notification-item <?php echo $n['is_read'] == 0 ? 'unread' : ''; ?>">
                                            <span><?php echo sanitize($n['message']); ?></span>
                                            <span class="time"><?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="p-2 border-top text-center">
                                <a href="alerts.php" class="text-decoration-none text-primary" style="font-size: 12px;">View all notifications</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="user-profile-menu" id="profile-toggle">
                        <div class="profile-avatar">
                            <?php 
                                $parts = explode(' ', $user_name);
                                $initials = '';
                                foreach ($parts as $p) {
                                    $initials .= strtoupper($p[0] ?? '');
                                }
                                echo substr($initials, 0, 2);
                            ?>
                        </div>
                        <div class="profile-info d-none d-md-flex">
                            <span class="profile-name"><?php echo sanitize($user_name); ?></span>
                            <span class="profile-role"><?php 
                                if ($user_role === 'registrar') echo 'Registrar / VC';
                                elseif ($user_role === 'dean') echo 'Dean / HOD';
                                else echo ucfirst($user_role);
                            ?></span>
                        </div>
                        <i class="ms-1" data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
                        <div class="profile-dropdown" id="profile-dropdown">
                            <a href="settings.php"><i data-lucide="user" style="width: 16px;"></i> Profile</a>
                            <a href="settings.php?section=system"><i data-lucide="settings" style="width: 16px;"></i> Settings</a>
                            <hr class="my-1">
                            <a href="logout.php"><i data-lucide="log-out" style="width: 16px;"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>
