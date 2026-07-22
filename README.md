# Sandip University Internal Task Planner & Meeting Scheduler

A modern, responsive, and secure web application for university academic planning, task tracking, meeting scheduling, and committee management. Built using HTML5, CSS3 (Bootstrap 5 + premium overrides), JavaScript, PHP, and MySQL.

---

## 📂 Project Directory Structure

```
sandip-university-planner/
│
├── database.sql           # Database schema & seed data
├── config.php             # PDO database connection & session security wrapper
├── README.md              # Project documentation (this file)
│
# Auth Pages
├── login.php              # Handles login and quick role switching
├── logout.php             # Cleans session cookies
│
# Layout Templates
├── header.php             # Navbar search, profile settings, and notification bell
├── sidebar.php            # Left navigation with conditional paths
├── footer.php             # Copyright info, IT Help Desk, and Privacy modals
│
# System Modules
├── index.php              # Public Landing page (Notice board, Calendar, Login panel)
├── dashboard.php          # Dynamic dashboards mapped by user role
├── tasks.php              # Task assignment lists and Kanban boards
├── meetings.php           # Meeting scheduling logs and Coordinator MOM builders
├── calendar.php           # Academic & Meeting Calendar powered by FullCalendar
├── alerts.php             # Log of notification messages
├── documents.php          # Uploaded file registry with versioning
├── departments.php        # Department lists, committee structures, and rosters
├── settings.php           # Profile fields, visual dark-theme toggler, and notice updates
│
# Action Handlers & Interfaces
├── create_task.php        # Form handler for creating tasks
├── schedule_meeting.php   # Form handler for creating meetings & notifications
├── get_calendar_events.php# JSON endpoint feeding FullCalendar events
├── upload_document.php    # Handles file upload validation and version increments
├── help_ticket.php        # Inserts IT support ticket records
├── room.php               # Simulated full-screen online meeting conference room
│
# Static Assets
├── css/
│   └── style.css          # Premium Custom styling (Deep Blue/Gold theme variables)
└── uploads/
    └── .gitkeep           # Placeholder preserving upload destination folder
```

---

## 🛠️ Installation & Setup (Deployment Guide)

To deploy this application on a local development server (like **XAMPP** or **WAMP**):

### 1. File Deployment
Copy the entire `sandip-university-planner` folder into your server's public root directory:
* **XAMPP**: `C:\xampp\htdocs\`
* **WampServer**: `C:\wamp64\www\`

### 2. Database Import
1. Run Apache and MySQL in your server control panel.
2. Open your browser and navigate to phpMyAdmin: `http://localhost/phpmyadmin/`.
3. Create a new database named `sandip_planner`.
4. Click on the `sandip_planner` database, select the **Import** tab at the top.
5. Choose the `database.sql` file from the project directory and click **Import** (or **Go**).
6. *Note: If your local database password differs from the standard XAMPP default (user: `root`, empty password), open `config.php` and edit the `$db_user` and `$db_pass` variables.*

### 3. Open Portal
Access the site by navigating to:
`http://localhost/sandip-university-planner/index.php`

---

## 🔐 Demo Credentials

All test accounts use the password **`password123`**:

* **Administrator**: `admin@sandip.edu.in`
* **Registrar / VC**: `registrar@sandip.edu.in`
* **Dean / HOD**: `dean@sandip.edu.in`
* **Faculty / Staff**: `faculty@sandip.edu.in`
* **Coordinator**: `coordinator@sandip.edu.in`

*(You can also use the **Demo Switcher Bar** at the bottom of the screens to jump between roles instantly without logging out).*

---

## 🛡️ Security Compliance & Features

1. **SQL Injection Security**: Implements strict PDO prepared statements for database queries to prevent SQL injections.
2. **XSS Protection**: Uses HTML entity escaping on all user-submitted text fields before rendering.
3. **Session Authentication**: Restricts unauthorized routing. Unlogged attempts redirect to the landing page.
4. **Upload Sandbox**: Validates file size (max 5MB) and whitelist extensions (`pdf`, `docx`, `xlsx`, `png`, `jpg`, `csv`, `txt`).
