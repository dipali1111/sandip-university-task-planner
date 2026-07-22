<?php
// create_task.php - Task Creation POST Handler
require_once 'config.php';
check_login();

// Allow only Dean or Admin roles
if (!in_array($_SESSION['role'], ['dean', 'admin'])) {
    header('Location: tasks.php?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $priority = sanitize($_POST['priority'] ?? 'medium');
    $due_date = $_POST['due_date'] ?? '';
    $assignee_id = filter_input(INPUT_POST, 'assignee_id', FILTER_VALIDATE_INT);
    $assigner_id = $_SESSION['user_id'];

    if (!empty($title) && !empty($due_date) && $assignee_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, priority, status, assigner_id, assignee_id, due_date) VALUES (?, ?, ?, 'todo', ?, ?, ?)");
            $stmt->execute([$title, $description, $priority, $assigner_id, $assignee_id, $due_date]);
            
            // Add notification for the assignee faculty
            $message = "You have been assigned a new task: " . $title . " (Due: " . date('d M', strtotime($due_date)) . ")";
            add_notification($pdo, $assignee_id, $message);
            
            header('Location: tasks.php?msg=success');
            exit;
        } catch (PDOException $e) {
            header('Location: tasks.php?error=' . urlencode('Database error: ' . $e->getMessage()));
            exit;
        }
    } else {
        header('Location: tasks.php?error=missing_fields');
        exit;
    }
} else {
    header('Location: tasks.php');
    exit;
}
?>
