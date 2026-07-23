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
        var holidayEvents = [
            { title: 'Holiday: Republic Day', start: '2026-01-26', allDay: true, color: '#dc3545', classNames: ['calendar-holiday'], extendedProps: { type: 'holiday' } },
            { title: 'Holiday: Maharashtra Day', start: '2026-05-01', allDay: true, color: '#dc3545', classNames: ['calendar-holiday'], extendedProps: { type: 'holiday' } },
            { title: 'Holiday: Independence Day', start: '2026-08-15', allDay: true, color: '#dc3545', classNames: ['calendar-holiday'], extendedProps: { type: 'holiday' } },
            { title: 'Holiday: Gandhi Jayanti', start: '2026-10-02', allDay: true, color: '#dc3545', classNames: ['calendar-holiday'], extendedProps: { type: 'holiday' } },
            { title: 'Holiday: Christmas Day', start: '2026-12-25', allDay: true, color: '#dc3545', classNames: ['calendar-holiday'], extendedProps: { type: 'holiday' } }
        ];
        var holidayDates = holidayEvents.map(function(event) { return event.start; });
        var formatCalendarDate = function(date) {
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return date.getFullYear() + '-' + month + '-' + day;
        };
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            themeSystem: 'standard',
            eventSources: [
                'get_calendar_events.php',
                holidayEvents
            ],
            dayCellClassNames: function(info) {
                var classes = [];
                if (info.date.getDay() === 0) {
                    classes.push('calendar-sunday');
                }
                if (holidayDates.indexOf(formatCalendarDate(info.date)) !== -1) {
                    classes.push('calendar-holiday-day');
                }
                return classes;
            },
            eventClick: function(info) {
                if (info.event.extendedProps.type === 'holiday') {
                    return;
                }
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
