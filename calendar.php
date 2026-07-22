<?php
// calendar.php - Schedule and Event Calendar
require_once 'config.php';
include 'header.php';
?>

<div class="container-fluid p-0">
    <div class="page-title">
        <div>
            <h1 class="m-0 font-weight-800" style="font-size: 28px;">Academic & Meeting Calendar</h1>
            <p class="text-muted font-size-14 mb-0" style="font-size: 14px;">Track upcoming events, deadlines, tasks and department meetings.</p>
        </div>
    </div>

    <!-- Calendar Card Container -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="full-calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap & jQuery dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('full-calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            themeSystem: 'standard',
            events: 'get_calendar_events.php', // Fetches JSON events
            eventClick: function(info) {
                // Determine whether it's a meeting or a task
                if (info.event.extendedProps.type === 'meeting') {
                    // Redirect to meetings page or show modal
                    window.location.href = 'meetings.php';
                } else {
                    // Redirect to tasks page
                    window.location.href = 'tasks.php';
                }
            }
        });
        calendar.render();
    });
</script>

<?php include 'footer.php'; ?>
