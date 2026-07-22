<?php
// dashboard.php - Role-based Dashboard Hub
require_once 'config.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$dept_id = $_SESSION['department_id'];

// Common stats queries
try {
    // Assigned tasks count (role dependent)
    if ($role === 'registrar' || $role === 'admin') {
        $task_count_stmt = $pdo->query("SELECT COUNT(*) FROM tasks");
    } elseif ($role === 'dean') {
        $task_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigner_id = ?");
        $task_count_stmt->execute([$user_id]);
    } else {
        $task_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assignee_id = ?");
        $task_count_stmt->execute([$user_id]);
    }
    $total_tasks = $task_count_stmt->fetchColumn();

    // Upcoming meetings count (role dependent)
    if ($role === 'registrar' || $role === 'admin') {
        $meet_count_stmt = $pdo->query("SELECT COUNT(*) FROM meetings WHERE date_time >= NOW()");
    } else {
        $meet_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM meetings m JOIN meeting_participants mp ON m.id = mp.meeting_id WHERE mp.user_id = ? AND m.date_time >= NOW()");
        $meet_count_stmt->execute([$user_id]);
    }
    $upcoming_meets = $meet_count_stmt->fetchColumn();

    // Pending tasks count (role dependent)
    if ($role === 'registrar' || $role === 'admin') {
        $pending_tasks_count_stmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'completed'");
        $pending_tasks_preview_stmt = $pdo->query("SELECT t.*, u.name as assignee_name FROM tasks t JOIN users u ON t.assignee_id = u.id WHERE t.status != 'completed' ORDER BY t.due_date ASC LIMIT 5");
    } elseif ($role === 'dean') {
        $pending_tasks_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigner_id = ? AND status != 'completed'");
        $pending_tasks_count_stmt->execute([$user_id]);
        $pending_tasks_preview_stmt = $pdo->prepare("SELECT t.*, u.name as assignee_name FROM tasks t JOIN users u ON t.assignee_id = u.id WHERE t.assigner_id = ? AND t.status != 'completed' ORDER BY t.due_date ASC LIMIT 5");
        $pending_tasks_preview_stmt->execute([$user_id]);
    } else {
        $pending_tasks_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assignee_id = ? AND status != 'completed'");
        $pending_tasks_count_stmt->execute([$user_id]);
        $pending_tasks_preview_stmt = $pdo->prepare("SELECT t.*, u.name as assigner_name FROM tasks t JOIN users u ON t.assigner_id = u.id WHERE t.assignee_id = ? AND t.status != 'completed' ORDER BY t.due_date ASC LIMIT 5");
        $pending_tasks_preview_stmt->execute([$user_id]);
    }
    $pending_tasks = $pending_tasks_preview_stmt->fetchAll();
    $pending_tasks_count = $pending_tasks_count_stmt->fetchColumn();

    // Today's meetings count (role dependent)
    if ($role === 'registrar' || $role === 'admin') {
        $today_meets_count_stmt = $pdo->query("SELECT COUNT(*) FROM meetings WHERE DATE(date_time) = CURDATE()");
        $today_meets_preview_stmt = $pdo->query("SELECT m.*, u.name as organizer FROM meetings m JOIN users u ON m.creator_id = u.id WHERE DATE(m.date_time) = CURDATE() ORDER BY m.date_time ASC LIMIT 5");
    } else {
        $today_meets_count_stmt = $pdo->prepare("SELECT COUNT(DISTINCT m.id) FROM meetings m LEFT JOIN meeting_participants mp ON m.id = mp.meeting_id WHERE (m.creator_id = ? OR mp.user_id = ?) AND DATE(m.date_time) = CURDATE()");
        $today_meets_count_stmt->execute([$user_id, $user_id]);
        $today_meets_preview_stmt = $pdo->prepare("SELECT DISTINCT m.*, u.name as organizer FROM meetings m LEFT JOIN meeting_participants mp ON m.id = mp.meeting_id JOIN users u ON m.creator_id = u.id WHERE (m.creator_id = ? OR mp.user_id = ?) AND DATE(m.date_time) = CURDATE() ORDER BY m.date_time ASC LIMIT 5");
        $today_meets_preview_stmt->execute([$user_id, $user_id]);
    }
    $today_meetings = $today_meets_preview_stmt->fetchAll();
    $today_meetings_count = $today_meets_count_stmt->fetchColumn();

    // Documents uploaded
    $doc_count_stmt = $pdo->query("SELECT COUNT(*) FROM documents");
    $total_docs = $doc_count_stmt->fetchColumn();

    // Unread notifications total and preview
    $unread_notifications_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_notifications_count_stmt->execute([$user_id]);
    $unread_notifications_count = $unread_notifications_count_stmt->fetchColumn();

    $unread_notifications_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
    $unread_notifications_stmt->execute([$user_id]);
    $unread_notifications = $unread_notifications_stmt->fetchAll();

} catch (PDOException $e) {
    $pending_tasks = $today_meetings = $unread_notifications = [];
    $pending_tasks_count = $today_meetings_count = $unread_notifications_count = $total_docs = $total_tasks = $upcoming_meets = 0;
}
?>

<div class="container-fluid p-0">
    <div class="page-title">
        <div>
            <h1 class="m-0 font-weight-800" style="font-size: 28px;">Dashboard</h1>
            <p class="text-muted font-size-14 mb-0" style="font-size: 14px;">Welcome back, <strong><?php echo sanitize($user_name); ?></strong>. Here is your overview.</p>
        </div>
        <div>
            <span class="badge bg-primary py-2 px-3 text-capitalize" style="background-color: var(--primary-color) !important; font-size: 13px;">
                Role: <?php echo sanitize($role === 'registrar' ? 'Registrar / VC' : ($role === 'dean' ? 'Dean / HOD' : $role)); ?>
            </span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Assigned Tasks</h3>
                <p><?php echo $total_tasks; ?></p>
            </div>
            <div class="stat-icon primary">
                <i data-lucide="check-square"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Upcoming Meetings</h3>
                <p><?php echo $upcoming_meets; ?></p>
            </div>
            <div class="stat-icon warning">
                <i data-lucide="calendar"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Pending Tasks</h3>
                <p><?php echo $pending_tasks_count; ?></p>
            </div>
            <div class="stat-icon danger">
                <i data-lucide="loader"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Today's Meetings</h3>
                <p><?php echo $today_meetings_count; ?></p>
            </div>
            <div class="stat-icon warning">
                <i data-lucide="calendar"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Unread Notifications</h3>
                <p><?php echo $unread_notifications_count ?? 0; ?></p>
            </div>
            <div class="stat-icon primary">
                <i data-lucide="bell-ring"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Documents Vault</h3>
                <p><?php echo $total_docs; ?></p>
            </div>
            <div class="stat-icon success">
                <i data-lucide="file-text"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <span>Pending Tasks</span>
                    <a href="tasks.php" class="text-decoration-none">View all</a>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_tasks)): ?>
                        <p class="text-muted">No pending tasks at the moment.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($pending_tasks as $task): ?>
                                <li class="mb-3">
                                    <strong><?php echo sanitize($task['title']); ?></strong>
                                    <div class="text-muted small">
                                        <?php if (isset($task['assignee_name'])): ?>Assigned to <?php echo sanitize($task['assignee_name']); ?><?php else: ?>Assigned by <?php echo sanitize($task['assigner_name']); ?><?php endif; ?>
                                        • Due <?php echo date('d M', strtotime($task['due_date'])); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <span>Today's Meetings</span>
                    <a href="meetings.php" class="text-decoration-none">View all</a>
                </div>
                <div class="card-body">
                    <?php if (empty($today_meetings)): ?>
                        <p class="text-muted">No meetings scheduled for today.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($today_meetings as $meet): ?>
                                <li class="mb-3">
                                    <strong><?php echo sanitize($meet['title']); ?></strong>
                                    <div class="text-muted small">
                                        <?php echo date('h:i A', strtotime($meet['date_time'])); ?> • Organizer: <?php echo sanitize($meet['organizer']); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <span>Unread Notifications</span>
                    <a href="alerts.php" class="text-decoration-none">View all</a>
                </div>
                <div class="card-body">
                    <?php if (empty($unread_notifications)): ?>
                        <p class="text-muted">You have no unread notifications.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($unread_notifications as $noti): ?>
                                <li class="mb-3">
                                    <strong><?php echo sanitize($noti['message']); ?></strong>
                                    <div class="text-muted small"><?php echo date('d M, h:i A', strtotime($noti['created_at'])); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ==================================================================
             1. ADMINISTRATOR DASHBOARD VIEW
             ================================================================== -->
        <?php if ($role === 'admin'): ?>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <span>User Management Overview</span>
                        <a href="departments.php" class="btn btn-sm btn-primary py-1 px-3" style="background-color: var(--primary-color); border-color: var(--primary-color);">Manage Members</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Department</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $u_stmt = $pdo->query("SELECT u.*, d.name as dept_name FROM users u LEFT JOIN departments d ON u.department_id = d.id LIMIT 5");
                                    while ($u = $u_stmt->fetch()):
                                    ?>
                                        <tr>
                                            <td><strong><?php echo sanitize($u['name']); ?></strong></td>
                                            <td><?php echo sanitize($u['email']); ?></td>
                                            <td><span class="badge bg-secondary text-capitalize"><?php echo sanitize($u['role']); ?></span></td>
                                            <td><?php echo sanitize($u['dept_name'] ?? 'None'); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Admin System Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="departments.php" class="btn btn-outline-primary text-start p-3 font-weight-600"><i data-lucide="plus-circle" class="me-2" style="width: 18px;"></i> Add Departments & Committees</a>
                            <a href="settings.php" class="btn btn-outline-primary text-start p-3 font-weight-600"><i data-lucide="settings" class="me-2" style="width: 18px;"></i> Change Portal Branding & Notices</a>
                            <a href="alerts.php" class="btn btn-outline-primary text-start p-3 font-weight-600"><i data-lucide="bell-ring" class="me-2" style="width: 18px;"></i> Broadcast Global Notification</a>
                        </div>
                    </div>
                </div>
            </div>

        <!-- ==================================================================
             2. REGISTRAR / VC DASHBOARD VIEW
             ================================================================== -->
        <?php elseif ($role === 'registrar'): ?>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">University Task Completion Performance</div>
                    <div class="card-body">
                        <div style="position: relative; height: 280px; width: 100%;">
                            <canvas id="taskStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">Department Workload Distribution</div>
                    <div class="card-body">
                        <div style="position: relative; height: 280px; width: 100%;">
                            <canvas id="deptWorkloadChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Get dynamic statistics for VC Charts -->
            <?php
            // 1. Task statuses
            $status_stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
            $statuses = ['todo' => 0, 'in_progress' => 0, 'review' => 0, 'completed' => 0];
            while ($row = $status_stmt->fetch()) {
                $statuses[$row['status']] = (int)$row['count'];
            }

            // 2. Department workloads
            $workload_stmt = $pdo->query("
                SELECT d.name, COUNT(t.id) as count 
                FROM departments d 
                LEFT JOIN users u ON d.id = u.department_id 
                LEFT JOIN tasks t ON u.id = t.assignee_id 
                GROUP BY d.id
            ");
            $workload_labels = [];
            $workload_counts = [];
            while ($row = $workload_stmt->fetch()) {
                $name = $row['name'];
                if (strpos($name, 'Computer Science') !== false) {
                    $name = 'CSE';
                } elseif (strpos($name, 'Electrical') !== false) {
                    $name = 'ECE/EEE';
                } elseif (strpos($name, 'Mechanical') !== false) {
                    $name = 'Mechanical';
                } elseif (strpos($name, 'Commerce') !== false) {
                    $name = 'Commerce';
                } elseif (strpos($name, 'Administration') !== false) {
                    $name = 'Admin';
                }
                $workload_labels[] = $name;
                $workload_counts[] = (int)$row['count'];
            }
            ?>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Status Donut Chart
                    const ctxStatus = document.getElementById('taskStatusChart').getContext('2d');
                    new Chart(ctxStatus, {
                        type: 'doughnut',
                        data: {
                            labels: ['To Do', 'In Progress', 'In Review', 'Completed'],
                            datasets: [{
                                data: [
                                    <?php echo $statuses['todo']; ?>,
                                    <?php echo $statuses['in_progress']; ?>,
                                    <?php echo $statuses['review']; ?>,
                                    <?php echo $statuses['completed']; ?>
                                ],
                                backgroundColor: ['#e2e8f0', '#00b4d8', '#ffb703', '#2ec4b6'],
                                borderWidth: 2,
                                borderColor: 'var(--card-light)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { color: 'var(--text-dark)' } }
                            }
                        }
                    });

                    // Department Bar Chart
                    const ctxWorkload = document.getElementById('deptWorkloadChart').getContext('2d');
                    new Chart(ctxWorkload, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode($workload_labels); ?>,
                            datasets: [{
                                label: 'Assigned Tasks',
                                data: <?php echo json_encode($workload_counts); ?>,
                                backgroundColor: 'rgba(15, 43, 92, 0.85)',
                                borderColor: 'var(--primary-color)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, grid: { color: 'var(--border-color)' }, ticks: { color: 'var(--text-dark)' } },
                                x: { grid: { display: false }, ticks: { color: 'var(--text-dark)', callback: function(val, index) {
                                    // Shorten department labels on chart
                                    let text = this.getLabelForValue(val);
                                    return text.length > 15 ? text.substr(0, 15) + '...' : text;
                                } } }
                            },
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                });
            </script>

        <!-- ==================================================================
             3. DEAN / HOD DASHBOARD VIEW
             ================================================================== -->
        <?php elseif ($role === 'dean'): ?>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <span>Department Tasks Assigned (HOD Control)</span>
                        <a href="tasks.php" class="btn btn-sm btn-primary py-1 px-3" style="background-color: var(--primary-color); border-color: var(--primary-color);">Assign Task</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Task Description</th>
                                        <th>Assignee</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $t_stmt = $pdo->prepare("
                                        SELECT t.*, u.name as assignee_name 
                                        FROM tasks t 
                                        JOIN users u ON t.assignee_id = u.id 
                                        WHERE t.assigner_id = ? 
                                        ORDER BY t.due_date ASC LIMIT 5
                                    ");
                                    $t_stmt->execute([$user_id]);
                                    $tasks = $t_stmt->fetchAll();
                                    if (empty($tasks)):
                                    ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No department tasks assigned yet. Click Assign Task to begin.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tasks as $t): ?>
                                            <tr>
                                                <td><strong><?php echo sanitize($t['title']); ?></strong></td>
                                                <td><?php echo sanitize($t['assignee_name']); ?></td>
                                                <td><span class="task-priority-badge <?php echo $t['priority']; ?>"><?php echo $t['priority']; ?></span></td>
                                                <td><span class="badge-status <?php echo $t['status']; ?>"><?php echo str_replace('_', ' ', $t['status']); ?></span></td>
                                                <td><?php echo date('d M Y', strtotime($t['due_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">HOD Quick Tools</div>
                    <div class="card-body">
                        <a href="meetings.php" class="btn btn-primary w-100 mb-3 py-3 font-weight-600" style="background-color: var(--primary-color); border-color: var(--primary-color);"><i data-lucide="video" class="me-2" style="width: 18px;"></i> Schedule Board Meeting</a>
                        <div class="d-grid gap-2">
                            <a href="reports.php" class="btn btn-outline-secondary text-start p-2 font-size-13"><i data-lucide="bar-chart-2" class="me-2" style="width: 16px;"></i> Performance Reports</a>
                            <a href="documents.php" class="btn btn-outline-secondary text-start p-2 font-size-13"><i data-lucide="file-up" class="me-2" style="width: 16px;"></i> Document Repository</a>
                        </div>
                    </div>
                </div>
            </div>

        <!-- ==================================================================
             4. FACULTY / STAFF DASHBOARD VIEW
             ================================================================== -->
        <?php elseif ($role === 'faculty'): ?>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <span>My Assigned Tasks</span>
                        <a href="tasks.php" class="btn btn-sm btn-primary py-1 px-3" style="background-color: var(--primary-color); border-color: var(--primary-color);">Update Tasks</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $my_t_stmt = $pdo->prepare("SELECT * FROM tasks WHERE assignee_id = ? ORDER BY due_date ASC LIMIT 5");
                                    $my_t_stmt->execute([$user_id]);
                                    $my_tasks = $my_t_stmt->fetchAll();
                                    if (empty($my_tasks)):
                                    ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">You have no pending tasks.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($my_tasks as $mt): ?>
                                            <tr>
                                                <td><strong><?php echo sanitize($mt['title']); ?></strong></td>
                                                <td><span class="task-priority-badge <?php echo $mt['priority']; ?>"><?php echo $mt['priority']; ?></span></td>
                                                <td><span class="badge-status <?php echo $mt['status']; ?>"><?php echo str_replace('_', ' ', $mt['status']); ?></span></td>
                                                <td><?php echo date('d M Y', strtotime($mt['due_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Attend Meetings</div>
                    <div class="card-body">
                        <?php
                        $m_stmt = $pdo->prepare("
                            SELECT m.*, u.name as organizer 
                            FROM meetings m 
                            JOIN meeting_participants mp ON m.id = mp.meeting_id 
                            JOIN users u ON m.creator_id = u.id
                            WHERE mp.user_id = ? AND m.date_time >= NOW()
                            ORDER BY m.date_time ASC LIMIT 2
                        ");
                        $m_stmt->execute([$user_id]);
                        $my_meetings = $m_stmt->fetchAll();
                        if (empty($my_meetings)):
                        ?>
                            <p class="text-muted text-center py-4">No meetings scheduled for you today.</p>
                        <?php else: ?>
                            <?php foreach ($my_meetings as $mm): ?>
                                <div class="p-3 border rounded mb-3 bg-light" style="font-size: 13px;">
                                    <h6 class="font-weight-600 mb-1 text-primary"><?php echo sanitize($mm['title']); ?></h6>
                                    <p class="mb-1 text-muted"><i data-lucide="clock" class="me-1" style="width: 14px; vertical-align: middle;"></i> <?php echo date('h:i A, M d', strtotime($mm['date_time'])); ?></p>
                                    <p class="mb-2 text-muted"><i data-lucide="user" class="me-1" style="width: 14px; vertical-align: middle;"></i> Organiser: <?php echo sanitize($mm['organizer']); ?></p>
                                    <?php if (!empty($mm['room_link'])): ?>
                                        <a href="<?php echo $mm['room_link']; ?>" target="_blank" class="btn btn-sm btn-success w-100 py-2"><i data-lucide="video" class="me-1" style="width: 14px; vertical-align: middle;"></i> Join Google Meet</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <!-- ==================================================================
             5. COORDINATOR DASHBOARD VIEW
             ================================================================== -->
        <?php elseif ($role === 'coordinator'): ?>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <span>Committee Meetings & MOM Log</span>
                        <a href="meetings.php" class="btn btn-sm btn-primary py-1 px-3" style="background-color: var(--primary-color); border-color: var(--primary-color);">Draft MOM</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Meeting Title</th>
                                        <th>Scheduled Date</th>
                                        <th>MOM Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $c_meet_stmt = $pdo->prepare("
                                        SELECT m.*, c.name as committee_name 
                                        FROM meetings m 
                                        JOIN committees c ON m.committee_id = c.id 
                                        WHERE c.coordinator_id = ? 
                                        ORDER BY m.date_time DESC LIMIT 5
                                    ");
                                    $c_meet_stmt->execute([$user_id]);
                                    $c_meetings = $c_meet_stmt->fetchAll();
                                    if (empty($c_meetings)):
                                    ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No committee meetings logged. Go to Meetings to schedule.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($c_meetings as $cm): ?>
                                            <tr>
                                                <td><strong><?php echo sanitize($cm['title']); ?></strong><br><small class="text-muted"><?php echo sanitize($cm['committee_name']); ?></small></td>
                                                <td><?php echo date('d M Y, h:i A', strtotime($cm['date_time'])); ?></td>
                                                <td>
                                                    <?php if ($cm['mom_published']): ?>
                                                        <span class="badge bg-success">Published</span>
                                                    <?php elseif (!empty($cm['mom'])): ?>
                                                        <span class="badge bg-warning text-dark">Draft Saved</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Pending MOM</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Coordinator Shortcuts</div>
                    <div class="card-body">
                        <a href="meetings.php" class="btn btn-primary w-100 mb-3 py-3 font-weight-600" style="background-color: var(--primary-color); border-color: var(--primary-color);"><i data-lucide="calendar-plus" class="me-2" style="width: 18px;"></i> Schedule Committee Meeting</a>
                        <div class="d-grid gap-2">
                            <a href="departments.php" class="btn btn-outline-secondary text-start p-2 font-size-13"><i data-lucide="users" class="me-2" style="width: 16px;"></i> My Committee Members</a>
                            <a href="documents.php" class="btn btn-outline-secondary text-start p-2 font-size-13"><i data-lucide="file-text" class="me-2" style="width: 16px;"></i> Publish Committee Files</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
