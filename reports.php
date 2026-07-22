<?php
// reports.php - Analytics & Performance Overview Reports
require_once 'config.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$dept_id = $_SESSION['department_id'];

// 1. Task breakdown queries
try {
    $todo_c = $inprog_c = $review_c = $completed_c = 0;
    
    if ($role === 'registrar' || $role === 'admin') {
        $status_stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
    } elseif ($role === 'dean') {
        $status_stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE assigner_id = ? OR assignee_id = ? GROUP BY status");
        $status_stmt->execute([$user_id, $user_id]);
    } else {
        $status_stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE assignee_id = ? GROUP BY status");
        $status_stmt->execute([$user_id]);
    }
    
    while ($row = $status_stmt->fetch()) {
        if ($row['status'] === 'todo') $todo_c = (int)$row['count'];
        elseif ($row['status'] === 'in_progress') $inprog_c = (int)$row['count'];
        elseif ($row['status'] === 'review') $review_c = (int)$row['count'];
        elseif ($row['status'] === 'completed') $completed_c = (int)$row['count'];
    }
    
    $total_tasks = $todo_c + $inprog_c + $review_c + $completed_c;
    $completion_rate = $total_tasks > 0 ? round(($completed_c / $total_tasks) * 100) : 0;

} catch (PDOException $e) {
    $total_tasks = $completion_rate = 0;
}

// 2. Department-wise Performance comparison (Registrar and Admin only)
$dept_labels = [];
$dept_totals = [];
$dept_completes = [];
if ($role === 'registrar' || $role === 'admin') {
    try {
        $dept_stmt = $pdo->query("
            SELECT d.name, COUNT(t.id) as total, SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed 
            FROM departments d 
            LEFT JOIN users u ON d.id = u.department_id 
            LEFT JOIN tasks t ON u.id = t.assignee_id 
            GROUP BY d.id
        ");
        while ($row = $dept_stmt->fetch()) {
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
            $dept_labels[] = $name;
            $dept_totals[] = (int)$row['total'];
            $dept_completes[] = (int)$row['completed'];
        }
    } catch (PDOException $e) {
        // Handle gracefully
    }
}

// 3. Help Desk active tickets summary (Admin only)
$tickets = [];
if ($role === 'admin') {
    try {
        $ticket_stmt = $pdo->query("SELECT h.*, u.name as user_name FROM help_tickets h JOIN users u ON h.user_id = u.id ORDER BY h.created_at DESC LIMIT 5");
        $tickets = $ticket_stmt->fetchAll();
    } catch (PDOException $e) {
        // Handle gracefully
    }
}
?>

<!-- Print-only CSS style overrides -->
<style>
    @media print {
        body { background-color: white; color: black; }
        .sidebar, .top-header, .btn, .nav, .dev-panel { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; padding-top: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .print-title { display: block !important; margin-bottom: 30px; text-align: center; }
        .print-title h2 { font-weight: 800; color: #0f2b5c; }
    }
    .print-title { display: none; }
</style>

<div class="container-fluid p-0">
    
    <!-- Printable Header Banner -->
    <div class="print-title">
        <h2>SANDIP UNIVERSITY</h2>
        <h5>Internal Planning & Meeting Scheduler Performance Report</h5>
        <hr>
    </div>

    <div class="page-title">
        <div>
            <h1 class="m-0 font-weight-800" style="font-size: 28px;">Performance & Analytics Reports</h1>
            <p class="text-muted font-size-14 mb-0" style="font-size: 14px;">Review academic progress charts, meeting metrics, and department workloads.</p>
        </div>
        <div>
            <button class="btn btn-outline-primary py-2 px-3 font-weight-600" onclick="window.print()">
                <i data-lucide="printer" class="me-1" style="width: 16px; vertical-align: middle;"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Completion Rate</h3>
                    <p><?php echo $completion_rate; ?>%</p>
                </div>
                <div class="stat-icon success"><i data-lucide="percent"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Assigned</h3>
                    <p><?php echo $total_tasks; ?></p>
                </div>
                <div class="stat-icon primary"><i data-lucide="list-todo"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Tasks Completed</h3>
                    <p><?php echo $completed_c; ?></p>
                </div>
                <div class="stat-icon success"><i data-lucide="check-circle-2"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Pending Review</h3>
                    <p><?php echo $review_c; ?></p>
                </div>
                <div class="stat-icon warning"><i data-lucide="clock"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Task Status Breakdown Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">Current Task Status Distribution</div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div style="width: 320px; height: 320px;">
                        <canvas id="reportStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department performance chart or Faculty workload summary -->
        <div class="col-lg-6 mb-4">
            <?php if ($role === 'registrar' || $role === 'admin'): ?>
                <div class="card h-100">
                    <div class="card-header">Department Performance Index</div>
                    <div class="card-body">
                        <div style="position: relative; height: 320px; width: 100%;">
                            <canvas id="reportDeptPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Details panel for Faculty / Dean showing list of metrics -->
                <div class="card h-100">
                    <div class="card-header">My Planning Metrics & Ratios</div>
                    <div class="card-body">
                        <h6 class="font-weight-600 mb-2">Metrics Checklist</h6>
                        <ul class="list-group list-group-flush mb-4" style="font-size: 13px;">
                            <li class="list-group-item d-flex justify-content-between p-3 bg-light border rounded mb-2">
                                <span>Completed Tasks Ratio:</span>
                                <strong><?php echo $completed_c; ?> / <?php echo $total_tasks; ?> Tasks</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between p-3 bg-light border rounded mb-2">
                                <span>Action Items Pending Review:</span>
                                <strong class="text-warning"><?php echo $review_c; ?> items</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between p-3 bg-light border rounded mb-2">
                                <span>In Progress Checklist:</span>
                                <strong class="text-info"><?php echo $inprog_c; ?> items</strong>
                            </li>
                        </ul>
                        <div class="p-3 border rounded border-warning" style="font-size: 12px; background-color: rgba(255, 183, 3, 0.05);">
                            <p class="mb-0"><i data-lucide="info" class="me-1 text-warning" style="width: 14px;"></i> Faculty and Dean progress indicators are synced in real-time. Any status shifts trigger database logging and Dean notification alerts.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Help Desk active issues dashboard (Admin only) -->
    <?php if ($role === 'admin' && !empty($tickets)): ?>
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">Recent IT Help Desk Support Tickets</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Ticket ID</th>
                                        <th>User Name</th>
                                        <th>Subject</th>
                                        <th>Details</th>
                                        <th>Status</th>
                                        <th>Date Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td>#0<?php echo $ticket['id']; ?></td>
                                            <td><?php echo sanitize($ticket['user_name']); ?></td>
                                            <td><strong><?php echo sanitize($ticket['subject']); ?></strong></td>
                                            <td><?php echo sanitize($ticket['message']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $ticket['status'] === 'open' ? 'bg-danger' : 'bg-success'; ?>">
                                                    <?php echo strtoupper($ticket['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($ticket['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Status Chart
        const ctxReportStatus = document.getElementById('reportStatusChart').getContext('2d');
        new Chart(ctxReportStatus, {
            type: 'pie',
            data: {
                labels: ['To Do', 'In Progress', 'In Review', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $todo_c; ?>,
                        <?php echo $inprog_c; ?>,
                        <?php echo $review_c; ?>,
                        <?php echo $completed_c; ?>
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

        // Department performance index (Admin / Registrar only)
        <?php if ($role === 'registrar' || $role === 'admin'): ?>
            const ctxReportDeptPerf = document.getElementById('reportDeptPerformanceChart').getContext('2d');
            new Chart(ctxReportDeptPerf, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($dept_labels); ?>,
                    datasets: [
                        {
                            label: 'Total Tasks Assigned',
                            data: <?php echo json_encode($dept_totals); ?>,
                            backgroundColor: 'rgba(15, 43, 92, 0.4)',
                            borderColor: 'var(--primary-color)',
                            borderWidth: 1
                        },
                        {
                            label: 'Tasks Completed',
                            data: <?php echo json_encode($dept_completes); ?>,
                            backgroundColor: '#2ec4b6',
                            borderColor: '#2ec4b6',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'var(--border-color)' }, ticks: { color: 'var(--text-dark)' } },
                        x: { grid: { display: false }, ticks: { color: 'var(--text-dark)' } }
                    },
                    plugins: {
                        legend: { position: 'top', labels: { color: 'var(--text-dark)' } }
                    }
                }
            });
        <?php endif; ?>
    });
</script>

<?php include 'footer.php'; ?>
