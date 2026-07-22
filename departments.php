<?php
// departments.php - Department & Committee Roster Manager
require_once 'config.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success_msg = '';
$error_msg = '';

// Handle creating a department (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_department') {
    if ($role !== 'admin') {
        $error_msg = "Only administrators can create departments.";
    } else {
        $dept_name = sanitize($_POST['dept_name'] ?? '');
        if (!empty($dept_name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
                $stmt->execute([$dept_name]);
                $success_msg = "Department '$dept_name' created successfully!";
            } catch (PDOException $e) {
                $error_msg = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all departments
try {
    $depts_stmt = $pdo->query("SELECT d.*, COUNT(u.id) as staff_count FROM departments d LEFT JOIN users u ON d.id = u.department_id GROUP BY d.id");
    $departments = $depts_stmt->fetchAll();
} catch (PDOException $e) {
    $departments = [];
}

// Fetch all committees
try {
    $comm_stmt = $pdo->query("SELECT c.*, u.name as coordinator_name FROM committees c LEFT JOIN users u ON c.coordinator_id = u.id");
    $committees = $comm_stmt->fetchAll();
} catch (PDOException $e) {
    $committees = [];
}

// Fetch all users for membership reference lists
try {
    $users_stmt = $pdo->query("SELECT u.id, u.name, u.role, d.name as dept_name FROM users u LEFT JOIN departments d ON u.department_id = d.id ORDER BY u.name ASC");
    $all_users = $users_stmt->fetchAll();
} catch (PDOException $e) {
    $all_users = [];
}
?>

<div class="container-fluid p-0">
    <div class="page-title">
        <div>
            <h1 class="m-0 font-weight-800" style="font-size: 28px;">Departments & Committees</h1>
            <p class="text-muted font-size-14 mb-0" style="font-size: 14px;">Audit Sandip University school structures, coordinator assignments, and boards.</p>
        </div>
        <div>
            <?php if ($role === 'admin'): ?>
                <button class="btn btn-primary py-2 px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#addDeptModal" style="background-color: var(--primary-color); border-color: var(--primary-color);">
                    <i data-lucide="plus-circle" class="me-2" style="width: 16px; height: 16px; vertical-align: middle;"></i>Add Department
                </button>
            <?php endif; ?>
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
        <!-- Departments Column -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><span class="d-flex align-items-center gap-2"><i data-lucide="building" class="text-primary"></i> Academic Departments</span></div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($departments as $d): ?>
                            <div class="list-group-item p-3 d-flex justify-content-between align-items-center bg-light mb-2 border rounded">
                                <div>
                                    <h6 class="font-weight-700 text-dark mb-1"><?php echo sanitize($d['name']); ?></h6>
                                    <span class="text-muted font-size-12" style="font-size: 12px;">Active Academic Members: <strong><?php echo $d['staff_count']; ?></strong></span>
                                </div>
                                <span class="badge bg-secondary rounded-pill py-2 px-3" style="font-size: 11px;">Active</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Committees Column -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><span class="d-flex align-items-center gap-2"><i data-lucide="users-2" class="text-warning"></i> Active Committees</span></div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($committees as $c): ?>
                            <div class="list-group-item p-3 bg-light mb-2 border rounded">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="font-weight-700 text-dark mb-0"><?php echo sanitize($c['name']); ?></h6>
                                    <span class="badge bg-info text-dark font-size-10" style="font-size: 10px;">Board Panel</span>
                                </div>
                                <p class="text-muted font-size-12 mb-2" style="font-size: 12px;"><i data-lucide="award" style="width: 14px; vertical-align: middle;"></i> Head Coordinator: <strong><?php echo sanitize($c['coordinator_name'] ?? 'Not Assigned'); ?></strong></p>
                                
                                <div class="mt-2 border-top pt-2">
                                    <strong class="font-size-11 text-muted" style="font-size: 11px;">Committee Members:</strong>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <?php
                                        // Fetch members for this specific committee
                                        $mem_stmt = $pdo->prepare("SELECT u.name FROM committee_members cm JOIN users u ON cm.user_id = u.id WHERE cm.committee_id = ?");
                                        $mem_stmt->execute([$c['id']]);
                                        $members = $mem_stmt->fetchAll();
                                        
                                        if (empty($members)):
                                            echo "<span class='text-muted font-size-11'>No members added yet.</span>";
                                        else:
                                            foreach ($members as $m):
                                        ?>
                                                <span class="badge bg-white text-dark border py-1 px-2" style="font-size: 11px;"><?php echo sanitize($m['name']); ?></span>
                                        <?php 
                                            endforeach;
                                        endif; 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active University Roster -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">Academic Member Roster</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Designation / Role</th>
                                    <th>School / Department</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_users as $au): ?>
                                    <tr>
                                        <td><strong><?php echo sanitize($au['name']); ?></strong></td>
                                        <td><?php echo sanitize($au['email']); ?></td>
                                        <td><span class="badge bg-secondary text-capitalize text-dark"><?php echo sanitize($au['role'] === 'registrar' ? 'Registrar/VC' : ($au['role'] === 'dean' ? 'Dean/HOD' : $au['role'])); ?></span></td>
                                        <td><?php echo sanitize($au['dept_name'] ?? 'Not Assigned'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================================================================
     ADD DEPARTMENT MODAL (ADMIN ONLY)
     ================================================================== -->
<?php if ($role === 'admin'): ?>
<div class="modal fade" id="addDeptModal" tabindex="-1" aria-labelledby="addDeptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: var(--border-radius); border: 1px solid var(--border-color); color:var(--text-dark); background-color: var(--card-light);">
            <form action="departments.php" method="POST">
                <input type="hidden" name="action" value="create_department">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title font-weight-700" id="addDeptModalLabel">Add Academic Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Department Name</label>
                        <input type="text" name="dept_name" class="form-control" placeholder="e.g. School of Civil Engineering" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary py-2 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary py-2 px-4" style="background-color: var(--primary-color); border-color: var(--primary-color);">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>
