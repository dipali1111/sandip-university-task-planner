<?php
// documents.php - Document Vault and Version Control
require_once 'config.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$dept_id = $_SESSION['department_id'];

$success_msg = $_GET['msg'] ?? '';
$error_msg = $_GET['error'] ?? '';

// Fetch tasks for dropdown
$tasks_stmt = $pdo->prepare("SELECT id, title FROM tasks WHERE assignee_id = ? OR assigner_id = ? OR ? = 'registrar' OR ? = 'admin'");
$tasks_stmt->execute([$user_id, $user_id, $role, $role]);
$tasks = $tasks_stmt->fetchAll();

// Fetch meetings for dropdown
$meetings_stmt = $pdo->prepare("
    SELECT DISTINCT m.id, m.title 
    FROM meetings m
    LEFT JOIN meeting_participants mp ON m.id = mp.meeting_id
    WHERE m.creator_id = ? OR mp.user_id = ? OR ? = 'registrar' OR ? = 'admin'
");
$meetings_stmt->execute([$user_id, $user_id, $role, $role]);
$meetings = $meetings_stmt->fetchAll();

// Fetch all documents
try {
    $stmt = $pdo->query("
        SELECT d.*, u.name as author, t.title as task_title, m.title as meeting_title 
        FROM documents d 
        JOIN users u ON d.uploaded_by = u.id
        LEFT JOIN tasks t ON d.task_id = t.id
        LEFT JOIN meetings m ON d.meeting_id = m.id
        ORDER BY d.upload_date DESC
    ");
    $docs = $stmt->fetchAll();
} catch (PDOException $e) {
    $docs = [];
}
?>

<div class="container-fluid p-0">
    <div class="page-title">
        <div>
            <h1 class="m-0 font-weight-800" style="font-size: 28px;">Document Vault</h1>
            <p class="text-muted font-size-14 mb-0" style="font-size: 14px;">Store files, publish agendas, and audit task reports with automatic version tracking.</p>
        </div>
        <div>
            <button class="btn btn-primary py-2 px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#uploadDocModal" style="background-color: var(--primary-color); border-color: var(--primary-color);">
                <i data-lucide="file-up" class="me-2" style="width: 16px; height: 16px; vertical-align: middle;"></i>Upload Document
            </button>
        </div>
    </div>

    <?php if ($success_msg === 'uploaded'): ?>
        <div class="alert alert-success py-2 font-size-13">
            <i data-lucide="check-circle" class="me-1" style="width: 16px; vertical-align: middle;"></i>
            Document uploaded and catalogued successfully!
        </div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger py-2 font-size-13">
            <i data-lucide="alert-circle" class="me-1" style="width: 16px; vertical-align: middle;"></i>
            <?php echo sanitize($error_msg); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">Document Registry & History</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Uploaded By</th>
                                    <th>Link Reference</th>
                                    <th>Version</th>
                                    <th>Upload Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($docs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i data-lucide="folder-open" class="mb-2" style="width: 48px; height: 48px; stroke-width: 1;"></i>
                                            <p class="mb-0">No documents catalogued yet.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($docs as $d): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i data-lucide="file-text" class="text-primary"></i>
                                                    <strong class="text-dark"><?php echo sanitize($d['filename']); ?></strong>
                                                </div>
                                            </td>
                                            <td><?php echo sanitize($d['author']); ?></td>
                                            <td>
                                                <?php if ($d['task_id']): ?>
                                                    <span class="badge bg-secondary font-size-10 text-capitalize" style="font-size: 10px;">Task: <?php echo sanitize($d['task_title']); ?></span>
                                                <?php elseif ($d['meeting_id']): ?>
                                                    <span class="badge bg-secondary font-size-10 text-capitalize" style="font-size: 10px;">Meeting: <?php echo sanitize($d['meeting_title']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted font-size-11" style="font-size: 11px;">General File</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-info text-dark">v<?php echo $d['version']; ?></span></td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($d['upload_date'])); ?></td>
                                            <td>
                                                <a href="<?php echo sanitize($d['filepath']); ?>" class="btn btn-sm btn-outline-primary py-1 px-3 font-size-12" style="font-size: 12px;" download>
                                                    <i data-lucide="download" style="width: 14px; vertical-align: middle;"></i> Download
                                                </a>
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
    </div>
</div>

<!-- ==================================================================
     DOCUMENT UPLOAD MODAL
     ================================================================== -->
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: var(--border-radius); border: 1px solid var(--border-color); color:var(--text-dark); background-color: var(--card-light);">
            <form action="upload_document.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title font-weight-700" id="uploadDocModalLabel">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Select File</label>
                        <input type="file" name="doc_file" class="form-control" required>
                        <small class="text-muted">Max file size: 5MB. Formats: PDF, DOCX, XLSX, PNG, JPG.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Link to Task (Optional)</label>
                        <select name="task_id" class="form-select">
                            <option value="">-- No Linked Task --</option>
                            <?php foreach ($tasks as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo sanitize($t['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Link to Meeting (Optional)</label>
                        <select name="meeting_id" class="form-select">
                            <option value="">-- No Linked Meeting --</option>
                            <?php foreach ($meetings as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo sanitize($m['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check p-0 mt-3">
                        <p class="text-muted font-size-11" style="font-size: 11px;"><i data-lucide="info" style="width:12px;"></i> Note: If you upload a document with the exact same name, the system will update the version number (e.g., v1 &rarr; v2) and preserve file integrity.</p>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary py-2 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary py-2 px-4" style="background-color: var(--primary-color); border-color: var(--primary-color);">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>
