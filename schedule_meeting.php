<?php
// schedule_meeting.php - Meeting Scheduling POST Handler
require_once 'config.php';
check_login();

// Allow only Dean, Coordinator, or Admin roles
if (!in_array($_SESSION['role'], ['dean', 'coordinator', 'admin'])) {
    header('Location: meetings.php?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $agenda = sanitize($_POST['agenda'] ?? '');
    $date_time = $_POST['date_time'] ?? '';
    $room_link = sanitize($_POST['room_link'] ?? '');
    $committee_id = !empty($_POST['committee_id']) ? filter_input(INPUT_POST, 'committee_id', FILTER_VALIDATE_INT) : null;
    $creator_id = $_SESSION['user_id'];
    $participants = $_POST['participants'] ?? []; // Array of invited user IDs

    if (!empty($title) && !empty($date_time)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO meetings (title, description, date_time, room_link, agenda, creator_id, committee_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $title, 
                $agenda, // Stored description and agenda together or separately
                $date_time, 
                $room_link, 
                $agenda, 
                $creator_id, 
                $committee_id
            ]);
            
            $meeting_id = $pdo->lastInsertId();

            // Insert participants
            if (!empty($participants)) {
                $p_stmt = $pdo->prepare("INSERT INTO meeting_participants (meeting_id, user_id) VALUES (?, ?)");
                foreach ($participants as $p_id) {
                    $p_id = (int)$p_id;
                    $p_stmt->execute([$meeting_id, $p_id]);
                    
                    // Alert invited users
                    $message = "New Meeting Scheduled: " . $title . " on " . date('d M, h:i A', strtotime($date_time));
                    add_notification($pdo, $p_id, $message);
                }
            }
            
            // Add creator as participant implicitly
            $p_stmt = $pdo->prepare("INSERT IGNORE INTO meeting_participants (meeting_id, user_id) VALUES (?, ?)");
            $p_stmt->execute([$meeting_id, $creator_id]);

            $pdo->commit();
            header('Location: meetings.php?msg=success');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            header('Location: meetings.php?error=' . urlencode('Database error: ' . $e->getMessage()));
            exit;
        }
    } else {
        header('Location: meetings.php?error=missing_fields');
        exit;
    }
} else {
    header('Location: meetings.php');
    exit;
}
?>
