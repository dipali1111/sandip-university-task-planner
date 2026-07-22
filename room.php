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
            --control-bg: #1f283e;
            --control-active: #ff9f1c;
            --control-danger: #e71d36;
            --border-meet: #232e48;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--meet-bg);
            color: #ffffff;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        /* Top Navigation Bar */
        .meet-topbar {
            height: 60px;
            background-color: var(--panel-bg);
            border-bottom: 1px solid var(--border-meet);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }
        .meet-title {
            font-size: 16px;
            font-weight: 700;
        }
        .meet-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--control-active);
        }

        /* Center Grid Layout */
        .meet-container {
            flex-grow: 1;
            display: flex;
            overflow: hidden;
            position: relative;
        }

        .main-stage {
            flex-grow: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            background-color: #05070a;
        }

        .screen-share-stage {
            width: 90%;
            height: 80%;
            background: linear-gradient(135deg, #1b2640 0%, #0f172a 100%);
            border: 2px solid var(--border-meet);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
        }
        
        .voice-indicator {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--control-active);
            color: var(--meet-bg);
            font-size: 28px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 0 rgba(255, 159, 28, 0.4);
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 159, 28, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 20px rgba(255, 159, 28, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 159, 28, 0); }
        }

        /* Right Panel (Notes, Participants, Chat) */
        .side-panel {
            width: 350px;
            background-color: var(--panel-bg);
            border-left: 1px solid var(--border-meet);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .side-panel.hidden {
            margin-right: -350px;
        }

        .side-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-meet);
        }
        .side-tab-btn {
            flex-grow: 1;
            background: none;
            border: none;
            color: #94a3b8;
            padding: 15px 0;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .side-tab-btn.active {
            color: var(--control-active);
            border-bottom: 2px solid var(--control-active);
        }

        .side-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            display: none;
        }
        .side-content.active {
            display: block;
        }

        /* Bottom Controls Bar */
        .meet-controls {
            height: 80px;
            background-color: var(--panel-bg);
            border-top: 1px solid var(--border-meet);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            z-index: 100;
        }

        .btn-control {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--control-bg);
            border: 1px solid var(--border-meet);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-control:hover {
            background-color: #2b3957;
            transform: scale(1.05);
        }
        .btn-control.active {
            background-color: white;
            color: var(--meet-bg);
        }
        .btn-control.danger {
            background-color: var(--control-danger);
            border-color: var(--control-danger);
        }
        .btn-control.danger:hover {
            background-color: #bd1328;
        }

        /* Participant Grid in side panel */
        .participant-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid var(--border-meet);
            font-size: 14px;
        }
        .avatar-circle-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--border-meet);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        /* Chat list styles */
        .chat-message-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 100%;
        }
        .chat-msg {
            background-color: var(--control-bg);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
        }
        .chat-sender {
            font-weight: 700;
            color: var(--control-active);
            margin-bottom: 2px;
        }
    </style>
</head>
<body>

    <!-- Topbar -->
    <header class="meet-topbar">
        <div class="meet-logo">
            <i data-lucide="graduation-cap"></i>
            <span class="font-weight-800" style="font-size:16px;">SANDIP UNI CALL</span>
        </div>
        <div class="meet-title">
            <?php echo sanitize($meeting['title']); ?>
        </div>
        <div>
            <span class="badge bg-danger py-2 px-3 font-weight-600"><i data-lucide="radio" style="width:14px;" class="me-1"></i> LIVE DEMO</span>
        </div>
    </header>

    <!-- Meeting Stage Area -->
    <div class="meet-container">
        
        <!-- Large Video Window Stage -->
        <main class="main-stage">
            <div class="screen-share-stage" id="meeting-stage">
                <!-- Presenter View (Simulated Screen share default) -->
                <div class="text-center p-4" id="presentation-content">
                    <i data-lucide="monitor" class="mb-3 text-warning" style="width: 60px; height: 60px;"></i>
                    <h4 class="font-weight-700">Sandip University Internal Planning Deck</h4>
                    <p class="text-white-50 max-width-600 mb-0 font-size-14" style="max-width: 500px; font-size:14px;">Presenting criteria documents, timelines, and coordinator targets for Board review.</p>
                    <small class="text-muted mt-3 d-block">Screen shared by Organiser: <?php echo sanitize($meeting['organizer']); ?></small>
                </div>
                <!-- Alternate Active Speaker View -->
                <div class="text-center d-none" id="speaker-content">
                    <div class="voice-indicator mb-3">
                        <?php echo strtoupper($meeting['organizer'][0]); ?>
                    </div>
                    <h5 class="font-weight-700"><?php echo sanitize($meeting['organizer']); ?></h5>
                    <small class="text-warning">Active Speaker (Meeting Organizer)</small>
                </div>
            </div>
            
            <div class="position-absolute bottom-0 start-0 m-4 p-2 rounded" style="background: rgba(0,0,0,0.6); font-size: 11px;">
                <span class="text-muted">Invite Link: </span>
                <span class="text-success"><?php echo sanitize($meeting['room_link'] ? $meeting['room_link'] : 'http://meet.sandip.edu/room?id='.$meeting_id); ?></span>
            </div>
        </main>

        <!-- Right Panels (Chat, Participants, Details) -->
        <aside class="side-panel" id="right-side-panel">
            <div class="side-tabs">
                <button class="side-tab-btn active" onclick="switchSideTab('tab-details')">Details</button>
                <button class="side-tab-btn" onclick="switchSideTab('tab-participants')">People (<?php echo count($participants); ?>)</button>
                <button class="side-tab-btn" onclick="switchSideTab('tab-chat')">Chat</button>
            </div>

            <!-- TAB 1: DETAILS -->
            <div class="side-content active" id="tab-details">
                <h6 class="font-weight-700 text-warning mb-2">Meeting Agenda</h6>
                <p class="font-size-13 text-white-50" style="font-size: 13px;"><?php echo nl2br(sanitize($meeting['agenda'] ? $meeting['agenda'] : 'No agenda posted.')); ?></p>
                <hr style="border-color: var(--border-meet);">
                <h6 class="font-weight-700 text-warning mb-2">Minutes of Meeting (MOM)</h6>
                <?php if ($meeting['mom_published']): ?>
                    <div class="p-3 border border-warning rounded" style="font-size: 12px; white-space: pre-wrap; background-color: rgba(255, 159, 28, 0.05);"><?php echo sanitize($meeting['mom']); ?></div>
                <?php else: ?>
                    <p class="text-muted font-size-12" style="font-size:12px;"><i data-lucide="info" style="width:14px; vertical-align:middle;"></i> MOM has not been published yet. The Coordinator will log this after the session finishes.</p>
                <?php endif; ?>
            </div>

            <!-- TAB 2: PARTICIPANTS -->
            <div class="side-content" id="tab-participants">
                <?php foreach ($participants as $p): ?>
                    <div class="participant-list-item">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle-sm">
                                <?php echo strtoupper($p['name'][0]); ?>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="font-weight-600"><?php echo sanitize($p['name']); ?></span>
                                <small class="text-muted text-capitalize" style="font-size: 10px;"><?php echo sanitize($p['role'] === 'registrar' ? 'Registrar/VC' : ($p['role'] === 'dean' ? 'Dean/HOD' : $p['role'])); ?></small>
                            </div>
                        </div>
                        <div>
                            <i data-lucide="mic" style="width: 14px; color:#4ade80;"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- TAB 3: CHAT -->
            <div class="side-content" id="tab-chat">
                <div class="chat-message-list">
                    <div class="chat-msg">
                        <div class="chat-sender">Prof. Neha Gupta</div>
                        <div>Good morning everyone, I have uploaded the draft syllabus.</div>
                    </div>
                    <div class="chat-msg">
                        <div class="chat-sender">Dr. Sandeep Sharma</div>
                        <div>Thank you Neha. We will review it in this session.</div>
                    </div>
                    <div class="chat-msg">
                        <div class="chat-sender">Mr. Vicky Verma</div>
                        <div>I am taking notes for the MOM logs. Please proceed.</div>
                    </div>
                </div>
                <div class="mt-3 border-top pt-2">
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Type a message...">
                        <button class="btn btn-sm btn-primary" style="background-color: var(--control-active); border-color: var(--control-active); color:var(--meet-bg);"><i data-lucide="send" style="width:14px;"></i></button>
                    </div>
                </div>
            </div>
        </aside>

    </div>

    <!-- Bottom Controls -->
    <div class="meet-controls">
        <button class="btn-control" id="ctrl-mic" onclick="toggleControl('ctrl-mic', 'mic', 'mic-off')">
            <i data-lucide="mic"></i>
        </button>
        <button class="btn-control" id="ctrl-video" onclick="toggleControl('ctrl-video', 'video', 'video-off')">
            <i data-lucide="video"></i>
        </button>
        <button class="btn-control" id="ctrl-screen" onclick="toggleStage()">
            <i data-lucide="monitor"></i>
        </button>
        <button class="btn-control" onclick="toggleSidePanel()">
            <i data-lucide="message-square"></i>
        </button>
        <a href="meetings.php" class="btn-control danger">
            <i data-lucide="phone-off"></i>
        </a>
    </div>

    <script>
        lucide.createIcons();

        // Control buttons toggles
        function toggleControl(btnId, activeIcon, inactiveIcon) {
            const btn = document.getElementById(btnId);
            btn.classList.toggle('active');
            
            const isOff = btn.classList.contains('active');
            btn.innerHTML = `<i data-lucide="${isOff ? inactiveIcon : activeIcon}"></i>`;
            lucide.createIcons();
        }

        // Toggle Stage (Screen Share vs Speaker)
        let sharing = true;
        function toggleStage() {
            sharing = !sharing;
            const stage = document.getElementById('meeting-stage');
            const presContent = document.getElementById('presentation-content');
            const speakerContent = document.getElementById('speaker-content');
            const screenBtn = document.getElementById('ctrl-screen');

            screenBtn.classList.toggle('active');

            if (sharing) {
                presContent.classList.remove('d-none');
                speakerContent.classList.add('d-none');
                stage.style.background = "linear-gradient(135deg, #1b2640 0%, #0f172a 100%)";
            } else {
                presContent.classList.add('d-none');
                speakerContent.classList.remove('d-none');
                stage.style.background = "#141a29";
            }
        }

        // Toggle Right Sidebar Panel
        function toggleSidePanel() {
            const panel = document.getElementById('right-side-panel');
            panel.classList.toggle('hidden');
        }

        // Switch side tab contents
        function switchSideTab(tabId) {
            // Remove active classes
            document.querySelectorAll('.side-tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.side-content').forEach(content => content.classList.remove('active'));

            // Find target element
            const activeBtn = event.currentTarget;
            activeBtn.classList.add('active');

            const activeContent = document.getElementById(tabId);
            activeContent.classList.add('active');
        }
    </script>
</body>
</html>
