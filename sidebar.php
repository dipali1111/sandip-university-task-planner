<?php
// sidebar.php - Sandip University Planner Sidebar Navigation
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'faculty';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i data-lucide="graduation-cap"></i>
        <div class="d-flex flex-column">
            <span>SANDIP</span>
            <small style="font-size: 9px; color: var(--accent-color); font-weight: 700; letter-spacing: 0.8px;">UNIVERSITY</small>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i data-lucide="layout-dashboard"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="<?php echo $current_page == 'tasks.php' ? 'active' : ''; ?>">
            <a href="tasks.php">
                <i data-lucide="check-square"></i>
                <span>Tasks</span>
            </a>
        </li>
        <li class="<?php echo $current_page == 'meetings.php' ? 'active' : ''; ?>">
            <a href="meetings.php">
                <i data-lucide="users"></i>
                <span>Meetings</span>
            </a>
        </li>
        <li class="<?php echo $current_page == 'calendar.php' ? 'active' : ''; ?>">
            <a href="calendar.php">
                <i data-lucide="calendar"></i>
                <span>Calendar</span>
            </a>
        </li>
        <li class="<?php echo $current_page == 'alerts.php' ? 'active' : ''; ?>">
            <a href="alerts.php">
                <i data-lucide="bell"></i>
                <span>Alerts</span>
            </a>
        </li>
        <li class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <a href="reports.php">
                <i data-lucide="bar-chart-3"></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="<?php echo $current_page == 'documents.php' ? 'active' : ''; ?>">
            <a href="documents.php">
                <i data-lucide="folder-closed"></i>
                <span>Documents</span>
            </a>
        </li>
        <li class="<?php echo $current_page == 'departments.php' ? 'active' : ''; ?>">
            <a href="departments.php">
                <i data-lucide="building"></i>
                <span>Departments</span>
            </a>
        </li>
        <li class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <a href="settings.php">
                <i data-lucide="settings"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <p class="mb-0">Version 2.0</p>
        <span style="font-size: 8px; opacity: 0.5;">Sandip Planner &copy; 2026</span>
    </div>
</aside>
