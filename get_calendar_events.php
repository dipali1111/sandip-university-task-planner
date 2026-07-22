<?php
// get_calendar_events.php - JSON Endpoint for FullCalendar Events
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$events = [];

try {
    // 1. Fetch Meetings for user
    $m_query = "
        SELECT DISTINCT m.id, m.title, m.date_time 
        FROM meetings m
        LEFT JOIN meeting_participants mp ON m.id = mp.meeting_id
        WHERE m.creator_id = ? OR mp.user_id = ? OR ? = 'registrar' OR ? = 'admin'
    ";
    $m_stmt = $pdo->prepare($m_query);
    $m_stmt->execute([$user_id, $user_id, $role, $role]);
    
    while ($m = $m_stmt->fetch()) {
        $events[] = [
            'id' => 'meet_' . $m['id'],
            'title' => '👥 ' . $m['title'],
            'start' => date('Y-m-d\TH:i:s', strtotime($m['date_time'])),
            'color' => '#ff9f1c', // Meeting color: Accent Orange
            'extendedProps' => [
                'type' => 'meeting'
            ]
        ];
    }

    // 2. Fetch Tasks for user
    $t_query = "SELECT id, title, due_date, priority FROM tasks WHERE 1=1";
    $t_params = [];
    
    if ($role === 'faculty') {
        $t_query .= " AND assignee_id = ?";
        $t_params[] = $user_id;
    } elseif ($role === 'dean') {
        $t_query .= " AND (assigner_id = ? OR assignee_id = ?)";
        $t_params[] = $user_id;
        $t_params[] = $user_id;
    } // Registrar and Admin fetch all tasks
    
    $t_stmt = $pdo->prepare($t_query);
    $t_stmt->execute($t_params);
    
    while ($t = $t_stmt->fetch()) {
        $color = '#2ec4b6'; // Default green for low/completed
        if ($t['priority'] === 'high') {
            $color = '#e71d36'; // High priority red
        } elseif ($t['priority'] === 'medium') {
            $color = '#ffb703'; // Medium priority yellow
        }
        
        $events[] = [
            'id' => 'task_' . $t['id'],
            'title' => '📝 ' . $t['title'],
            'start' => $t['due_date'], // All-day event since tasks have due dates
            'allDay' => true,
            'color' => $color,
            'extendedProps' => [
                'type' => 'task'
            ]
        ];
    }

    echo json_encode($events);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
