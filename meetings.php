<?php
// meetings.php - Meeting Scheduler & MOM Module
require_once 'config.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$dept_id = $_SESSION['department_id'];

$success_msg = '';
$error_msg = '';

// Handle MOM Save/Publish actions (Coordinator/Dean only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_mom') {
    $meeting_id = filter_input(INPUT_POST, 'meeting_id', FILTER_VALIDATE_INT);
    $mom_text = sanitize($_POST['mom_text'] ?? '');
    $publish = isset($_POST['publish']) ? 1 : 0;
    
    if ($meeting_id && !empty($mom_text)) {
        try {
            // Verify access (must be creator of meeting, coordinator, or admin)
            $check = $pdo->prepare("SELECT * FROM meetings WHERE id = ?");
            $check->execute([$meeting_id]);
            $meet = $check->fetch();
            
            if ($meet && ($meet['creator_id'] == $user_id || $role === 'coordinator' || $role === 'admin')) {
                $stmt = $pdo->prepare("UPDATE meetings SET mom = ?, mom_published = ? WHERE id = ?");
                $stmt->execute([$mom_text, $publish, $meeting_id]);
                
                // If published, notify participants
                if ($publish) {
                    $p_stmt = $pdo->prepare("SELECT user_id FROM meeting_participants WHERE meeting_id = ?");
                    $p_stmt->execute([$meeting_id]);
                    while ($p = $p_stmt->fetch()) {
                        add_notification($pdo, $p['user_id'], "Minutes of Meeting (MOM) published for: " . $meet['title']);
                    }
                }
                
                $success_msg = $publish ? "MOM published successfully!" : "MOM draft saved successfully!";
            } else {
                $error_msg = "You do not have permission to log MOM for this meeting.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all users to populate the invite checkboxes
$users_stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id != ? ORDER BY name ASC");
$users_stmt->execute([$user_id]);
$all_users = $users_stmt->fetchAll();

// Fetch committees for coordinator
$comm_stmt = $pdo->prepare("SELECT id, name FROM committees WHERE coordinator_id = ? OR ? = 'admin'");
$comm_stmt->execute([$user_id, $role]);
$my_committees = $comm_stmt->fetchAll();

// Fetch meetings for the user (where they are organizer or invited)
$query = "
    SELECT DISTINCT m.*, u.name as organizer, c.name as committee_name
    FROM meetings m
    JOIN users u ON m.creator_id = u.id
    LEFT JOIN committees c ON m.committee_id = c.id
    LEFT JOIN meeting_participants mp ON m.id = mp.meeting_id
    WHERE m.creator_id = ? OR mp.user_id = ? OR ? = 'registrar' OR ? = 'admin'
    ORDER BY m.date_time ASC
";
$meet_stmt = $pdo->prepare($query);
$meet_stmt->execute([$user_id, $user_id, $role, $role]);
$meetings = $meet_stmt->fetchAll();

$upcoming_meetings = [];
$past_meetings = [];
foreach ($meetings as $m) {
    if (strtotime($m['date_time']) >= time()) {
        $upcoming_meetings[] = $m;
    } else {
        $past_meetings[] = $m;
    }
}
?>

<div class="container-fluid p-0">
    <div class="page-title">
        <div>
            <h1 class="m-0 font-weight-800" style="font-size: 28px;">Meetings Board</h1>
            <p class="text-muted font-size-14 mb-0" style="font-size: 14px;">Schedule committee gatherings, manage agendas, and publish Minutes of Meetings (MOM).</p>
        </div>
        <div>
            <?php if (in_array($role, ['dean', 'coordinator', 'admin'])): ?>
                <button class="btn btn-primary py-2 px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#scheduleMeetingModal" style="background-color: var(--primary-color); border-color: var(--primary-color);">
                    <i data-lucide="calendar-plus" class="me-2" style="width: 16px; height: 16px; vertical-align: middle;"></i>Schedule Meeting
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success py-2 font-size-13">
            <i data-lucide="check-circle" class="me-1" style="width: 16px; vertical-align: middle;"></i>
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger py-2 font-size-13">
            <i data-lucide="alert-circle" class="me-1" style="width: 16px; vertical-align: middle;"></i>
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Upcoming Meetings Column -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><span class="d-flex align-items-center gap-2"><i data-lucide="clock" class="text-warning"></i> Upcoming Meetings</span></div>
                <div class="card-body">
                    <?php if (empty($upcoming_meetings)): ?>
                        <div class="text-center text-muted py-5">
                            <i data-lucide="calendar" class="mb-2" style="width:40px; height:40px; stroke-width:1;"></i>
                            <p>No upcoming meetings found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_meetings as $um): ?>
                            <div class="p-3 border rounded mb-3 bg-light position-relative">
                                <span class="badge bg-secondary font-size-10 text-capitalize float-end" style="font-size: 10px;">
                                    <?php echo sanitize($um['committee_name'] ?? 'General Meeting'); ?>
                                </span>
                                <h5 class="font-weight-700 text-primary mb-1"><?php echo sanitize($um['title']); ?></h5>
                                <p class="text-muted font-size-12 mb-2" style="font-size: 12px;"><?php echo sanitize($um['description']); ?></p>
                                <div class="font-size-13 text-dark mb-3" style="font-size: 13px;">
                                    <div class="mb-1"><i data-lucide="calendar" style="width:14px; vertical-align: middle;" class="me-1 text-muted"></i> <?php echo date('d M Y, h:i A', strtotime($um['date_time'])); ?></div>
                                    <div><i data-lucide="user" style="width:14px; vertical-align: middle;" class="me-1 text-muted"></i> Organizer: <strong><?php echo sanitize($um['organizer']); ?></strong></div>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if (!empty($um['room_link'])): ?>
                                        <a href="room.php?id=<?php echo $um['id']; ?>" class="btn btn-sm btn-success px-3 font-weight-600"><i data-lucide="video" style="width:14px;" class="me-1"></i> Join Meeting Room</a>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-secondary font-weight-600" onclick="showMeetingDetails(<?php echo htmlspecialchars(json_encode($um)); ?>)">View Details</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Past Meetings Column -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><span class="d-flex align-items-center gap-2"><i data-lucide="check-circle-2" class="text-success"></i> Past Meetings & MOM Logs</span></div>
                <div class="card-body">
                    <?php if (empty($past_meetings)): ?>
                        <div class="text-center text-muted py-5">
                            <i data-lucide="history" class="mb-2" style="width:40px; height:40px; stroke-width:1;"></i>
                            <p>No past meetings logged.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($past_meetings as $pm): ?>
                            <div class="p-3 border rounded mb-3 bg-light">
                                <span class="badge float-end <?php echo $pm['mom_published'] ? 'bg-success' : (!empty($pm['mom']) ? 'bg-warning text-dark' : 'bg-danger'); ?>" style="font-size: 10px;">
                                    <?php echo $pm['mom_published'] ? 'MOM Published' : (!empty($pm['mom']) ? 'MOM Draft' : 'Pending MOM'); ?>
                                </span>
                                <h5 class="font-weight-700 text-dark mb-1"><?php echo sanitize($pm['title']); ?></h5>
                                <p class="text-muted font-size-12 mb-2" style="font-size: 12px;"><?php echo date('d M Y, h:i A', strtotime($pm['date_time'])); ?></p>
                                
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-sm btn-outline-secondary font-weight-600" onclick="showMeetingDetails(<?php echo htmlspecialchars(json_encode($pm)); ?>)">View Details</button>
                                    
                                    <?php if (($pm['creator_id'] == $user_id || $role === 'coordinator' || $role === 'admin')): ?>
                                        <button class="btn btn-sm btn-outline-primary font-weight-600" onclick="openMOMEditor(<?php echo $pm['id']; ?>, <?php echo htmlspecialchars(json_encode($pm['mom'])); ?>)">
                                            <i data-lucide="file-signature" style="width:14px;" class="me-1"></i> Log MOM
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================================================================
     SCHEDULE MEETING MODAL
     ================================================================== -->
<?php if (in_array($role, ['dean', 'coordinator', 'admin'])): ?>
<div class="modal fade" id="scheduleMeetingModal" tabindex="-1" aria-labelledby="scheduleMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: var(--border-radius); border: 1px solid var(--border-color); color:var(--text-dark); background-color: var(--card-light);">
            <form action="schedule_meeting.php" method="POST">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title font-weight-700" id="scheduleMeetingModalLabel">Schedule Committee Meeting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Meeting Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Research Funding Assessment Panel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Agenda / Discussion Points</label>
                        <textarea name="agenda" class="form-control" rows="3" placeholder="Enter meeting agenda line by line..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-size-13 font-weight-600">Date & Time</label>
                            <input type="datetime-local" name="date_time" class="form-control" required value="<?php echo date('Y-m-d\TH:00', strtotime('+1 day')); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-size-13 font-weight-600">Committee Reference (Optional)</label>
                            <select name="committee_id" class="form-select">
                                <option value="">-- General Meeting --</option>
                                <?php foreach ($my_committees as $com): ?>
                                    <option value="<?php echo $com['id']; ?>"><?php echo sanitize($com['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Mock Video Conference Link</label>
                        <input type="url" name="room_link" class="form-control" placeholder="https://meet.google.com/xyz-pqrs-uvw" value="https://meet.google.com/abc-defg-hij">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Invite Participants</label>
                        <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                            <div class="row">
                                <?php foreach ($all_users as $au): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="participants[]" value="<?php echo $au['id']; ?>" id="user_check_<?php echo $au['id']; ?>">
                                            <label class="form-check-label font-size-13" for="user_check_<?php echo $au['id']; ?>">
                                                <strong><?php echo sanitize($au['name']); ?></strong> <span class="text-muted text-capitalize">(<?php echo $au['role']; ?>)</span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary py-2 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary py-2 px-4" style="background-color: var(--primary-color); border-color: var(--primary-color);">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ==================================================================
     MOM LOGGING EDITOR MODAL
     ================================================================== -->
<div class="modal fade" id="logMOMModal" tabindex="-1" aria-labelledby="logMOMModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: var(--border-radius); border: 1px solid var(--border-color); color:var(--text-dark); background-color: var(--card-light);">
            <form action="meetings.php" method="POST">
                <input type="hidden" name="action" value="save_mom">
                <input type="hidden" name="meeting_id" id="mom_meeting_id">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title font-weight-700">Write Minutes of Meeting (MOM)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Meeting Notes & Resolutions</label>
                        <textarea name="mom_text" id="mom_text" class="form-control" rows="8" placeholder="Record resolutions, attendee absences, and follow-up items here..." required></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="publish" value="1" id="publishMOM">
                        <label class="form-check-label font-size-13 font-weight-600" for="publishMOM">
                            Publish MOM instantly (notify all participants)
                        </label>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary py-2 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary py-2 px-4" style="background-color: var(--primary-color); border-color: var(--primary-color);">Save MOM</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================================================================
     MEETING DETAILS DIALOG MODAL
     ================================================================== -->
<div class="modal fade" id="meetingDetailsModal" tabindex="-1" aria-labelledby="meetingDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--border-radius); border: 1px solid var(--border-color); color:var(--text-dark); background-color: var(--card-light);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title font-weight-700" id="details_title">Meeting Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body font-size-14" style="font-size: 14px;">
                <p id="details_desc" class="text-muted"></p>
                <div class="mb-3">
                    <strong>Date & Time:</strong> <span id="details_date"></span>
                </div>
                <div class="mb-3">
                    <strong>Agenda:</strong>
                    <div class="bg-light p-3 border rounded text-dark mt-1" style="white-space: pre-wrap;" id="details_agenda"></div>
                </div>
                <div id="mom_container" class="d-none mb-3">
                    <strong>Minutes of Meeting (MOM):</strong>
                    <div class="bg-light p-3 border rounded border-warning text-dark mt-1" style="white-space: pre-wrap;" id="details_mom"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary py-2 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Open MOM Logging Popup
    function openMOMEditor(meetingId, currentMOM) {
        document.getElementById('mom_meeting_id').value = meetingId;
        document.getElementById('mom_text').value = currentMOM ? currentMOM : '';
        const modal = new bootstrap.Modal(document.getElementById('logMOMModal'));
        modal.show();
    }

    // Show Details Modal
    function showMeetingDetails(meeting) {
        document.getElementById('details_title').innerText = meeting.title;
        document.getElementById('details_desc').innerText = meeting.description ? meeting.description : "No description provided.";
        
        // Format Date
        let date = new Date(meeting.date_time);
        document.getElementById('details_date').innerText = date.toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
        
        document.getElementById('details_agenda').innerText = meeting.agenda ? meeting.agenda : "No agenda recorded.";
        
        const momContainer = document.getElementById('mom_container');
        if (meeting.mom && meeting.mom_published == 1) {
            momContainer.classList.remove('d-none');
            document.getElementById('details_mom').innerText = meeting.mom;
        } else {
            momContainer.classList.add('d-none');
        }
        
        const modal = new bootstrap.Modal(document.getElementById('meetingDetailsModal'));
        modal.show();
    }
</script>

<?php include 'footer.php'; ?>
