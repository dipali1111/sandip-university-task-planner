-- MySQL database schema for Sandip University Task Planner & Meeting Scheduler
CREATE DATABASE IF NOT EXISTS sandip_planner;
USE sandip_planner;

-- 1. Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL, -- admin, registrar, dean, faculty, coordinator
    department_id INT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Committees Table
CREATE TABLE IF NOT EXISTS committees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    coordinator_id INT,
    FOREIGN KEY (coordinator_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Committee Members Junction Table
CREATE TABLE IF NOT EXISTS committee_members (
    committee_id INT,
    user_id INT,
    PRIMARY KEY (committee_id, user_id),
    FOREIGN KEY (committee_id) REFERENCES committees(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tasks Table
CREATE TABLE IF NOT EXISTS tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    priority VARCHAR(20) NOT NULL, -- high, medium, low
    status VARCHAR(20) NOT NULL DEFAULT 'todo', -- todo, in_progress, review, completed
    assigner_id INT NOT NULL,
    assignee_id INT NOT NULL,
    due_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Meetings Table
CREATE TABLE IF NOT EXISTS meetings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    date_time DATETIME NOT NULL,
    room_link VARCHAR(255),
    agenda TEXT,
    creator_id INT NOT NULL,
    committee_id INT,
    mom TEXT, -- Minutes of Meeting text
    mom_published TINYINT DEFAULT 0,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (committee_id) REFERENCES committees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Meeting Participants Junction Table
CREATE TABLE IF NOT EXISTS meeting_participants (
    meeting_id INT,
    user_id INT,
    PRIMARY KEY (meeting_id, user_id),
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Documents Table
CREATE TABLE IF NOT EXISTS documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    version INT DEFAULT 1,
    task_id INT,
    meeting_id INT,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Notifications / Alerts Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. System Announcements / Notices
CREATE TABLE IF NOT EXISTS announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_by INT NOT NULL,
    date_posted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Help Desk Tickets Table
CREATE TABLE IF NOT EXISTS help_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'open', -- open, closed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ================================================
-- SEED DATA
-- ================================================

-- Seed Departments
INSERT INTO departments (id, name) VALUES 
(1, 'School of Computer Science & Engineering'),
(2, 'School of Electrical & Electronics Engineering'),
(3, 'School of Mechanical Engineering'),
(4, 'School of Commerce & Management'),
(5, 'Central Administration');

-- Seed Users (Bcrypt hash of 'password123' is '$2y$10$mC/0dGZ/jX9K.pS8EwP8E.T2YvMhA8wYwUeS7p6rJ6aR4wQ1K9S4i')
INSERT INTO users (id, name, email, password, role, department_id) VALUES
(1, 'Dr. Amit Patel', 'admin@sandip.edu.in', '$2y$10$mC/0dGZ/jX9K.pS8EwP8E.T2YvMhA8wYwUeS7p6rJ6aR4wQ1K9S4i', 'admin', 5),
(2, 'Prof. Rajendra Prasad', 'registrar@sandip.edu.in', '$2y$10$mC/0dGZ/jX9K.pS8EwP8E.T2YvMhA8wYwUeS7p6rJ6aR4wQ1K9S4i', 'registrar', 5),
(3, 'Dr. Sandeep Sharma', 'dean@sandip.edu.in', '$2y$10$mC/0dGZ/jX9K.pS8EwP8E.T2YvMhA8wYwUeS7p6rJ6aR4wQ1K9S4i', 'dean', 1),
(4, 'Prof. Neha Gupta', 'faculty@sandip.edu.in', '$2y$10$mC/0dGZ/jX9K.pS8EwP8E.T2YvMhA8wYwUeS7p6rJ6aR4wQ1K9S4i', 'faculty', 1),
(5, 'Mr. Vicky Verma', 'coordinator@sandip.edu.in', '$2y$10$mC/0dGZ/jX9K.pS8EwP8E.T2YvMhA8wYwUeS7p6rJ6aR4wQ1K9S4i', 'coordinator', 1),
(6, 'Dr. Rahul Mehta', 'hod.ece@sandip.edu.in', '$2y$10$mC/0dGZ/jX9K.pS8EwP8E.T2YvMhA8wYwUeS7p6rJ6aR4wQ1K9S4i', 'dean', 2),
(7, 'Prof. Anil Kumar', 'faculty.ece@sandip.edu.in', '$2y$10$mC/0dGZ/jX9K.pS8EwP8E.T2YvMhA8wYwUeS7p6rJ6aR4wQ1K9S4i', 'faculty', 2);

-- Seed Committees
INSERT INTO committees (id, name, coordinator_id) VALUES
(1, 'Research & Development Committee', 5),
(2, 'Exam Coordination Committee', 5),
(3, 'Internal Quality Assurance Cell (IQAC)', 1);

-- Seed Committee Members
INSERT INTO committee_members (committee_id, user_id) VALUES
(1, 3), (1, 4), (1, 5), (1, 6),
(2, 4), (2, 5), (2, 7),
(3, 1), (3, 2), (3, 3), (3, 6);

-- Seed System Announcements
INSERT INTO announcements (id, title, content, created_by, date_posted) VALUES
(1, 'Admissions Open 2026-27', 'Applications are open for undergraduate and postgraduate engineering courses. Refer to university portal.', 1, '2026-07-01 10:00:00'),
(2, 'Syllabus Revision Notice', 'HODs and coordinators are requested to prepare recommendations for the upcoming Board of Studies meeting.', 3, '2026-07-15 14:30:00'),
(3, 'Annual Convocation 2026 Schedule', 'The 10th Convocation will be held on August 20th. Registrations for degrees are open now.', 2, '2026-07-20 09:00:00');

-- Seed Tasks
INSERT INTO tasks (id, title, description, priority, status, assigner_id, assignee_id, due_date) VALUES
(1, 'Syllabus Review CSE 2026', 'Review and update the syllabus for B.Tech Computer Science & Engineering 3rd year.', 'high', 'in_progress', 3, 4, '2026-07-30'),
(2, 'Mid-Term Exam Scheduling', 'Draft and submit the timetable for CSE mid-term examinations.', 'medium', 'todo', 3, 5, '2026-08-05'),
(3, 'Lab Equipment Audit', 'Audit electrical lab equipment and submit a list of required upgrades.', 'low', 'completed', 6, 7, '2026-07-18'),
(4, 'Accreditation Documentation Draft', 'Compile the SSR criteria 4 reports for final registrar validation.', 'high', 'todo', 3, 4, '2026-08-15');

-- Seed Meetings
INSERT INTO meetings (id, title, description, date_time, room_link, agenda, creator_id, committee_id) VALUES
(1, 'R&D Funding Review', 'Discuss current project proposals and seed funding allocations.', '2026-07-25 11:00:00', 'https://meet.google.com/abc-defg-hij', '1. Welcome address\n2. Review of project proposals\n3. Funding allocation decisions', 3, 1),
(2, 'IQAC Quality Assurance Meeting', 'Review quality indicators for university accreditation.', '2026-07-28 15:00:00', 'https://meet.google.com/xyz-pqrs-uvw', '1. Progress reports review\n2. Documentation gaps\n3. Action plan for NAAC', 1, 3);

-- Seed Meeting Participants
INSERT INTO meeting_participants (meeting_id, user_id) VALUES
(1, 3), (1, 4), (1, 5), (1, 6),
(2, 1), (2, 2), (2, 3), (2, 6);

-- Seed Alerts / Notifications
INSERT INTO notifications (id, user_id, message, is_read) VALUES
(1, 4, 'You have been assigned a new task: Syllabus Review CSE 2026', 0),
(2, 4, 'New Meeting Scheduled: R&D Funding Review on 2026-07-25', 0),
(3, 3, 'Task completed: Lab Equipment Audit by Prof. Anil Kumar', 0);
