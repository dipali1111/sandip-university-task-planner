<?php
// room.php - Immersive Simulated Virtual Meeting Room
require_once 'config.php';
check_login();

$meeting_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$meeting_id) {
    header('Location: meetings.php');
    exit;
}

// Fetch meeting details
$stmt = $pdo->prepare("SELECT m.*, u.name as organizer FROM meetings m JOIN users u ON m.creator_id = u.id WHERE m.id = ?");
$stmt->execute([$meeting_id]);
$meeting = $stmt->fetch();

if (!$meeting) {
    header('Location: meetings.php');
    exit;
}

// Fetch meeting participants
$p_stmt = $pdo->prepare("SELECT u.name, u.role FROM meeting_participants mp JOIN users u ON mp.user_id = u.id WHERE mp.meeting_id = ?");
$p_stmt->execute([$meeting_id]);
$participants = $p_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Room: <?php echo sanitize($meeting['title']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --meet-bg: #0b0f19;
            --panel-bg: #141a29;
            --surface: rgba(255, 255, 255, 0.05);
            --border-meet: rgba(255, 255, 255, 0.1);
            --accent: #0b5ed7;
            --accent-soft: #7dd3fc;
            --text-soft: #cbd5e1;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(180deg, #050914 0%, #0b0f19 100%);
            color: #f8fafc;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .meet-topbar {
            background-color: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid var(--border-meet);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            gap: 1rem;
        }
        .meet-topbar .meet-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .meet-topbar .meet-logo i {
            width: 24px;
            height: 24px;
        }
        .meet-headline {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .meet-headline h1 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
        }
        .meet-headline p {
            margin: 0;
            color: var(--text-soft);
            font-size: 0.95rem;
        }
        .page-shell {
            flex-grow: 1;
            padding: 1.5rem;
        }
        .room-card,
        .panel-card,
        .info-card {
            background: rgba(18, 27, 46, 0.92);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 1.2rem;
            box-shadow: 0 24px 60px rgba(0,0,0,0.25);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .room-card:hover,
        .panel-card:hover,
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 32px 80px rgba(0,0,0,0.3);
        }
        .card-body {
            padding: 1.75rem;
        }
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #f8fafc;
        }
        .text-muted-light {
            color: var(--text-soft);
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 0.9rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .status-available {
            background-color: rgba(11, 94, 215, 0.15);
            color: #7dd3fc;
        }
        .status-completed {
            background-color: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
        }
        .info-list {
            list-style: none;
            padding: 0;
            margin: 1rem 0 0;
        }
        .info-list li {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            font-size: 0.95rem;
            color: #dbe2ef;
        }
        .info-list li:last-child {
            border-bottom: none;
        }
        .info-list i {
            width: 20px;
            height: 20px;
            color: var(--accent-soft);
        }
        .room-link-btn {
            border-radius: 1rem;
            background: linear-gradient(135deg, #0b5ed7, #6610f2);
            border: none;
            color: #ffffff;
            font-weight: 600;
        }
        .room-link-btn:hover {
            background: linear-gradient(135deg, #0847a1, #5200c6);
        }
        .participant-list-item {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 1rem;
            padding: 1rem 1.15rem;
            margin-bottom: 0.85rem;
        }
        .participant-name {
            font-weight: 700;
            color: #f8fafc;
        }
        .participant-role {
            font-size: 0.82rem;
            color: #94a3b8;
        }
        .page-footer {
            padding: 1rem 1.5rem 1.5rem;
            background: transparent;
        }
        @media (max-width: 991.98px) {
            .meet-topbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .meet-topbar .meet-headline {
                width: 100%;
            }
        }
        @media (max-width: 767.98px) {
            .page-shell {
                padding: 1rem;
            }
            .card-body {
                padding: 1.25rem;
            }
            .info-list li {
                grid-template-columns: 1fr;
            }
            .info-list li strong {
                justify-self: start;
            }
        }
    </style>
</head>
<body>

    <header class="meet-topbar">
        <div class="meet-logo">
            <i data-lucide="monitor-speaker"></i>
            <span>Sandip Meeting Room</span>
        </div>
        <div class="meet-headline">
            <h1>Meeting Room Overview</h1>
            <p class="mb-0">A polished room view for your scheduled session and participants.</p>
        </div>
        <div>
            <?php if (!empty($meeting['room_link'])): ?>
                <a href="<?php echo sanitize($meeting['room_link']); ?>" class="btn room-link-btn" target="_blank" rel="noreferrer">
                    <i data-lucide="link-2" class="me-2"></i>Open Meeting Link
                </a>
            <?php else: ?>
                <span class="status-badge status-completed"><i data-lucide="x-circle"></i>No Link</span>
            <?php endif; ?>
        </div>
    </header>

    <main class="page-shell">
        <div class="container-fluid px-0">
            <div class="row g-4">
                <div class="col-xl-7">
                    <div class="room-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-4 flex-column flex-sm-row">
                                <div>
                                    <h2 class="section-title">Room Details</h2>
                                    <p class="text-muted-light mb-0">Your current meeting session at a glance.</p>
                                </div>
                                <span class="status-badge status-available"><i data-lucide="wifi"></i> Available</span>
                            </div>

                            <ul class="info-list">
                                <li><i data-lucide="calendar"></i><span>Title</span><strong class="text-end text-wrap"><?php echo sanitize($meeting['title']); ?></strong></li>
                                <li><i data-lucide="clock"></i><span>Date & Time</span><strong><?php echo date('d M Y, h:i A', strtotime($meeting['date_time'])); ?></strong></li>
                                <li><i data-lucide="user"></i><span>Organizer</span><strong><?php echo sanitize($meeting['organizer']); ?></strong></li>
                                <li><i data-lucide="users"></i><span>Participants</span><strong><?php echo count($participants); ?></strong></li>
                                <li><i data-lucide="map-pin"></i><span>Room Link</span><strong class="text-wrap"><?php echo sanitize($meeting['room_link'] ?: 'Not provided'); ?></strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="info-card h-100">
                        <div class="card-body d-flex flex-column h-100">
                            <div class="mb-4">
                                <h2 class="section-title">Agenda & Notes</h2>
                                <p class="text-muted-light mb-0">Review the meeting agenda and room summary.</p>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-white-50" style="white-space: pre-wrap; line-height: 1.7;">
                                    <?php echo sanitize($meeting['agenda'] ?: 'No agenda posted for this meeting.'); ?>
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="<?php echo sanitize($meeting['room_link'] ?: '#'); ?>" class="btn room-link-btn w-100" target="_blank" rel="noreferrer">
                                    <i data-lucide="video" class="me-2"></i>Join Meeting Room
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="panel-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-4 flex-column flex-md-row gap-3">
                                <div>
                                    <h2 class="section-title">Participants</h2>
                                    <p class="text-muted-light mb-0">People invited to this meeting.</p>
                                </div>
                                <span class="badge status-badge status-available"><i data-lucide="users"></i> <?php echo count($participants); ?> Participants</span>
                            </div>
                            <?php foreach ($participants as $p): ?>
                                <div class="participant-list-item">
                                    <div>
                                        <div class="participant-name"><?php echo sanitize($p['name']); ?></div>
                                        <div class="participant-role"><?php echo sanitize($p['role']); ?></div>
                                    </div>
                                    <span class="badge bg-primary text-white">Joined</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
