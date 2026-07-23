<?php
// upload_document.php - Secure File Upload & Version Handler
require_once 'config.php';
check_login();

try {
    $check_col = $pdo->query("SHOW COLUMNS FROM documents LIKE 'category'");
    if ($check_col->rowCount() === 0) {
        $pdo->exec("ALTER TABLE documents ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'general'");
    }
} catch (PDOException $e) {
    // Ignore if the table is not ready yet; the upload flow will continue with a default category.
}

$allowed_categories = ['general', 'task', 'meeting', 'report', 'policy', 'training'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['doc_file'])) {
    $file = $_FILES['doc_file'];
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: documents.php?error=' . urlencode('File upload failed. Error code: ' . $file['error']));
        exit;
    }

    $filename = basename($file['name']);
    $file_size = $file['size'];
    $temp_path = $file['tmp_name'];
    
    // File validation
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file_size > $max_size) {
        header('Location: documents.php?error=' . urlencode('File exceeds maximum size limits (5MB).'));
        exit;
    }

    // Secure file extension checks
    $allowed_exts = ['pdf', 'docx', 'xlsx', 'png', 'jpg', 'txt', 'csv'];
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_exts)) {
        header('Location: documents.php?error=' . urlencode('Format not supported. Only PDF, DOCX, XLSX, PNG, JPG, CSV and TXT are allowed.'));
        exit;
    }

    // Set uploads directory path
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Versioning logic
    try {
        $check_stmt = $pdo->prepare("SELECT MAX(version) as max_ver FROM documents WHERE filename = ?");
        $check_stmt->execute([$filename]);
        $ver_row = $check_stmt->fetch();
        $version = $ver_row['max_ver'] ? ((int)$ver_row['max_ver'] + 1) : 1;

        // Make sure we generate a unique file name on storage to prevent file system conflicts
        $storage_name = pathinfo($filename, PATHINFO_FILENAME) . '_v' . $version . '.' . $file_ext;
        $destination = $upload_dir . $storage_name;

        if (move_uploaded_file($temp_path, $destination)) {
            // Save to database
            $db_filepath = 'uploads/' . $storage_name;
            $task_id = !empty($_POST['task_id']) ? (int)$_POST['task_id'] : null;
            $meeting_id = !empty($_POST['meeting_id']) ? (int)$_POST['meeting_id'] : null;
            $uploaded_by = $_SESSION['user_id'];
            $category = $_POST['document_category'] ?? 'general';
            if (!in_array($category, $allowed_categories, true)) {
                $category = 'general';
            }

            $insert_stmt = $pdo->prepare("INSERT INTO documents (filename, filepath, uploaded_by, version, task_id, meeting_id, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->execute([$filename, $db_filepath, $uploaded_by, $version, $task_id, $meeting_id, $category]);

            header('Location: documents.php?msg=uploaded');
            exit;
        } else {
            header('Location: documents.php?error=' . urlencode('Could not save file to disk. Check directory permissions.'));
            exit;
        }
    } catch (PDOException $e) {
        header('Location: documents.php?error=' . urlencode('Database error: ' . $e->getMessage()));
        exit;
    }
} else {
    header('Location: documents.php');
    exit;
}
?>
