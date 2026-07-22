<?php
// config.php - Sandip University Internal Task Planner Database Connection & Session Configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sandip_planner';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Elegant fall-back screen with setup instructions
    die("
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Connection Error - Sandip University Planner</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'Outfit', sans-serif; background-color: #0f2b5c; color: white; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .card { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 16px; max-width: 600px; padding: 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); }
            h1 { font-weight: 800; color: #ff9f1c; }
            code { background: rgba(0, 0, 0, 0.3); padding: 2px 6px; border-radius: 4px; color: #ff9f1c; }
        </style>
    </head>
    <body>
        <div class='card text-center'>
            <h1 class='mb-4'>Database Connection Error</h1>
            <p class='lead'>We could not connect to the database <code>$db_name</code> on <code>$db_host</code>.</p>
            <hr class='my-4' style='border-color: rgba(255, 255, 255, 0.2);'>
            <h5 class='text-start mb-3'>How to fix:</h5>
            <ol class='text-start'>
                <li class='mb-2'>Ensure your local MySQL server (like <strong>XAMPP</strong> or <strong>WampServer</strong>) is running.</li>
                <li class='mb-2'>Create a database named <code>$db_name</code> in phpMyAdmin or MySQL console.</li>
                <li class='mb-2'>Import the file <code>database.sql</code> located in this project's directory into the database.</li>
                <li class='mb-2'>If your MySQL credentials are different from user: <code>root</code> and empty password, please update <code>config.php</code>.</li>
            </ol>
            <p class='mt-4 mb-0 text-muted'>Error Details: " . htmlspecialchars($e->getMessage()) . "</p>
            <a href='index.php' class='btn btn-warning mt-4 py-2 px-4'>Retry Connection</a>
        </div>
    </body>
    </html>
    ");
}

// User login & role verification helpers
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function check_roles($allowed_roles) {
    check_login();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header('Location: dashboard.php?error=unauthorized');
        exit;
    }
}

// Global Notification dispatcher helper
function add_notification($pdo, $user_id, $message) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    return $stmt->execute([$user_id, $message]);
}

// Helper to escape HTML output
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>
