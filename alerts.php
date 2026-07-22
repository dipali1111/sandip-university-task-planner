<?php
// alerts.php - Alerts and Notifications Manager
require_once 'config.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$success_msg = '';

// Handle mark all as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $success_msg = "All notifications marked as read.";
        
        // Redirect to clean URL
        header('Location: alerts.php');
        exit;
    } catch (PDOException $e) {
        $error_msg = "Database error: " . $e->getMessage();
    }
}

// Handle mark individual as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $noti_id = filter_input(INPUT_POST, 'noti_id', FILTER_VALIDATE_INT);
    if ($noti_id) {
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$noti_id, $user_id]);
            $success_msg = "Notification marked as read.";
        } catch (PDOException $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all notifications for user
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $all_notis = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_notis = [];
}
?>

<div class="container-fluid p-0">
    <div class="page-title">
        <div>
            <h1 class="m-0 font-weight-800" style="font-size: 28px;">Alerts & Notifications</h1>
            <p class="text-muted font-size-14 mb-0" style="font-size: 14px;">Review logs of task assignments, updates, and scheduled meetings.</p>
        </div>
        <div>
            <?php if (!empty($all_notis)): ?>
                <a href="alerts.php?action=mark_all_read" class="btn btn-outline-secondary py-2 px-3 font-weight-600">
                    <i data-lucide="check-check" class="me-1" style="width:16px; vertical-align: middle;"></i> Mark All Read
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success py-2 font-size-13">
            <i data-lucide="check-circle" class="me-1" style="width: 16px; vertical-align: middle;"></i>
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">Notification Log</div>
                <div class="card-body p-0">
                    <?php if (empty($all_notis)): ?>
                        <div class="text-center text-muted py-5">
                            <i data-lucide="bell-off" class="mb-2" style="width: 48px; height: 48px; stroke-width: 1;"></i>
                            <p class="mb-0">You have no notifications yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($all_notis as $n): ?>
                                <div class="list-group-item p-3 d-flex align-items-center justify-content-between <?php echo $n['is_read'] == 0 ? 'bg-light font-weight-600 border-start border-primary border-4' : ''; ?>">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="mt-1">
                                            <?php if ($n['is_read'] == 0): ?>
                                                <span class="p-1 rounded-circle bg-primary d-inline-block"></span>
                                            <?php else: ?>
                                                <span class="p-1 rounded-circle bg-secondary d-inline-block"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="mb-1 text-dark"><?php echo sanitize($n['message']); ?></p>
                                            <small class="text-muted"><i data-lucide="clock" style="width: 12px; vertical-align: middle;" class="me-1"></i> <?php echo date('d F Y, h:i A', strtotime($n['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <?php if ($n['is_read'] == 0): ?>
                                        <form action="alerts.php" method="POST">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="noti_id" value="<?php echo $n['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary py-1 px-3 font-size-11" style="font-size: 11px;">
                                                Mark Read
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>
