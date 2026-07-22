<?php
// tasks.php - Task Management Module
require_once 'config.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$dept_id = $_SESSION['department_id'];

$search = $_GET['search'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Handle Task status update (Faculty action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['status'] ?? '';
    
    if ($task_id && in_array($new_status, ['todo', 'in_progress', 'review', 'completed'])) {
        // Verify assignee or permission
        $check_stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $check_stmt->execute([$task_id]);
        $task = $check_stmt->fetch();
        
        if ($task && ($task['assignee_id'] == $user_id || $role === 'dean' || $role === 'admin')) {
            $update_stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $task_id]);
            
            // Notify assigner
            $msg = $_SESSION['user_name'] . " updated task status to " . str_replace('_', ' ', $new_status) . ": " . $task['title'];
            add_notification($pdo, $task['assigner_id'], $msg);
            
            $success_msg = "Task status updated successfully!";
        }
    }
}

// Fetch faculties for task assignment dropdown
$faculty_stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'faculty' AND department_id = ?");
$faculty_stmt->execute([$dept_id]);
$faculties = $faculty_stmt->fetchAll();

// Build Query based on search and filters
$query = "SELECT t.*, a.name as assigner_name, b.name as assignee_name 
          FROM tasks t 
          JOIN users a ON t.assigner_id = a.id 
          JOIN users b ON t.assignee_id = b.id WHERE 1=1";
$params = [];

if ($role === 'faculty') {
    $query .= " AND t.assignee_id = ?";
    $params[] = $user_id;
} elseif ($role === 'dean') {
    $query .= " AND (t.assigner_id = ? OR t.assignee_id = ?)";
    $params[] = $user_id;
    $params[] = $user_id;
}

if (!empty($search)) {
    $query .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_priority)) {
    $query .= " AND t.priority = ?";
    $params[] = $filter_priority;
}

if (!empty($filter_status)) {
    $query .= " AND t.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY t.due_date ASC";
$tasks_stmt = $pdo->prepare($query);
$tasks_stmt->execute($params);
$tasks = $tasks_stmt->fetchAll();

// Group tasks by status for Kanban Board
$todo_tasks = [];
$progress_tasks = [];
$review_tasks = [];
$completed_tasks = [];

foreach ($tasks as $t) {
    if ($t['status'] === 'todo') $todo_tasks[] = $t;
    elseif ($t['status'] === 'in_progress') $progress_tasks[] = $t;
    elseif ($t['status'] === 'review') $review_tasks[] = $t;
    elseif ($t['status'] === 'completed') $completed_tasks[] = $t;
}
?>

<div class="container-fluid p-0">
    <div class="page-title">
        <div>
            <h1 class="m-0 font-weight-800" style="font-size: 28px;">Task Management</h1>
            <p class="text-muted font-size-14 mb-0" style="font-size: 14px;">Assign, plan and track tasks across your department.</p>
        </div>
        <div>
            <?php if ($role === 'dean' || $role === 'admin'): ?>
                <button class="btn btn-primary py-2 px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#createTaskModal" style="background-color: var(--primary-color); border-color: var(--primary-color);">
                    <i data-lucide="plus-circle" class="me-2" style="width: 16px; height: 16px; vertical-align: middle;"></i>Assign New Task
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success py-2 font-size-13">
            <i data-lucide="check-circle" class="me-1" style="width: 16px; vertical-align: middle;"></i>
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form action="tasks.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i data-lucide="search" style="width: 14px; color: var(--text-muted);"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search tasks..." value="<?php echo sanitize($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Priorities --</option>
                        <option value="high" <?php echo $filter_priority === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $filter_priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $filter_priority === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Statuses --</option>
                        <option value="todo" <?php echo $filter_status === 'todo' ? 'selected' : ''; ?>>To Do</option>
                        <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="review" <?php echo $filter_status === 'review' ? 'selected' : ''; ?>>In Review</option>
                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="tasks.php" class="btn btn-sm btn-outline-secondary w-100 font-weight-600">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tab selection -->
    <ul class="nav nav-tabs mb-4" id="taskTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#listView" type="button" role="tab"><i data-lucide="list" class="me-1" style="width: 16px;"></i>List View</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="board-tab" data-bs-toggle="tab" data-bs-target="#boardView" type="button" role="tab"><i data-lucide="kanban" class="me-1" style="width: 16px;"></i>Kanban Board</button>
        </li>
    </ul>

    <div class="tab-content" id="taskTabContent">
        <!-- ==================================================================
             TAB 1: LIST VIEW (TABLE)
             ================================================================== -->
        <div class="tab-pane fade show active" id="listView" role="tabpanel">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>Task Title</th>
                                    <th>Assigned To</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tasks)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No tasks match your filters.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tasks as $t): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-dark"><?php echo sanitize($t['title']); ?></strong>
                                                <p class="mb-0 text-muted font-size-12" style="font-size: 12px;"><?php echo sanitize($t['description']); ?></p>
                                            </td>
                                            <td><i data-lucide="user" style="width: 14px; vertical-align: middle; color:var(--text-muted);"></i> <?php echo sanitize($t['assignee_name']); ?></td>
                                            <td><span class="task-priority-badge <?php echo $t['priority']; ?>"><?php echo $t['priority']; ?></span></td>
                                            <td><span class="badge-status <?php echo $t['status']; ?>"><?php echo str_replace('_', ' ', $t['status']); ?></span></td>
                                            <td><i data-lucide="calendar" style="width: 14px; vertical-align: middle; color:var(--text-muted);"></i> <?php echo date('M d, Y', strtotime($t['due_date'])); ?></td>
                                            <td>
                                                <?php if ($t['assignee_id'] == $user_id): ?>
                                                    <!-- Quick status update action -->
                                                    <form action="tasks.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                                                        <select name="status" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block w-auto">
                                                            <option value="todo" <?php echo $t['status'] === 'todo' ? 'selected' : ''; ?>>To Do</option>
                                                            <option value="in_progress" <?php echo $t['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                            <option value="review" <?php echo $t['status'] === 'review' ? 'selected' : ''; ?>>In Review</option>
                                                            <option value="completed" <?php echo $t['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                        </select>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted font-size-12" style="font-size: 12px;">Assigned by <?php echo sanitize($t['assigner_name']); ?></span>
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

        <!-- ==================================================================
             TAB 2: KANBAN BOARD VIEW
             ================================================================== -->
        <div class="tab-pane fade" id="boardView" role="tabpanel">
            <div class="task-board">
                <!-- Column 1: Todo -->
                <div class="kanban-col">
                    <div class="kanban-col-header">
                        <span>To Do</span>
                        <span class="task-count"><?php echo count($todo_tasks); ?></span>
                    </div>
                    <?php foreach ($todo_tasks as $t): ?>
                        <div class="task-card">
                            <span class="task-priority-badge <?php echo $t['priority']; ?>"><?php echo $t['priority']; ?></span>
                            <div class="task-title"><?php echo sanitize($t['title']); ?></div>
                            <div class="task-desc"><?php echo sanitize($t['description']); ?></div>
                            <div class="task-meta">
                                <span class="task-user"><i data-lucide="user" style="width:12px;"></i> <?php echo sanitize($t['assignee_name']); ?></span>
                                <span><?php echo date('d M', strtotime($t['due_date'])); ?></span>
                            </div>
                            <?php if ($t['assignee_id'] == $user_id || $role === 'dean'): ?>
                                <div class="mt-2 pt-2 border-top d-flex gap-1 justify-content-end">
                                    <form action="tasks.php" method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                                        <input type="hidden" name="status" value="in_progress">
                                        <button type="submit" class="btn btn-sm btn-outline-info py-1 px-2 font-size-10" style="font-size: 10px;">Start Work</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Column 2: In Progress -->
                <div class="kanban-col">
                    <div class="kanban-col-header">
                        <span>In Progress</span>
                        <span class="task-count"><?php echo count($progress_tasks); ?></span>
                    </div>
                    <?php foreach ($progress_tasks as $t): ?>
                        <div class="task-card">
                            <span class="task-priority-badge <?php echo $t['priority']; ?>"><?php echo $t['priority']; ?></span>
                            <div class="task-title"><?php echo sanitize($t['title']); ?></div>
                            <div class="task-desc"><?php echo sanitize($t['description']); ?></div>
                            <div class="task-meta">
                                <span class="task-user"><i data-lucide="user" style="width:12px;"></i> <?php echo sanitize($t['assignee_name']); ?></span>
                                <span><?php echo date('d M', strtotime($t['due_date'])); ?></span>
                            </div>
                            <?php if ($t['assignee_id'] == $user_id || $role === 'dean'): ?>
                                <div class="mt-2 pt-2 border-top d-flex gap-1 justify-content-end">
                                    <form action="tasks.php" method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                                        <input type="hidden" name="status" value="review">
                                        <button type="submit" class="btn btn-sm btn-outline-warning py-1 px-2 font-size-10" style="font-size: 10px;">Submit for Review</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Column 3: Review -->
                <div class="kanban-col">
                    <div class="kanban-col-header">
                        <span>Under Review</span>
                        <span class="task-count"><?php echo count($review_tasks); ?></span>
                    </div>
                    <?php foreach ($review_tasks as $t): ?>
                        <div class="task-card">
                            <span class="task-priority-badge <?php echo $t['priority']; ?>"><?php echo $t['priority']; ?></span>
                            <div class="task-title"><?php echo sanitize($t['title']); ?></div>
                            <div class="task-desc"><?php echo sanitize($t['description']); ?></div>
                            <div class="task-meta">
                                <span class="task-user"><i data-lucide="user" style="width:12px;"></i> <?php echo sanitize($t['assignee_name']); ?></span>
                                <span><?php echo date('d M', strtotime($t['due_date'])); ?></span>
                            </div>
                            <?php if ($role === 'dean' || $role === 'admin'): ?>
                                <div class="mt-2 pt-2 border-top d-flex gap-1 justify-content-end">
                                    <form action="tasks.php" method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2 font-size-10" style="font-size: 10px;">Approve & Complete</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Column 4: Completed -->
                <div class="kanban-col">
                    <div class="kanban-col-header">
                        <span>Completed</span>
                        <span class="task-count"><?php echo count($completed_tasks); ?></span>
                    </div>
                    <?php foreach ($completed_tasks as $t): ?>
                        <div class="task-card">
                            <span class="task-priority-badge <?php echo $t['priority']; ?>"><?php echo $t['priority']; ?></span>
                            <div class="task-title"><?php echo sanitize($t['title']); ?></div>
                            <div class="task-desc"><?php echo sanitize($t['description']); ?></div>
                            <div class="task-meta">
                                <span class="task-user"><i data-lucide="user" style="width:12px;"></i> <?php echo sanitize($t['assignee_name']); ?></span>
                                <span><?php echo date('d M', strtotime($t['due_date'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================================================================
     TASK CREATION MODAL (DEAN/ADMIN ONLY)
     ================================================================== -->
<?php if ($role === 'dean' || $role === 'admin'): ?>
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: var(--border-radius); border: 1px solid var(--border-color); color:var(--text-dark); background-color: var(--card-light);">
            <form action="create_task.php" method="POST">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title font-weight-700" id="createTaskModalLabel">Assign New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Task Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Syllabus Review CSE 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Provide detailed instructions for the task..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-size-13 font-weight-600">Priority</label>
                            <select name="priority" class="form-select" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-size-13 font-weight-600">Due Date</label>
                            <input type="date" name="due_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Assign To (Faculty)</label>
                        <select name="assignee_id" class="form-select" required>
                            <option value="">-- Choose Faculty Member --</option>
                            <?php foreach ($faculties as $fac): ?>
                                <option value="<?php echo $fac['id']; ?>"><?php echo sanitize($fac['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary py-2 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary py-2 px-4" style="background-color: var(--primary-color); border-color: var(--primary-color);">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>
