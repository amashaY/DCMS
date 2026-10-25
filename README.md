# Department Communication Management System
### Faculty of Applied Sciences, Rajarata University of Sri Lanka
**Course:** ICT 1407 – Database Systems Problem-based Assignment  
**Project Group:** `TEAM_214.0`

---

## 👥 Project Team Members

| Name | Student Registration No. | Role / Contribution |
| :--- | :--- | :--- |
| **Lahiruni Cooray** | `TC1370` | Requirements Analysis & Documentation |
| **Shehan Geethadewa** | `SM3568` | Full-Stack Development & Database Integration |
| **Pasindu Dissanayake** | `PD654` | Conceptual & Relational Database Design |
| **Sadew Dewram** | `SD1736` | UI/UX Layout, Calendar & Front-End Styling |
| **Amasha Yashohara** | `AA4419` | System Testing, Normalization & Verification |

---

## 📖 Executive Summary & Background

In the Department of Computing at the Faculty of Applied Sciences, Rajarata University of Sri Lanka (RUSL), managing academic communication and annual meeting cycles historically relied on disconnected spreadsheets, manual email threads, and verbal notices.

The **Department Communication Management System** provides a streamlined, centralized web portal developed with clean **PHP (MySQLi with Prepared Statements)**, **HTML5**, and standard **CSS3**. It provides an annual monthly calendar, automated notifications, document management, role-based controls, attendance tracking, and historical summary reporting tailored specifically for a university department environment.

---

## 🎨 Role-Based Interface Color Schemes

To provide clear visual role awareness, the user interface dynamically adapts its color theme based on the logged-in staff member's position:

| Staff Role | Theme Color | Navigation & Accent Palette | Visual Purpose |
| :--- | :--- | :--- | :--- |
| **HOD (Head of Department)** | **Administrative Royal Blue** | Navbar `#1e3a8a`, Primary Action `#1d4ed8` | Authoritative executive control for meetings and decisions |
| **Management Assistant** | **Executive Administrative Teal** | Navbar `#115e59`, Primary Action `#0f766e` | Administrative data entry and operational support |
| **Lecturer** | **Academic Indigo** | Navbar `#312e81`, Primary Action `#4338ca` | Academic faculty session planning and agenda review |
| **Senior Lecturer** | **Prestige Deep Violet** | Navbar `#4c1d95`, Primary Action `#6d28d9` | Senior academic leadership and committee governance |
| **Demonstrator** | **Steel Blue / Slate** | Navbar `#1e293b`, Primary Action `#0369a1` | Laboratory and practical session support |
| **Technical Staff** | **Warm Charcoal / Amber** | Navbar `#1c1917`, Primary Action `#b45309` | Lab maintenance and infrastructure coordination |
| **Student Coordinator** | **Forest Emerald Green** | Navbar `#14532d`, Primary Action `#15803d` | Student liaison and batch coordination |

---

## 🔑 Pre-Seeded Default Staff Accounts (Credentials)

Per project guidelines, there is **no public registration form** and passwords are stored as specified. The database is pre-seeded with the exact **8 staff accounts** covering all required university roles:

| Staff Member Name | Email Address | Username | Password | Role / Access Level | Theme Applied |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **Dr. Head of Department** | `hod@example.com` | `hod` | `Hod@123456` | **HOD** | 🔵 Royal Blue |
| **Management Assistant One** | `MA1@example.com` | `MA1` | `Assist1@123` | **Management Assistant** | 🟢 Teal |
| **Mr. Lecturer One** | `lec1@example.com` | `lec1` | `Lec1@12356` | **Lecturer** | 🟣 Academic Indigo |
| **Ms. Lecturer Two** | `lec2@example.com` | `lec2` | `Lec2@12356` | **Lecturer** | 🟣 Academic Indigo |
| **Dr. Senior Lecturer One** | `senior.lecturer1@example.com` | `senior.lecturer1` | `SenLec1@123` | **Senior Lecturer** | 🟣 Deep Violet |
| **Demonstrator One** | `demonstrator1@example.com` | `demonstrator1` | `Demo1@1234` | **Demonstrator** | ⚪ Steel Slate |
| **Technical Staff One** | `technical1@example.com` | `technical1` | `Tech1@1234` | **Technical Staff** | 🟤 Warm Charcoal |
| **Student Coordinator One** | `student.coordinator1@example.com` | `student.coordinator1` | `Coord1@123` | **Student Coordinator** | 🟢 Emerald Green |

> **Note on Login:** You can log in using either the **Email Address** OR the **Username**, along with the corresponding password on `login.php`.

---

## 🏛️ The 13 Standing Department Meeting Types

The database schema includes the exact **13 recurring department meeting types** defined in the project proposal with their academic recurrence cycles:

| Type ID | Meeting Category Name | Recurrence Pattern | Academic Purpose / Description |
| :---: | :--- | :--- | :--- |
| **1** | **Department Academic Staff Meeting** | Monthly | Regular monthly review of academic progress, course assignments, and departmental operations. |
| **2** | **Curriculum Development & Review Committee Meeting** | Per-semester | Per-semester review and modernization of computing degree curricula, syllabus, and course modules. |
| **3** | **Department Quality Assurance Cell (DQAC) Meeting** | Bi-monthly | Internal quality audits, peer reviews, accreditation criteria, and student feedback reviews. |
| **4** | **Board of Examiners Pre-Evaluation Meeting** | Exam-linked | Examination paper setting, moderation scheduling, practical exams, and invigilation planning. |
| **5** | **Results Scrutiny & Approval Board Meeting** | Exam-linked | Formal scrutiny of continuous assessments, final exam marks, and grade confirmations. |
| **6** | **Industry Consultative Board (ICB) Meeting** | Annual | Collaborative session with ICT industry leaders to align degree programs with market needs. |
| **7** | **Department Research & Higher Degrees Committee (DRC)** | Bi-monthly | Evaluation of faculty research grants, research papers, and postgraduate student progress. |
| **8** | **Academic Mentoring & Student Welfare Meeting** | Monthly | Assessment of student academic difficulties, mentoring allocations, and welfare requests. |
| **9** | **Laboratory & Infrastructure Development Committee** | Per-semester | Planning for computing labs, server infrastructure, hardware maintenance, and software licenses. |
| **10** | **Staff Development & Workshop Planning Committee** | Per-semester | Organization of faculty training, academic pedagogical workshops, and technical skill-building. |
| **11** | **Department Sports & Cultural Committee Meeting** | Annual | Planning for student hackathons, coding contests, faculty sports meet, and cultural events. |
| **12** | **Student Representatives Liaison Meeting** | Bi-monthly | Open forum between department faculty and elected student batch representatives. |
| **13** | **Annual Department Strategic & Budget Review Meeting** | Annual | Strategic planning, KPI evaluations, capital equipment budget, and resource allocations. |

---

## 🚀 Step-by-Step Setup Guide

Follow these steps to run the application on your computer using **XAMPP** (or WampServer / MAMP):

### Step 1: Place Project Files into XAMPP Web Root
1. Copy the project folder into your XAMPP web root directory:
   ```
   C:\xampp\htdocs\DCMS_DB_0.3
   ```

### Step 2: Start Apache and MySQL Services
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

### Step 3: Import the Database Schema
1. Open your browser and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on the **Import** tab at the top.
3. Click **Choose File** and select `database.sql` from the project folder:
   ```
   C:\xampp\htdocs\DCMS_DB_0.3\database.sql
   ```
4. Click **Import** (or **Go**).
5. The database `dcms_db` will be created with all 8 relational tables, the 8 staff accounts, and the 13 meeting types.

### Step 4: Verify Database Connection Configuration
Open `config/db_connect.php` in any text editor:
```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'dcms_db';
```

### Step 5: Launch the Application in Browser
1. In your browser, open:
   ```
   http://localhost/DCMS_DB_0.3/login.php
   ```
2. Log in with any of the pre-seeded credentials (e.g., Username: `hod`, Password: `Hod@123456`).

---

## 🔍 Detailed Feature Overview

### 1. User Authentication & Role-Based Access Control (RBAC) (`login.php`, `includes/auth_check.php`)
- **University Crest Branding:** Login interface with the official university crest (`assets/images/crest.png`).
- **Session Security:** `auth_check.php` verifies active user session and role on every internal page.
- **Dynamic Theming:** The user's role sets the theme color across all pages.
- **Role Enforcement:**
  - **Administrative Roles (`HOD`, `Management Assistant`):** Full control to schedule meetings, update statuses, upload documents, enter agendas, record attendance, and log formal decisions.
  - **Academic & Other Staff (`Lecturer`, `Senior Lecturer`, `Demonstrator`, `Technical Staff`, `Student Coordinator`):** View meetings, calendars, agendas, minutes, download materials, and inspect personal attendance.

### 2. Department Executive Dashboard (`index.php`)
- **Key Metrics Overview:**
  - Total Department Meetings count.
  - Upcoming Sessions count (`date >= CURDATE()`).
  - Department Attendance Compliance Rate (`(Present / Total Records) * 100`).
  - Active Document Assets in repository.
- **Upcoming Meetings List:** Quick links to view meeting details and record attendance.
- **Recent Department Alerts:** Shows real-time automated notices with unread indicators.

### 3. Department Meeting Management (`meetings.php`, `schedule_meeting.php`, `meeting_details.php`)
- **Comprehensive Listing (`meetings.php`):** Displays all scheduled sessions with type, date, time, venue, agenda status, documents count, decision status, and status badge.
- **Multi-Parameter Search & Filtering:** Filter by Meeting Type, Status, or keyword.
- **Schedule New Meeting (`schedule_meeting.php`):** A clean, dedicated form for HOD and Management Assistant to plan sessions.
- **Meeting Session Details (`meeting_details.php`):** Complete relational page showing meeting metadata, agenda, decision report, uploaded files with download links, attendance summary, and administrative status update forms.

### 4. Monthly Meeting Calendar (`calendar.php`)
- **Pure PHP Calendar Engine:** Calculates and renders the month grid directly using PHP date functions and MySQL `MONTH(date)` / `YEAR(date)` queries.
- **Previous & Next Month Navigation:** Easy navigation through past and upcoming months.
- **Clickable Session Pills:** Click any meeting pill to open its full details page.

### 5. Document Repository (`documents.php`)
- **Three Organized Modules (Tabbed View):**
  1. **Attached Files:** Direct table of uploaded documents (PDF, DOCX, PPTX, XLSX) with download buttons and a simple upload form for admins.
  2. **Meeting Agendas:** Distributed agendas with an admin form to add or update agenda points.
  3. **Decision Logs & Minutes:** Central archive of ratified decisions with an admin form to log minutes.

### 6. Department Attendance Tracking (`attendance.php`)
- **Meeting Selector:** Dropdown to pick any meeting session and load its full attendance roster.
- **Roster Table:** Displays all 8 department staff members with radio buttons (**Present**, **Absent**, **Excused**).
- **Quick Action Helper:** "Mark All Present" and "Mark All Absent" buttons.
- **Personal Attendance History:** Displays personal past attendance records for the logged-in staff member.

### 7. Automated Notification System (`notifications.php`)
- **Automated Triggers:** Notifications are automatically broadcast to staff members whenever a meeting is scheduled, cancelled, or rescheduled.
- **Top Navigation Bell:** Shows an unread notification counter badge.
- **Notification Inbox:** Staff can review their alerts and mark all as read.

### 8. Historical Querying & Summary Reports (`reports.php`)
- **Historical Query Filters:** Filter past meetings by meeting category, attending staff member, status, and date range.
- **Staff Attendance Compliance Breakdown:** Summary table showing total sessions, present count, absent count, excused count, and compliance percentage for each staff member.
- **Export & Print:** One-click CSV export and print-friendly report stylesheet.

---

## 🗄️ Database Architecture & Relational Schema (`dcms_db`)

The database consists of 8 normalized relational tables enforcing referential integrity with foreign keys:

1. **`Staff`**: Stores staff ID, name, email, username, password, and role.
2. **`Meeting_Type`**: Contains the 13 standing university meeting types and descriptions.
3. **`Scheduled_Meeting`**: Stores meeting sessions with type ID, date, time, location, and status.
4. **`Agenda`**: Stores meeting agendas linked to `Scheduled_Meeting`.
5. **`Attendance`**: Tracks individual staff attendance (`Present`, `Absent`, `Excused`) per meeting session.
6. **`Decision_Report`**: Stores formal minutes and action items linked to `Scheduled_Meeting`.
7. **`Notification`**: Stores automated alerts for each staff member.
8. **`Meeting_Materials`**: Stores uploaded document file names and paths linked to meetings.

---

## 🛡️ Security & Integrity Highlights

- **SQL Injection Prevention:** Every query with user-supplied input strictly uses prepared statements (`$conn->prepare()`, `bind_param()`, `execute()`).
- **XSS Prevention:** All dynamic database values displayed in HTML are sanitized with `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
- **Session Authentication Guard:** `includes/auth_check.php` prevents unauthenticated access.
- **File Upload Security:** File extensions are strictly validated against an allowlist (`pdf`, `doc`, `docx`, `ppt`, `pptx`, `xls`, `xlsx`, `txt`) to prevent executing arbitrary scripts.
- **Simplicity & Authenticity:** Written in clean, readable PHP and standard CSS that can be easily understood and defended during coursework evaluation.

---
*Department of Computing, Faculty of Applied Sciences, Rajarata University of Sri Lanka.*
