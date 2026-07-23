<?php
// schedule_meeting.php - Meeting Scheduling Page and POST Handler
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
}

$creator_id = $_SESSION['user_id'];
$committee_stmt = $pdo->prepare("SELECT id, name FROM committees WHERE coordinator_id = ? OR creator_id = ? ORDER BY name ASC");
$committee_stmt->execute([$creator_id, $creator_id]);
$my_committees = $committee_stmt->fetchAll();

$user_stmt = $pdo->query("SELECT id, name, role FROM users ORDER BY name ASC");
$all_users = $user_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Meeting - Sandip University Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7fb;
            color: #1f2937;
            min-height: 100vh;
            padding: 2rem 0;
        }
        .schedule-card {
            border: 0;
            border-radius: 1.25rem;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        }
        .form-control,
        .form-select,
        .form-check-input {
            border-radius: 1rem;
        }
        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.18);
            border-color: #0d6efd;
        }
        .form-label {
            font-weight: 600;
            color: #0f172a;
        }
        .field-note {
            font-size: 0.92rem;
            color: #6b7280;
        }
        .participant-card {
            max-height: 280px;
            overflow-y: auto;
            border-radius: 1rem;
        }
        .btn-schedule {
            background: linear-gradient(135deg, #0b5ed7 0%, #6610f2 100%);
            border: none;
            box-shadow: 0 12px 24px rgba(11, 94, 215, 0.18);
        }
        .btn-schedule:hover {
            background: linear-gradient(135deg, #0a58ca 0%, #5b0bd5 100%);
        }
        .section-heading {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .icon-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.96rem;
        }
        .input-group-text {
            border-radius: 0.95rem;
            background-color: #eef2ff;
            border: 1px solid #dbeafe;
            color: #3b82f6;
        }
        @media (max-width: 575.98px) {
            .schedule-card {
                margin: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9">
                <div class="card schedule-card p-4 p-md-5">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                        <div>
                            <h1 class="h3 mb-1">Schedule a New Meeting</h1>
                            <p class="text-muted mb-0">Use this form to create a committee meeting and invite participants instantly.</p>
                        </div>
                        <div class="text-sm-end">
                            <span class="badge rounded-pill bg-primary-subtle text-primary py-2 px-3">Role: <?php echo ucfirst($_SESSION['role']); ?></span>
                        </div>
                    </div>

                    <form action="schedule_meeting.php" method="POST" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="title" class="form-label icon-label"><i class="bi bi-clipboard-check"></i>Meeting Title</label>
                            <input type="text" id="title" name="title" class="form-control form-control-lg" placeholder="Research Review Committee Session" required>
                            <div class="invalid-feedback">Please provide a meeting title.</div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="date_time" class="form-label icon-label"><i class="bi bi-calendar-event"></i>Date & Time</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
                                    <input type="datetime-local" id="date_time" name="date_time" class="form-control" required value="<?php echo date('Y-m-d\TH:00', strtotime('+1 day')); ?>">
                                </div>
                                <div class="invalid-feedback">Please select meeting date and time.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="committee_id" class="form-label icon-label"><i class="bi bi-building"></i>Committee Reference</label>
                                <select id="committee_id" name="committee_id" class="form-select">
                                    <option value="">General Meeting</option>
                                    <?php foreach ($my_committees as $com): ?>
                                        <option value="<?php echo $com['id']; ?>"><?php echo sanitize($com['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="field-note">Optional committee or department alignment.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="room_link" class="form-label icon-label"><i class="bi bi-link-45deg"></i>Meeting Room / Meeting Link</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link"></i></span>
                                <input type="url" id="room_link" name="room_link" class="form-control" placeholder="https://meet.google.com/abc-defg-hij" value="https://meet.google.com/abc-defg-hij">
                            </div>
                            <div class="field-note">Provide the conference link if available.</div>
                        </div>

                        <div class="mb-4">
                            <label for="agenda" class="form-label icon-label"><i class="bi bi-card-text"></i>Meeting Agenda</label>
                            <textarea id="agenda" name="agenda" class="form-control form-control-lg" rows="4" placeholder="Enter the key discussion points and outcomes..." style="min-height: 140px;"></textarea>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="section-heading mb-0"><i class="bi bi-people-fill me-2"></i>Participants</div>
                                <span class="field-note">Select invited users for this meeting.</span>
                            </div>
                            <div class="card participant-card border-0 bg-white p-3 shadow-sm">
                                <div class="row gy-3">
                                    <?php foreach ($all_users as $au): ?>
                                        <div class="col-sm-6">
                                            <div class="form-check rounded-3 border px-3 py-2">
                                                <input class="form-check-input" type="checkbox" name="participants[]" value="<?php echo $au['id']; ?>" id="participant_<?php echo $au['id']; ?>">
                                                <label class="form-check-label ms-2" for="participant_<?php echo $au['id']; ?>">
                                                    <strong><?php echo sanitize($au['name']); ?></strong><br>
                                                    <small class="text-muted text-capitalize"><?php echo sanitize($au['role']); ?></small>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
                            <a href="meetings.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4">Back to Meetings</a>
                            <button type="submit" class="btn btn-schedule btn-lg rounded-pill px-5">Schedule Meeting</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>

