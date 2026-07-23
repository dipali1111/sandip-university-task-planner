<?php
require_once 'config.php';
check_login();

$allowed_categories = ['general', 'task', 'meeting', 'report', 'policy', 'training'];
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_document') {
        $document_id = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
        if ($document_id) {
            $stmt = $pdo->prepare("SELECT uploaded_by, filepath FROM documents WHERE id = ?");
            $stmt->execute([$document_id]);
            $doc = $stmt->fetch();

            if ($doc && ($doc['uploaded_by'] == $user_id || in_array($role, ['admin', 'registrar', 'dean'], true))) {
                $storage_path = __DIR__ . '/' . ltrim($doc['filepath'], '/');
                if (file_exists($storage_path)) {
                    @unlink($storage_path);
                }

                $delete_stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
                $delete_stmt->execute([$document_id]);
                header('Location: documents.php?msg=deleted');
                exit;
            }
        }

        header('Location: documents.php?error=unauthorized');
        exit;
    }

    if ($action === 'edit_document') {
        $document_id = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
        $document_name = trim($_POST['document_name'] ?? '');
        $category = $_POST['document_category'] ?? 'general';
        $task_id = !empty($_POST['task_id']) ? (int)$_POST['task_id'] : null;
        $meeting_id = !empty($_POST['meeting_id']) ? (int)$_POST['meeting_id'] : null;

        if (!in_array($category, $allowed_categories, true)) {
            $category = 'general';
        }

        if ($document_id && $document_name !== '') {
            $stmt = $pdo->prepare("SELECT uploaded_by FROM documents WHERE id = ?");
            $stmt->execute([$document_id]);
            $doc = $stmt->fetch();

            if ($doc && ($doc['uploaded_by'] == $user_id || in_array($role, ['admin', 'registrar', 'dean'], true))) {
                $update_stmt = $pdo->prepare("UPDATE documents SET filename = ?, category = ?, task_id = ?, meeting_id = ? WHERE id = ?");
                $update_stmt->execute([$document_name, $category, $task_id, $meeting_id, $document_id]);
                header('Location: documents.php?msg=updated');
                exit;
            }
        }

        header('Location: documents.php?error=invalid_edit');
        exit;
    }
}

header('Location: documents.php');
exit;
