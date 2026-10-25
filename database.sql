

CREATE DATABASE IF NOT EXISTS `dcms_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dcms_db`;

-- Drop tables if already existing (in reverse dependency order)
DROP TABLE IF EXISTS `Meeting_Materials`;
DROP TABLE IF EXISTS `Notification`;
DROP TABLE IF EXISTS `Decision_Report`;
DROP TABLE IF EXISTS `Attendance`;
DROP TABLE IF EXISTS `Agenda`;
DROP TABLE IF EXISTS `Scheduled_Meeting`;
DROP TABLE IF EXISTS `Meeting_Type`;
DROP TABLE IF EXISTS `Staff`;




CREATE TABLE `Staff` (
    `staff_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `username` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL, -- Plain text per project specification
    `role` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE `Meeting_Type` (
    `type_id` INT AUTO_INCREMENT PRIMARY KEY,
    `type_name` VARCHAR(100) NOT NULL,
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `Scheduled_Meeting` (
    `meeting_id` INT AUTO_INCREMENT PRIMARY KEY,
    `type_id` INT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `location` VARCHAR(100) DEFAULT 'Department Boardroom',
    `status` VARCHAR(20) DEFAULT 'Scheduled', -- 'Scheduled', 'Completed', 'Cancelled', 'Rescheduled'
    CONSTRAINT `fk_meeting_type` FOREIGN KEY (`type_id`) 
        REFERENCES `Meeting_Type`(`type_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `Agenda` (
    `agenda_id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` INT NOT NULL,
    `agenda_details` TEXT NOT NULL,
    CONSTRAINT `fk_agenda_meeting` FOREIGN KEY (`meeting_id`) 
        REFERENCES `Scheduled_Meeting`(`meeting_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `Attendance` (
    `attendance_id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` INT NOT NULL,
    `staff_id` INT NOT NULL,
    `status` VARCHAR(20) DEFAULT 'Absent', -- 'Present', 'Absent', 'Excused'
    CONSTRAINT `fk_attendance_meeting` FOREIGN KEY (`meeting_id`) 
        REFERENCES `Scheduled_Meeting`(`meeting_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_attendance_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `Staff`(`staff_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `unique_meeting_staff` (`meeting_id`, `staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `Decision_Report` (
    `report_id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` INT NOT NULL,
    `decisions` TEXT NOT NULL,
    CONSTRAINT `fk_decision_meeting` FOREIGN KEY (`meeting_id`) 
        REFERENCES `Scheduled_Meeting`(`meeting_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `Notification` (
    `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
    `staff_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `date_sent` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notification_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `Staff`(`staff_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `Meeting_Materials` (
    `material_id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_material_meeting` FOREIGN KEY (`meeting_id`) 
        REFERENCES `Scheduled_Meeting`(`meeting_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO `Staff` (`staff_id`, `name`, `email`, `username`, `password`, `role`) VALUES
(1, 'Dr. Head of Department', 'hod@example.com', 'hod', 'Hod@123456', 'HOD'),
(2, 'Mr. Lecturer One', 'lec1@example.com', 'lec1', 'Lec1@12356', 'Lecturer'),
(3, 'Ms. Lecturer Two', 'lec2@example.com', 'lec2', 'Lec2@12356', 'Lecturer'),
(4, 'Management Assistant One', 'MA1@example.com', 'MA1', 'Assist1@123', 'Management Assistant'),
(5, 'Dr. Senior Lecturer One', 'senior.lecturer1@example.com', 'senior.lecturer1', 'SenLec1@123', 'Senior Lecturer'),
(6, 'Demonstrator One', 'demonstrator1@example.com', 'demonstrator1', 'Demo1@1234', 'Demonstrator'),
(7, 'Technical Staff One', 'technical1@example.com', 'technical1', 'Tech1@1234', 'Technical Staff'),
(8, 'Student Coordinator One', 'student.coordinator1@example.com', 'student.coordinator1', 'Coord1@123', 'Student Coordinator');


INSERT INTO `Meeting_Type` (`type_id`, `type_name`, `description`) VALUES
(1, 'Department Academic Staff Meeting', 'Monthly department review of academic progress, course assignments, and departmental operations.'),
(2, 'Curriculum Development & Review Committee Meeting', 'Per-semester review and modernization of computing degree curricula, syllabus, and course modules.'),
(3, 'Department Quality Assurance Cell (DQAC) Meeting', 'Bi-monthly internal quality audits, peer reviews, accreditation criteria, and student feedback reviews.'),
(4, 'Board of Examiners Pre-Evaluation Meeting', 'Exam-linked meeting for examination paper setting, moderation scheduling, and invigilation planning.'),
(5, 'Results Scrutiny & Approval Board Meeting', 'Exam-linked formal scrutiny of continuous assessments, final exam marks, and grade confirmations.'),
(6, 'Industry Consultative Board (ICB) Meeting', 'Annual collaborative session with ICT industry leaders to align degree programs with market needs.'),
(7, 'Department Research & Higher Degrees Committee (DRC)', 'Bi-monthly evaluation of faculty research grants, research papers, and postgraduate student progress.'),
(8, 'Academic Mentoring & Student Welfare Meeting', 'Monthly assessment of student academic difficulties, mentoring allocations, and welfare requests.'),
(9, 'Laboratory & Infrastructure Development Committee', 'Per-semester planning for computing labs, server infrastructure, hardware maintenance, and software licenses.'),
(10, 'Staff Development & Workshop Planning Committee', 'Per-semester organization of faculty training, academic pedagogical workshops, and skill-building.'),
(11, 'Department Sports & Cultural Committee Meeting', 'Annual planning for student hackathons, coding contests, faculty sports meet, and cultural events.'),
(12, 'Student Representatives Liaison Meeting', 'Bi-monthly open forum between department faculty and elected student batch representatives.'),
(13, 'Annual Department Strategic & Budget Review Meeting', 'Annual strategic planning, KPI evaluations, capital equipment budget, and resource allocations.');

