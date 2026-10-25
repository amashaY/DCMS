<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Executive Dashboard: index.php
 */

$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// 1. Fetch Key Metrics from Database
$total_meetings = 0;
$upcoming_meetings = 0;
$attendance_rate = 0;
$total_materials = 0;

// Total meetings
$res = $conn->query("SELECT COUNT(*) AS cnt FROM Scheduled_Meeting");
if ($res && $r = $res->fetch_assoc()) $total_meetings = (int)$r['cnt'];

// Upcoming meetings
$res = $conn->query("SELECT COUNT(*) AS cnt FROM Scheduled_Meeting WHERE date >= CURDATE() AND status != 'Cancelled'");
if ($res && $r = $res->fetch_assoc()) $upcoming_meetings = (int)$r['cnt'];

// Overall department attendance compliance rate
$res = $conn->query("SELECT COUNT(*) AS total_recs, SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS total_present FROM Attendance");
if ($res && $r = $res->fetch_assoc()) {
    $total_recs = (int)$r['total_recs'];
    $total_present = (int)$r['total_present'];
    if ($total_recs > 0) {
        $attendance_rate = round(($total_present / $total_recs) * 100);
    }
}

// Total documents / materials
$res = $conn->query("SELECT COUNT(*) AS cnt FROM Meeting_Materials");
if ($res && $r = $res->fetch_assoc()) $total_materials = (int)$r['cnt'];

// 2. Fetch Upcoming 6 Sessions
$upcoming_sql = "
    SELECT sm.meeting_id, sm.date, sm.time, sm.location, sm.status, mt.type_name
    FROM Scheduled_Meeting sm
    LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
    WHERE sm.date >= CURDATE() AND sm.status != 'Cancelled'
    ORDER BY sm.date ASC, sm.time ASC
    LIMIT 6
";
$upcoming_res = $conn->query($upcoming_sql);

// 3. Fetch Recent Notifications for Current User
$stmt = $conn->prepare("
    SELECT notification_id, message, date_sent, is_read
    FROM Notification
    WHERE staff_id = ?
    ORDER BY date_sent DESC
    LIMIT 5
");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$notifs_res = $stmt->get_result();
?>

<!-- Welcome Banner -->
<div class="page-header">
    <div class="page-title">
        <h1>Department Executive Dashboard</h1>
        <p>Welcome back, <strong><?= htmlspecialchars($current_user_name); ?></strong> (Role: <?= htmlspecialchars($current_user_role); ?>) &bull; Department of Computing</p>
    </div>
    <div>
        <?php if (is_admin()): ?>
            <a href="schedule_meeting.php" class="btn btn-primary">+ Schedule Meeting</a>
        <?php endif; ?>
        <a href="calendar.php" class="btn btn-secondary">🗓️ View Calendar</a>
    </div>
</div>

<!-- High-Level Metric Cards -->
<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
            <h3><?= $total_meetings; ?></h3>
            <p>Total Department Meetings</p>
        </div>
    </div>
    <div class="stat-box green">
        <div class="stat-icon">⏰</div>
        <div class="stat-content">
            <h3><?= $upcoming_meetings; ?></h3>
            <p>Upcoming Sessions</p>
        </div>
    </div>
    <div class="stat-box amber">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
            <h3><?= $attendance_rate; ?>%</h3>
            <p>Attendance Compliance</p>
        </div>
    </div>
    <div class="stat-box purple">
        <div class="stat-icon">📁</div>
        <div class="stat-content">
            <h3><?= $total_materials; ?></h3>
            <p>Repository Documents</p>
        </div>
    </div>
</div>

<!-- Main Split Content -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start;">
    
    <!-- Left Column: Upcoming Meetings Schedule -->
    <div class="card">
        <div class="card-header">
            <h2>📌 Upcoming Scheduled Meetings</h2>
            <a href="meetings.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if ($upcoming_res && $upcoming_res->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Meeting Type</th>
                                <th>Date & Time</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($m = $upcoming_res->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($m['type_name'] ?? 'General Meeting'); ?></strong>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($m['date'])); ?><br>
                                        <small style="color: #718096;"><?= date('h:i A', strtotime($m['time'])); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($m['location'] ?? 'Department Boardroom'); ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($m['status']); ?>">
                                            <?= htmlspecialchars($m['status']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="meeting_details.php?id=<?= $m['meeting_id']; ?>" class="btn btn-secondary btn-sm">
                                            Details
                                        </a>
                                        <?php if (is_admin()): ?>
                                            <a href="attendance.php?meeting_id=<?= $m['meeting_id']; ?>" class="btn btn-primary btn-sm">
                                                Attendance
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding: 30px; text-align: center; color: #718096;">
                    <p style="font-size: 28px; margin-bottom: 5px;">📅</p>
                    <p>No upcoming meetings currently scheduled.</p>
                    <?php if (is_admin()): ?>
                        <a href="schedule_meeting.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">+ Schedule Meeting</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Recent Alerts & Quick Info -->
    <div>
        <!-- Recent Notifications Card -->
        <div class="card">
            <div class="card-header">
                <h2>🔔 Recent Notifications</h2>
                <a href="notifications.php" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <div class="card-body" style="padding: 12px 18px;">
                <?php if ($notifs_res && $notifs_res->num_rows > 0): ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php while ($notif = $notifs_res->fetch_assoc()): ?>
                            <li style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; font-size: 13px;">
                                <p style="margin-bottom: 3px; color: #2d3748; <?= ($notif['is_read'] == 0) ? 'font-weight: 600;' : ''; ?>">
                                    <?= htmlspecialchars($notif['message']); ?>
                                </p>
                                <small style="color: #718096;">
                                    <?= date('M d, Y h:i A', strtotime($notif['date_sent'])); ?>
                                </small>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: #718096; font-size: 13px; text-align: center; padding: 15px 0;">
                        No recent notifications.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Quick Overview Card -->
        <div class="card">
            <div class="card-header">
                <h2>🏛️ Department Information</h2>
            </div>
            <div class="card-body" style="font-size: 13px; line-height: 1.6;">
                <p><strong>Department:</strong> Computing</p>
                <p><strong>Faculty:</strong> Applied Sciences</p>
                <p><strong>University:</strong> Rajarata University of Sri Lanka</p>
                <p><strong>Standing Categories:</strong> 13 Recurring Meeting Types</p>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 10px 0;">
                <p style="color: #718096; font-size: 12px;">
                    The Department Communication Management System manages annual calendar scheduling, minutes, attendance tracking, and materials.
                </p>
            </div>
        </div>
    </div>

</div>

<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
