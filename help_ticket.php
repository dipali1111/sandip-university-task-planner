<?php
// help_ticket.php - Help Desk Ticket POST Handler
require_once 'config.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (!empty($subject) && !empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO help_tickets (user_id, subject, message, status) VALUES (?, ?, ?, 'open')");
            $stmt->execute([$user_id, $subject, $message]);
            
            // Redirect back to dashboard with a success state
            header('Location: dashboard.php?msg=ticket_submitted');
            exit;
        } catch (PDOException $e) {
            header('Location: dashboard.php?error=' . urlencode('IT Support Desk error: ' . $e->getMessage()));
            exit;
        }
    } else {
        header('Location: dashboard.php?error=empty_ticket');
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
}
?>
