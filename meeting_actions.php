<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Action Request Handler: meeting_actions.php
 * Handles meeting scheduling, status changes, agendas, files, attendance, and decisions
 */

require_once __DIR__ . '/config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_REQUEST['action'] ?? '';

// Ensure user is authenticated for all backend actions
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$is_admin = is_admin_user();

/* ----------------------------------------------------------------------------
   ACTION: SCHEDULE NEW MEETING (HOD / Management Assistant Only)
   ---------------------------------------------------------------------------- */
if ($action === 'schedule_meeting' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        die("Permission denied: Only HOD or Management Assistant can schedule meetings.");
    }

    $type_id = (int)($_POST['type_id'] ?? 0);
    $date = sanitize_input($_POST['date'] ?? '');
    $time = sanitize_input($_POST['time'] ?? '');
    $location = sanitize_input($_POST['location'] ?? 'Department Boardroom');
    $agenda_details = trim($_POST['agenda_details'] ?? '');

    if ($type_id <= 0 || empty($date) || empty($time)) {
        header("Location: schedule_meeting.php?error=Please+fill+in+all+required+fields");
        exit();
    }

    // Insert scheduled meeting using Prepared Statement
    $stmt = $conn->prepare("INSERT INTO Scheduled_Meeting (type_id, date, time, location, status) VALUES (?, ?, ?, ?, 'Scheduled')");
    $stmt->bind_param("isss", $type_id, $date, $time, $location);

    if ($stmt->execute()) {
        $meeting_id = $stmt->insert_id;
        $stmt->close();

        // Save Agenda if entered
        if (!empty($agenda_details)) {
            $ag_stmt = $conn->prepare("INSERT INTO Agenda (meeting_id, agenda_details) VALUES (?, ?)");
            $ag_stmt->bind_param("is", $meeting_id, $agenda_details);
            $ag_stmt->execute();
            $ag_stmt->close();
        }

        // Handle File Upload if attached
        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['material_file']['tmp_name'];
            $orig_name = basename($_FILES['material_file']['name']);
            $file_ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

            // Allowed extensions security check
            $allowed_exts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'png', 'jpg'];
            if (in_array($file_ext, $allowed_exts)) {
                $safe_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig_name);
                $upload_dir = __DIR__ . '/uploads/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $target_path = $upload_dir . $safe_name;
                if (move_uploaded_file($file_tmp, $target_path)) {
                    $db_path = 'uploads/' . $safe_name;
                    $f_stmt = $conn->prepare("INSERT INTO Meeting_Materials (meeting_id, file_name, file_path) VALUES (?, ?, ?)");
                    $f_stmt->bind_param("iss", $meeting_id, $orig_name, $db_path);
                    $f_stmt->execute();
                    $f_stmt->close();
                }
            }
        }

        // Initialize Attendance records for all staff members (default 'Absent')
        $staff_res = $conn->query("SELECT staff_id FROM Staff");
        if ($staff_res) {
            $att_stmt = $conn->prepare("INSERT IGNORE INTO Attendance (meeting_id, staff_id, status) VALUES (?, ?, 'Absent')");
            while ($s_row = $staff_res->fetch_assoc()) {
                $sid = $s_row['staff_id'];
                $att_stmt->bind_param("ii", $meeting_id, $sid);
                $att_stmt->execute();
            }
            $att_stmt->close();
        }

        // Get Meeting Type Name for notification
        $t_res = $conn->query("SELECT type_name FROM Meeting_Type WHERE type_id = " . (int)$type_id);
        $type_name = "Department Meeting";
        if ($t_res && $t_row = $t_res->fetch_assoc()) {
            $type_name = $t_row['type_name'];
        }

        // Broadcast notification to all staff members (FR-05)
        $notif_msg = "New meeting scheduled: {$type_name} on {$date} at " . date('g:i A', strtotime($time)) . " in {$location}.";
        broadcast_notification($conn, $notif_msg);

        header("Location: meetings.php?msg=Meeting+scheduled+successfully");
        exit();
    } else {
        header("Location: schedule_meeting.php?error=Failed+to+schedule+meeting:+" . urlencode($conn->error));
        exit();
    }
}

/* ----------------------------------------------------------------------------
   ACTION: UPDATE MEETING STATUS (Scheduled, Completed, Cancelled, Rescheduled)
   ---------------------------------------------------------------------------- */
if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        die("Permission denied.");
    }

    $meeting_id = (int)($_POST['meeting_id'] ?? 0);
    $status = sanitize_input($_POST['status'] ?? '');

    $allowed_statuses = ['Scheduled', 'Completed', 'Cancelled', 'Rescheduled'];
    if ($meeting_id > 0 && in_array($status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE Scheduled_Meeting SET status = ? WHERE meeting_id = ?");
        $stmt->bind_param("si", $status, $meeting_id);
        $stmt->execute();
        $stmt->close();

        // Broadcast notification if cancelled or rescheduled
        if (in_array($status, ['Cancelled', 'Rescheduled'])) {
            $msg = "Meeting notice: Session #{$meeting_id} has been marked as {$status}.";
            broadcast_notification($conn, $msg);
        }

        header("Location: meeting_details.php?id={$meeting_id}&msg=Meeting+status+updated+to+" . urlencode($status));
        exit();
    }
    header("Location: meetings.php?error=Invalid+status+update");
    exit();
}

/* ----------------------------------------------------------------------------
   ACTION: SAVE OR UPDATE AGENDA
   ---------------------------------------------------------------------------- */
if ($action === 'save_agenda' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        die("Permission denied.");
    }

    $meeting_id = (int)($_POST['meeting_id'] ?? 0);
    $agenda_details = trim($_POST['agenda_details'] ?? '');

    if ($meeting_id > 0) {
        $check = $conn->prepare("SELECT agenda_id FROM Agenda WHERE meeting_id = ?");
        $check->bind_param("i", $meeting_id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE Agenda SET agenda_details = ? WHERE meeting_id = ?");
            $stmt->bind_param("si", $agenda_details, $meeting_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO Agenda (agenda_details, meeting_id) VALUES (?, ?)");
            $stmt->bind_param("si", $agenda_details, $meeting_id);
        }
        $stmt->execute();
        $stmt->close();
        $check->close();

        // Return to meeting details if coming from details page
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'meeting_details.php') !== false) {
            header("Location: meeting_details.php?id={$meeting_id}&msg=Agenda+saved+successfully");
        } else {
            header("Location: documents.php?tab=agendas&msg=Agenda+saved+successfully");
        }
        exit();
    }
    header("Location: documents.php?error=Failed+to+save+agenda");
    exit();
}

/* ----------------------------------------------------------------------------
   ACTION: SAVE DECISIONS / MINUTES OF MEETING
   ---------------------------------------------------------------------------- */
if ($action === 'save_decisions' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        die("Permission denied.");
    }

    $meeting_id = (int)($_POST['meeting_id'] ?? 0);
    $decisions = trim($_POST['decisions'] ?? '');

    if ($meeting_id > 0) {
        $check = $conn->prepare("SELECT report_id FROM Decision_Report WHERE meeting_id = ?");
        $check->bind_param("i", $meeting_id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE Decision_Report SET decisions = ? WHERE meeting_id = ?");
            $stmt->bind_param("si", $decisions, $meeting_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO Decision_Report (decisions, meeting_id) VALUES (?, ?)");
            $stmt->bind_param("si", $decisions, $meeting_id);
        }
        $stmt->execute();
        $stmt->close();
        $check->close();

        // Automatically set meeting status to Completed
        $upd = $conn->prepare("UPDATE Scheduled_Meeting SET status = 'Completed' WHERE meeting_id = ?");
        $upd->bind_param("i", $meeting_id);
        $upd->execute();
        $upd->close();

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'meeting_details.php') !== false) {
            header("Location: meeting_details.php?id={$meeting_id}&msg=Decisions+saved+and+meeting+marked+Completed");
        } else {
            header("Location: documents.php?tab=decisions&msg=Decisions+saved+and+meeting+marked+Completed");
        }
        exit();
    }
    header("Location: documents.php?error=Failed+to+save+decisions");
    exit();
}

/* ----------------------------------------------------------------------------
   ACTION: UPLOAD MEETING MATERIAL / DOCUMENT
   ---------------------------------------------------------------------------- */
if ($action === 'upload_material' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        die("Permission denied.");
    }

    $meeting_id = (int)($_POST['meeting_id'] ?? 0);

    if ($meeting_id > 0 && isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['document_file']['tmp_name'];
        $orig_name = basename($_FILES['document_file']['name']);
        $file_ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        $allowed_exts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'png', 'jpg'];
        if (in_array($file_ext, $allowed_exts)) {
            $safe_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig_name);
            $upload_dir = __DIR__ . '/uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $target = $upload_dir . $safe_name;
            if (move_uploaded_file($file_tmp, $target)) {
                $relative_path = 'uploads/' . $safe_name;
                $stmt = $conn->prepare("INSERT INTO Meeting_Materials (meeting_id, file_name, file_path) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $meeting_id, $orig_name, $relative_path);
                $stmt->execute();
                $stmt->close();

                $referer = $_SERVER['HTTP_REFERER'] ?? '';
                if (strpos($referer, 'meeting_details.php') !== false) {
                    header("Location: meeting_details.php?id={$meeting_id}&msg=Document+uploaded+successfully");
                } else {
                    header("Location: documents.php?tab=materials&msg=Document+uploaded+successfully");
                }
                exit();
            }
        } else {
            header("Location: documents.php?error=Invalid+file+type.+Allowed:+PDF,+DOCX,+PPTX,+XLSX,+TXT");
            exit();
        }
    }
    header("Location: documents.php?error=File+upload+failed");
    exit();
}

/* ----------------------------------------------------------------------------
   ACTION: RECORD / UPDATE ATTENDANCE
   ---------------------------------------------------------------------------- */
if ($action === 'save_attendance' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        die("Permission denied.");
    }

    $meeting_id = (int)($_POST['meeting_id'] ?? 0);
    $attendance_data = $_POST['attendance'] ?? []; // Array [staff_id => status]

    if ($meeting_id > 0 && !empty($attendance_data)) {
        $stmt = $conn->prepare("
            INSERT INTO Attendance (meeting_id, staff_id, status) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");

        foreach ($attendance_data as $staff_id => $status) {
            $sid = (int)$staff_id;
            $st = in_array($status, ['Present', 'Absent', 'Excused']) ? $status : 'Absent';
            $stmt->bind_param("iis", $meeting_id, $sid, $st);
            $stmt->execute();
        }
        $stmt->close();

        header("Location: attendance.php?meeting_id={$meeting_id}&msg=Attendance+records+saved+successfully");
        exit();
    }
    header("Location: attendance.php?meeting_id={$meeting_id}&error=Could+not+save+attendance");
    exit();
}

/* ----------------------------------------------------------------------------
   ACTION: MARK NOTIFICATIONS AS READ
   ---------------------------------------------------------------------------- */
if ($action === 'mark_notifs_read') {
    $stmt = $conn->prepare("UPDATE Notification SET is_read = 1 WHERE staff_id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: notifications.php?msg=All+notifications+marked+as+read");
    exit();
}

// Fallback redirect
header("Location: index.php");
exit();
?>
