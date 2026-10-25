<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Attendance Tracking & Management: attendance.php
 */

$page_title = 'Meeting Attendance';
require_once __DIR__ . '/includes/header.php';

$msg = $_GET['msg'] ?? '';
$err = $_GET['error'] ?? '';

$selected_meeting_id = (int)($_GET['meeting_id'] ?? 0);

// If no meeting selected, default to the most recent meeting
if ($selected_meeting_id <= 0) {
    $recent = $conn->query("SELECT meeting_id FROM Scheduled_Meeting ORDER BY date DESC, time DESC LIMIT 1");
    if ($recent && $r = $recent->fetch_assoc()) {
        $selected_meeting_id = (int)$r['meeting_id'];
    }
}

// Fetch all scheduled meetings for dropdown selector
$meetings_list = [];
$m_res = $conn->query("
    SELECT sm.meeting_id, sm.date, sm.time, sm.status, mt.type_name
    FROM Scheduled_Meeting sm
    LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
    ORDER BY sm.date DESC, sm.time DESC
");
if ($m_res) {
    while ($r = $m_res->fetch_assoc()) {
        $meetings_list[] = $r;
    }
}

// Fetch details for currently selected meeting
$selected_meeting = null;
if ($selected_meeting_id > 0) {
    $m_stmt = $conn->prepare("
        SELECT sm.meeting_id, sm.date, sm.time, sm.location, sm.status, mt.type_name
        FROM Scheduled_Meeting sm
        LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
        WHERE sm.meeting_id = ?
        LIMIT 1
    ");
    $m_stmt->bind_param("i", $selected_meeting_id);
    $m_stmt->execute();
    $selected_meeting = $m_stmt->get_result()->fetch_assoc();
    $m_stmt->close();
}

// Fetch staff attendance roster for this meeting
$roster = [];
if ($selected_meeting_id > 0) {
    // Left join Staff with Attendance to show every staff member even if no record yet
    $r_stmt = $conn->prepare("
        SELECT s.staff_id, s.name, s.role, s.email,
               COALESCE(a.status, 'Absent') AS attendance_status,
               a.attendance_id
        FROM Staff s
        LEFT JOIN Attendance a ON s.staff_id = a.staff_id AND a.meeting_id = ?
        ORDER BY s.staff_id ASC
    ");
    $r_stmt->bind_param("i", $selected_meeting_id);
    $r_stmt->execute();
    $r_res = $r_stmt->get_result();
    while ($row = $r_res->fetch_assoc()) {
        $roster[] = $row;
    }
    $r_stmt->close();
}
?>

<!-- Feedback Messages -->
<?php if (!empty($msg)): ?>
    <div class="alert alert-success">
        <span>✓ <?= htmlspecialchars($msg); ?></span>
    </div>
<?php endif; ?>
<?php if (!empty($err)): ?>
    <div class="alert alert-danger">
        <span>✕ <?= htmlspecialchars($err); ?></span>
    </div>
<?php endif; ?>

<div class="page-header">
    <div class="page-title">
        <h1>Department Meeting Attendance</h1>
        <p>Record, verify, and monitor staff attendance for academic and departmental sessions</p>
    </div>
    <div>
        <a href="reports.php" class="btn btn-secondary">📈 Attendance Summary Reports</a>
    </div>
</div>

<!-- Meeting Selector Bar -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding: 14px 18px;">
        <form action="attendance.php" method="GET" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <label for="meeting_id" style="font-weight: 600; font-size: 13px;">Select Meeting Session:</label>
            <select name="meeting_id" id="meeting_id" class="form-control" style="flex: 1; min-width: 280px;" onchange="this.form.submit()">
                <?php if (empty($meetings_list)): ?>
                    <option value="">No meetings scheduled yet</option>
                <?php else: ?>
                    <?php foreach ($meetings_list as $m): ?>
                        <option value="<?= $m['meeting_id']; ?>" <?= ($selected_meeting_id == $m['meeting_id']) ? 'selected' : ''; ?>>
                            #<?= $m['meeting_id']; ?> - <?= htmlspecialchars($m['type_name']); ?> (<?= date('M d, Y', strtotime($m['date'])); ?> at <?= date('h:i A', strtotime($m['time'])); ?>) [<?= $m['status']; ?>]
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Load Meeting</button>
        </form>
    </div>
</div>

<?php if ($selected_meeting): ?>
    <!-- Selected Meeting Overview Card -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
            <div>
                <span class="badge badge-<?= strtolower($selected_meeting['status']); ?>"><?= $selected_meeting['status']; ?></span>
                <h2 style="font-size: 17px; margin-top: 5px; color: #1a365d;"><?= htmlspecialchars($selected_meeting['type_name']); ?></h2>
                <p style="color: #4a5568; font-size: 13px;">
                    📅 <strong>Date:</strong> <?= date('l, F j, Y', strtotime($selected_meeting['date'])); ?> &bull; 
                    ⏰ <strong>Time:</strong> <?= date('g:i A', strtotime($selected_meeting['time'])); ?> &bull; 
                    📍 <strong>Venue:</strong> <?= htmlspecialchars($selected_meeting['location']); ?>
                </p>
            </div>
            <div>
                <a href="meeting_details.php?id=<?= $selected_meeting['meeting_id']; ?>" class="btn btn-secondary btn-sm">
                    View Meeting Details & Agenda
                </a>
            </div>
        </div>
    </div>

    <!-- Attendance Roster Card -->
    <div class="card">
        <div class="card-header">
            <h2>👥 Staff Attendance Roster (<?= count($roster); ?> Members)</h2>
            <?php if (is_admin()): ?>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="markAllStatus('Present')">Mark All Present</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="markAllStatus('Absent')">Mark All Absent</button>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body" style="padding: 0;">
            <form action="meeting_actions.php" method="POST">
                <input type="hidden" name="action" value="save_attendance">
                <input type="hidden" name="meeting_id" value="<?= $selected_meeting_id; ?>">

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Staff Member Name</th>
                                <th>Designation / Role</th>
                                <th>Email</th>
                                <th style="text-align: right;">Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roster as $idx => $s): ?>
                                <tr>
                                    <td><?= $idx + 1; ?></td>
                                    <td><strong><?= htmlspecialchars($s['name']); ?></strong></td>
                                    <td><?= htmlspecialchars($s['role']); ?></td>
                                    <td><span style="color: #718096;"><?= htmlspecialchars($s['email']); ?></span></td>
                                    <td style="text-align: right;">
                                        <?php if (is_admin()): ?>
                                            <div style="display: inline-flex; gap: 10px; align-items: center;">
                                                <label style="cursor: pointer; font-size: 12px; color: #276749;">
                                                    <input type="radio" name="attendance[<?= $s['staff_id']; ?>]" value="Present" <?= ($s['attendance_status'] === 'Present') ? 'checked' : ''; ?>> Present
                                                </label>
                                                <label style="cursor: pointer; font-size: 12px; color: #c53030;">
                                                    <input type="radio" name="attendance[<?= $s['staff_id']; ?>]" value="Absent" <?= ($s['attendance_status'] === 'Absent') ? 'checked' : ''; ?>> Absent
                                                </label>
                                                <label style="cursor: pointer; font-size: 12px; color: #c05621;">
                                                    <input type="radio" name="attendance[<?= $s['staff_id']; ?>]" value="Excused" <?= ($s['attendance_status'] === 'Excused') ? 'checked' : ''; ?>> Excused
                                                </label>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge badge-<?= strtolower($s['attendance_status']); ?>">
                                                <?= htmlspecialchars($s['attendance_status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (is_admin()): ?>
                    <div style="padding: 14px 18px; background-color: #f7fafc; border-top: 1px solid #e2e8f0; text-align: right;">
                        <button type="submit" class="btn btn-primary">
                            💾 Save Attendance Records
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- No meetings exist yet -->
    <div class="card">
        <div class="card-body" style="padding: 40px; text-align: center; color: #718096;">
            <p style="font-size: 32px;">👥</p>
            <h3>No Scheduled Meetings Available</h3>
            <p>Attendance records can be taken once a meeting session is scheduled.</p>
            <?php if (is_admin()): ?>
                <a href="schedule_meeting.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">+ Schedule Meeting</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Section: Current User's Personal Attendance Summary -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h2>📋 My Personal Attendance History (<?= htmlspecialchars($current_user_name); ?>)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php
        $my_att_stmt = $conn->prepare("
            SELECT sm.meeting_id, sm.date, sm.time, sm.location, mt.type_name, a.status AS my_status
            FROM Attendance a
            JOIN Scheduled_Meeting sm ON a.meeting_id = sm.meeting_id
            LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
            WHERE a.staff_id = ?
            ORDER BY sm.date DESC, sm.time DESC
        ");
        $my_att_stmt->bind_param("i", $current_user_id);
        $my_att_stmt->execute();
        $my_res = $my_att_stmt->get_result();
        ?>
        <?php if ($my_res && $my_res->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Meeting</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th style="text-align: right;">My Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($my_row = $my_res->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($my_row['type_name']); ?></strong></td>
                                <td><?= date('M d, Y', strtotime($my_row['date'])); ?> at <?= date('h:i A', strtotime($my_row['time'])); ?></td>
                                <td><?= htmlspecialchars($my_row['location']); ?></td>
                                <td style="text-align: right;">
                                    <span class="badge badge-<?= strtolower($my_row['my_status']); ?>">
                                        <?= htmlspecialchars($my_row['my_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="padding: 20px; text-align: center; color: #718096; font-size: 13px;">
                No attendance records have been registered for your account yet.
            </p>
        <?php endif; ?>
        <?php $my_att_stmt->close(); ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
