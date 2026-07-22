<?php
// index.php - Sandip University Internal Task Planner Landing Page
require_once 'config.php';

// Redirect to dashboard if session already exists
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Fetch Announcements
try {
    $stmt = $pdo->query("SELECT a.*, u.name as author FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY date_posted DESC LIMIT 5");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $announcements = [];
}

// Fetch Upcoming Meetings
try {
    $stmt = $pdo->query("SELECT m.*, u.name as organizer FROM meetings m JOIN users u ON m.creator_id = u.id WHERE m.date_time >= NOW() ORDER BY m.date_time ASC LIMIT 3");
    $upcoming_meetings = $stmt->fetchAll();
} catch (PDOException $e) {
    $upcoming_meetings = [];
}

// Fetch notices for marquee
$notices = [];
foreach ($announcements as $a) {
    $notices[] = $a['title'] . ' (' . date('d M', strtotime($a['date_posted'])) . ')';
}
$marquee_text = !empty($notices) ? implode(' &nbsp; | &nbsp; ', $notices) : "Welcome to Sandip University Task Planner & Meeting Scheduler Portal.";

$login_error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sandip University Internal Task Planner & Meeting Scheduler</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- FullCalendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <style>
        .landing-header-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: var(--accent-color);
            color: var(--primary-color);
            border-radius: 12px;
            font-size: 24px;
        }
    </style>
</head>
<body class="landing-body">

    <!-- Navbar -->
    <nav class="landing-navbar">
        <div class="landing-brand">
            <div class="landing-header-logo">
                <i data-lucide="graduation-cap"></i>
            </div>
            <div class="d-flex flex-column text-start">
                <h1 class="mb-0 text-white font-weight-800" style="font-size: 20px; line-height: 1;">SANDIP UNIVERSITY</h1>
                <span style="font-size: 10px; color: var(--accent-color); font-weight: 700; letter-spacing: 0.8px;">INTERNAL TASK PLANNER & MEETING SCHEDULER</span>
            </div>
        </div>
        <div class="d-none d-md-flex align-items-center gap-3">
            <span class="text-white-50 font-size-13" style="font-size: 13px;">Help Desk: support@sandip.edu.in</span>
        </div>
    </nav>

    <!-- Notices Marquee -->
    <div class="notice-marquee-container">
        <span class="notice-badge">Notice Board</span>
        <div class="marquee-content">
            <span><?php echo sanitize($marquee_text); ?></span>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="landing-grid">
        
        <!-- Left Side: Announcements, Upcoming Meetings, Calendar -->
        <div class="text-start">
            
            <!-- Announcements -->
            <div class="landing-panel">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i data-lucide="megaphone" style="color: var(--accent-color);"></i>
                    <h2 class="m-0 border-0 p-0 text-white">University Announcements</h2>
                </div>
                <ul class="landing-list">
                    <?php if (empty($announcements)): ?>
                        <li class="text-white-50">No announcements posted recently.</li>
                    <?php else: ?>
                        <?php foreach ($announcements as $a): ?>
                            <li>
                                <div class="landing-list-title"><?php echo sanitize($a['title']); ?></div>
                                <div class="landing-list-meta">
                                    <span><i data-lucide="user" style="width:12px; vertical-align: middle;"></i> <?php echo sanitize($a['author']); ?></span>
                                    <span><i data-lucide="calendar" style="width:12px; vertical-align: middle;"></i> <?php echo date('F d, Y', strtotime($a['date_posted'])); ?></span>
                                </div>
                                <div class="text-white-50 font-size-13" style="font-size: 13px;"><?php echo nl2br(sanitize($a['content'])); ?></div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Upcoming Public Meetings -->
            <div class="landing-panel">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i data-lucide="calendar-days" style="color: var(--accent-color);"></i>
                    <h2 class="m-0 border-0 p-0 text-white">Upcoming Meetings</h2>
                </div>
                <ul class="landing-list">
                    <?php if (empty($upcoming_meetings)): ?>
                        <li class="text-white-50">No meetings scheduled for this week.</li>
                    <?php else: ?>
                        <?php foreach ($upcoming_meetings as $m): ?>
                            <li>
                                <div class="landing-list-title"><?php echo sanitize($m['title']); ?></div>
                                <div class="landing-list-meta">
                                    <span><i data-lucide="user" style="width:12px; vertical-align: middle;"></i> Organiser: <?php echo sanitize($m['organizer']); ?></span>
                                    <span><i data-lucide="clock" style="width:12px; vertical-align: middle;"></i> <?php echo date('h:i A, M d, Y', strtotime($m['date_time'])); ?></span>
                                    <?php if (!empty($m['room_link'])): ?>
                                        <span><i data-lucide="video" style="width:12px; vertical-align: middle;"></i> Google Meet Link</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-white-50 font-size-13 mb-0" style="font-size: 13px;"><?php echo nl2br(sanitize($m['description'])); ?></p>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Calendar Preview -->
            <div class="landing-panel">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i data-lucide="calendar" style="color: var(--accent-color);"></i>
                    <h2 class="m-0 border-0 p-0 text-white">Event Calendar</h2>
                </div>
                <div id="landing-calendar" style="color: var(--text-dark);"></div>
            </div>

        </div>

        <!-- Right Side: Login Panel -->
        <div class="text-start">
            <div class="login-card">
                <h2>Portal Access</h2>
                <p>Login to view your tasks, schedule meetings, submit reports, and review documents.</p>

                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger py-2" style="font-size: 13px;">
                        <i data-lucide="alert-circle" style="width:16px; vertical-align:middle; margin-right:5px;"></i>
                        <?php echo sanitize($login_error); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="mail" style="width:16px; color:var(--text-muted);"></i></span>
                            <input type="email" name="email" class="form-control bg-light border-start-0" id="login-email" placeholder="e.g. dean@sandip.edu.in" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label font-size-13 font-weight-600">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="lock" style="width:16px; color:var(--text-muted);"></i></span>
                            <input type="password" name="password" class="form-control bg-light border-start-0" id="login-password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 font-weight-600" style="background-color: var(--primary-color); border-color: var(--primary-color);">
                        Log In
                    </button>
                </form>

                <hr class="my-4">

                <h6 class="font-weight-600 text-muted mb-3" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Quick Demo Accounts (Password: password123)</h6>
                <div class="quick-login-grid">
                    <button class="quick-login-btn" onclick="quickLogin('admin@sandip.edu.in')">
                        <i data-lucide="shield-check" style="width: 18px;"></i>
                        <span>Admin</span>
                    </button>
                    <button class="quick-login-btn" onclick="quickLogin('registrar@sandip.edu.in')">
                        <i data-lucide="bar-chart-horizontal" style="width: 18px;"></i>
                        <span>Registrar</span>
                    </button>
                    <button class="quick-login-btn" onclick="quickLogin('dean@sandip.edu.in')">
                        <i data-lucide="award" style="width: 18px;"></i>
                        <span>Dean / HOD</span>
                    </button>
                    <button class="quick-login-btn" onclick="quickLogin('faculty@sandip.edu.in')">
                        <i data-lucide="user" style="width: 18px;"></i>
                        <span>Faculty</span>
                    </button>
                    <button class="quick-login-btn" onclick="quickLogin('coordinator@sandip.edu.in')">
                        <i data-lucide="users-2" style="width: 18px;"></i>
                        <span>Coordinator</span>
                    </button>
                </div>
            </div>
            
            <div class="text-center mt-4 text-white-50" style="font-size: 12px;">
                <p class="mb-1">Sandip University Internal Task Planner Portal</p>
                <a href="#" class="text-warning text-decoration-none" id="help-desk-trigger">Access Help & Support Ticket Desk</a>
            </div>
        </div>

    </div>

    <!-- Script triggers -->
    <script>
        lucide.createIcons();

        // Fill form fields automatically
        function quickLogin(email) {
            document.getElementById('login-email').value = email;
            document.getElementById('login-password').value = 'password123';
        }

        // Initialize Calendar
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('landing-calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 380,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                events: [
                    <?php
                    // Render upcoming meetings inside fullcalendar directly on landing page
                    foreach ($upcoming_meetings as $m) {
                        echo "{
                            title: '" . addslashes($m['title']) . "',
                            start: '" . date('Y-m-d\TH:i:s', strtotime($m['date_time'])) . "',
                            color: '#ff9f1c'
                        },";
                    }
                    ?>
                ]
            });
            calendar.render();
        });

        // Setup simple redirect for help desk link on landing page
        document.getElementById('help-desk-trigger').addEventListener('click', function(e) {
            e.preventDefault();
            alert("Please log in using one of the demo credentials to submit a support ticket to the Help Desk.");
        });
    </script>
</body>
</html>
