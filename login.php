<?php
// login.php - Sandip University Planner Authentication Controller
require_once 'config.php';

// Direct redirection if already logged in
if (isset($_SESSION['user_id']) && !isset($_GET['action'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Handle quick-switch or login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['action']) && $_GET['action'] === 'switch') {
        // Quick switch demo role bypass
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];
                
                header('Location: dashboard.php');
                exit;
            }
        }
        header('Location: index.php?error=invalid_switch');
        exit;
    } else {
        // Standard Login verification
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Foolproof check: standard verify, plain text DB value check, or master demo password check
            if ($user && (password_verify($password, $user['password']) || $password === $user['password'] || $password === 'password123')) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];

                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Please enter a valid email and password.';
        }
        
        // Redirect back to index with error message
        header('Location: index.php?error=' . urlencode($error));
        exit;
    }
} else {
    // If user accesses login.php via GET, redirect to landing page
    header('Location: index.php');
    exit;
}
?>
