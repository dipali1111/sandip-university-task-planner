<?php
// documents.php - Document Vault and Version Control
require_once 'config.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$dept_id = $_SESSION['department_id'];

$success_msg = $_GET['msg'] ?? '';
$error_msg = $_GET['error'] ?? '';
$search = $_GET['search'] ?? '';
$filter_category = $_GET['category'] ?? '';
$allowed_categories = ['general', 'task', 'meeting', 'report', 'policy', 'training'];
$category_labels = [
    'general' => 'General',
    'task' => 'Task',
    'meeting' => 'Meeting',
    'report' => 'Report',
    'policy' => 'Policy',
    'training' => 'Training'
];

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

    <div class="card mb-4">
        <div class="card-body py-3">
            <form action="documents.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i data-lucide="search" style="width: 14px; color: var(--text-muted);"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by filename, task, meeting or author" value="<?php echo sanitize($search); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Categories --</option>
                        <?php foreach ($allowed_categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $filter_category === $cat ? 'selected' : ''; ?>><?php echo sanitize($category_labels[$cat]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="documents.php" class="btn btn-sm btn-outline-secondary w-100 font-weight-600">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>

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
                                    <th>Category</th>
                                    <th>Link Reference</th>
                                    <th>Version</th>
                                    <th>Upload Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($docs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
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
                                                <span class="badge bg-light text-dark text-capitalize">
                                                    <?php echo sanitize($category_labels[$d['category']] ?? 'General'); ?>
                                                </span>
                                            </td>
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
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <button type="button" class="btn btn-sm btn-outline-info py-1 px-3 font-size-12" style="font-size: 12px;" onclick='openDocumentPreview(<?php echo json_encode(['filename' => $d['filename'], 'filepath' => $d['filepath']]); ?>)'>
                                                        <i data-lucide="eye" style="width: 14px; vertical-align: middle;"></i> Preview
                                                    </button>
                                                    <a href="<?php echo sanitize($d['filepath']); ?>" class="btn btn-sm btn-outline-primary py-1 px-3 font-size-12" style="font-size: 12px;" download>
                                                        <i data-lucide="download" style="width: 14px; vertical-align: middle;"></i> Download
                                                    </a>
                                                    <?php if ($d['uploaded_by'] == $user_id || in_array($role, ['admin', 'registrar', 'dean'], true)): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-warning py-1 px-3 font-size-12" style="font-size: 12px;" onclick='openEditDocumentModal(<?php echo json_encode(['id' => $d['id'], 'filename' => $d['filename'], 'category' => $d['category'] ?? 'general', 'task_id' => $d['task_id'], 'meeting_id' => $d['meeting_id']]); ?>)'>
                                                            <i data-lucide="pencil" style="width: 14px; vertical-align: middle;"></i> Edit
                                                        </button>
                                                        <form action="manage_document.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="delete_document">
                                                            <input type="hidden" name="document_id" value="<?php echo $d['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-3 font-size-12" style="font-size: 12px;" onclick="return confirm('Delete this document record and file?');">
                                                                <i data-lucide="trash-2" style="width: 14px; vertical-align: middle;"></i> Delete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
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
                        <label class="form-label font-size-13 font-weight-600">Document Category</label>
                        <select name="document_category" class="form-select">
                            <option value="general">General</option>
                            <option value="task">Task</option>
                            <option value="meeting">Meeting</option>
                            <option value="report">Report</option>
                            <option value="policy">Policy</option>
                            <option value="training">Training</option>
                        </select>
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

<!-- Preview Modal -->
<div class="modal fade" id="previewDocModal" tabindex="-1" aria-labelledby="previewDocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--border-radius); border: 1px solid var(--border-color); color:var(--text-dark); background-color: var(--card-light);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title font-weight-700" id="previewDocModalLabel">Document Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="previewDocBody">
                <div id="previewImageBlock" class="d-none text-center">
                    <img id="previewImage" src="" alt="Document preview" class="img-fluid rounded" style="max-height: 70vh;">
                </div>
                <iframe id="previewFrame" class="d-none w-100" style="height: 70vh; border: 0; border-radius: 10px;"></iframe>
                <pre id="previewText" class="d-none p-3 bg-light rounded" style="white-space: pre-wrap; max-height: 70vh; overflow:auto;"></pre>
                <div id="previewUnsupported" class="d-none text-center text-muted py-4">
                    <i data-lucide="file-question" class="mb-2" style="width: 40px; height: 40px;"></i>
                    <p class="mb-0">Preview is not available for this file type.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editDocModal" tabindex="-1" aria-labelledby="editDocModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: var(--border-radius); border: 1px solid var(--border-color); color:var(--text-dark); background-color: var(--card-light);">
            <form action="manage_document.php" method="POST">
                <input type="hidden" name="action" value="edit_document">
                <input type="hidden" name="document_id" id="editDocumentId">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title font-weight-700" id="editDocModalLabel">Edit Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Document Name</label>
                        <input type="text" name="document_name" id="editDocumentName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Category</label>
                        <select name="document_category" id="editDocumentCategory" class="form-select">
                            <option value="general">General</option>
                            <option value="task">Task</option>
                            <option value="meeting">Meeting</option>
                            <option value="report">Report</option>
                            <option value="policy">Policy</option>
                            <option value="training">Training</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Link to Task (Optional)</label>
                        <select name="task_id" id="editTaskId" class="form-select">
                            <option value="">-- No Linked Task --</option>
                            <?php foreach ($tasks as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo sanitize($t['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Link to Meeting (Optional)</label>
                        <select name="meeting_id" id="editMeetingId" class="form-select">
                            <option value="">-- No Linked Meeting --</option>
                            <?php foreach ($meetings as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo sanitize($m['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary py-2 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary py-2 px-4" style="background-color: var(--primary-color); border-color: var(--primary-color);">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function openEditDocumentModal(doc) {
        document.getElementById('editDocumentId').value = doc.id;
        document.getElementById('editDocumentName').value = doc.filename;
        document.getElementById('editDocumentCategory').value = doc.category || 'general';
        document.getElementById('editTaskId').value = doc.task_id || '';
        document.getElementById('editMeetingId').value = doc.meeting_id || '';

        const modal = new bootstrap.Modal(document.getElementById('editDocModal'));
        modal.show();
    }
    function openDocumentPreview(doc) {
        const modalTitle = document.getElementById('previewDocModalLabel');
        const imageBlock = document.getElementById('previewImageBlock');
        const previewImage = document.getElementById('previewImage');
        const previewFrame = document.getElementById('previewFrame');
        const previewText = document.getElementById('previewText');
        const previewUnsupported = document.getElementById('previewUnsupported');

        modalTitle.textContent = doc.filename;
        imageBlock.classList.add('d-none');
        previewFrame.classList.add('d-none');
        previewText.classList.add('d-none');
        previewUnsupported.classList.add('d-none');
        previewImage.removeAttribute('src');
        previewFrame.removeAttribute('src');

        const ext = (doc.filepath.split('.').pop() || '').toLowerCase();
        if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext)) {
            previewImage.src = doc.filepath;
            imageBlock.classList.remove('d-none');
        } else if (ext === 'pdf') {
            previewFrame.src = doc.filepath;
            previewFrame.classList.remove('d-none');
        } else if (['txt', 'csv', 'json', 'md'].includes(ext)) {
            fetch(doc.filepath)
                .then(response => response.text())
                .then(text => {
                    previewText.textContent = text;
                    previewText.classList.remove('d-none');
                })
                .catch(() => {
                    previewUnsupported.classList.remove('d-none');
                });
        } else {
            previewUnsupported.classList.remove('d-none');
        }

        const modal = new bootstrap.Modal(document.getElementById('previewDocModal'));
        modal.show();
    }
</script>

<?php include 'footer.php'; ?>
