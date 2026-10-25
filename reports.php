<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Historical Search & Summary Reports: reports.php
 */

require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/includes/auth_check.php';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Department_Meeting_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');

    // Output CSV headers
    fputcsv($output, ['Meeting ID', 'Meeting Type', 'Date', 'Time', 'Location', 'Status', 'Decisions Recorded']);

    $csv_sql = "
        SELECT sm.meeting_id, mt.type_name, sm.date, sm.time, sm.location, sm.status,
               IF(dr.decisions IS NOT NULL, 'Yes', 'No') AS has_decisions
        FROM Scheduled_Meeting sm
        LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
        LEFT JOIN Decision_Report dr ON sm.meeting_id = dr.meeting_id
        ORDER BY sm.date DESC
    ";
    $csv_res = $conn->query($csv_sql);
    if ($csv_res) {
        while ($row = $csv_res->fetch_assoc()) {
            fputcsv($output, [
                $row['meeting_id'],
                $row['type_name'],
                $row['date'],
                $row['time'],
                $row['location'],
                $row['status'],
                $row['has_decisions']
            ]);
        }
    }
    fclose($output);
    exit();
}

$page_title = 'Summary Reports & Historical Querying';
require_once __DIR__ . '/includes/header.php';

// Filter inputs
$filter_type = (int)($_GET['type_id'] ?? 0);
$filter_staff = (int)($_GET['staff_id'] ?? 0);
$date_from = sanitize_input($_GET['date_from'] ?? '');
$date_to = sanitize_input($_GET['date_to'] ?? '');
$filter_status = sanitize_input($_GET['status'] ?? '');

// Base query for filtered meetings
$query = "
    SELECT sm.meeting_id, sm.date, sm.time, sm.location, sm.status,
           mt.type_name,
           (SELECT COUNT(*) FROM Attendance WHERE meeting_id = sm.meeting_id AND status = 'Present') AS present_count,
           (SELECT COUNT(*) FROM Attendance WHERE meeting_id = sm.meeting_id) AS total_roster,
           dr.decisions
    FROM Scheduled_Meeting sm
    LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
    LEFT JOIN Decision_Report dr ON sm.meeting_id = dr.meeting_id
    WHERE 1=1
";

$params = [];
$types = '';

if ($filter_type > 0) {
    $query .= " AND sm.type_id = ?";
    $params[] = $filter_type;
    $types .= 'i';
}

if (!empty($filter_status)) {
    $query .= " AND sm.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($date_from)) {
    $query .= " AND sm.date >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $query .= " AND sm.date <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if ($filter_staff > 0) {
    $query .= " AND sm.meeting_id IN (SELECT meeting_id FROM Attendance WHERE staff_id = ? AND status = 'Present')";
    $params[] = $filter_staff;
    $types .= 'i';
}

$query .= " ORDER BY sm.date DESC, sm.time DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$meetings_res = $stmt->get_result();

// High-level statistics
$stat_completed = 0;
$stat_cancelled = 0;
$stat_scheduled = 0;
$stat_rescheduled = 0;

$count_res = $conn->query("
    SELECT status, COUNT(*) as cnt 
    FROM Scheduled_Meeting 
    GROUP BY status
");
if ($count_res) {
    while ($cr = $count_res->fetch_assoc()) {
        if ($cr['status'] === 'Completed') $stat_completed = (int)$cr['cnt'];
        elseif ($cr['status'] === 'Cancelled') $stat_cancelled = (int)$cr['cnt'];
        elseif ($cr['status'] === 'Scheduled') $stat_scheduled = (int)$cr['cnt'];
        elseif ($cr['status'] === 'Rescheduled') $stat_rescheduled = (int)$cr['cnt'];
    }
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Department Reports & Historical Querying</h1>
        <p>Analyze meeting metrics, attendance compliance, and historical decision records</p>
    </div>
    <div class="no-print">
        <a href="reports.php?export=csv" class="btn btn-secondary">📥 Export CSV</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ Print</button>
    </div>
</div>

<!-- Meeting Statistics Summary -->
<div class="stats-grid">
    <div class="stat-box green">
        <div class="stat-icon">✓</div>
        <div class="stat-content">
            <h3><?= $stat_completed; ?></h3>
            <p>Meetings Completed</p>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
            <h3><?= $stat_scheduled; ?></h3>
            <p>Scheduled / Upcoming</p>
        </div>
    </div>
    <div class="stat-box amber">
        <div class="stat-icon">⚠️</div>
        <div class="stat-content">
            <h3><?= $stat_rescheduled; ?></h3>
            <p>Rescheduled Sessions</p>
        </div>
    </div>
    <div class="stat-box red">
        <div class="stat-icon">✕</div>
        <div class="stat-content">
            <h3><?= $stat_cancelled; ?></h3>
            <p>Cancelled Meetings</p>
        </div>
    </div>
</div>

<!-- Historical Query Filter Form -->
<div class="card no-print" style="margin-bottom: 20px;">
    <div class="card-header">
        <h2>🔍 Historical Query Filters</h2>
    </div>
    <div class="card-body">
        <form action="reports.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="rep_type_id">Meeting Category</label>
                <select name="type_id" id="rep_type_id" class="form-control">
                    <option value="">All 13 Meeting Types</option>
                    <?php
                    $t_res = $conn->query("SELECT type_id, type_name FROM Meeting_Type ORDER BY type_id ASC");
                    if ($t_res) {
                        while ($t = $t_res->fetch_assoc()) {
                            $sel = ($filter_type == $t['type_id']) ? 'selected' : '';
                            echo "<option value='{$t['type_id']}' {$sel}>" . htmlspecialchars($t['type_name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="rep_staff_id">Attended By Staff Member</label>
                <select name="staff_id" id="rep_staff_id" class="form-control">
                    <option value="">All Department Staff</option>
                    <?php
                    $s_res = $conn->query("SELECT staff_id, name, role FROM Staff ORDER BY staff_id ASC");
                    if ($s_res) {
                        while ($s = $s_res->fetch_assoc()) {
                            $sel = ($filter_staff == $s['staff_id']) ? 'selected' : '';
                            echo "<option value='{$s['staff_id']}' {$sel}>" . htmlspecialchars($s['name']) . " (" . htmlspecialchars($s['role']) . ")</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="rep_status">Session Status</label>
                <select name="status" id="rep_status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="Scheduled" <?= ($filter_status === 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="Completed" <?= ($filter_status === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?= ($filter_status === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="Rescheduled" <?= ($filter_status === 'Rescheduled') ? 'selected' : ''; ?>>Rescheduled</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="date_from">Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($date_from); ?>">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="date_to">Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($date_to); ?>">
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
                <a href="reports.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Meeting Session Report Table -->
<div class="card">
    <div class="card-header">
        <h2>📋 Historical Meeting Query Results (<?= ($meetings_res) ? $meetings_res->num_rows : 0; ?> Sessions)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if ($meetings_res && $meetings_res->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Meeting Type</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Attendance</th>
                            <th>Decisions Logged</th>
                            <th>Status</th>
                            <th class="no-print" style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($m = $meetings_res->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $m['meeting_id']; ?></strong></td>
                                <td><strong><?= htmlspecialchars($m['type_name']); ?></strong></td>
                                <td><?= date('M d, Y', strtotime($m['date'])); ?><br><small style="color: #718096;"><?= date('h:i A', strtotime($m['time'])); ?></small></td>
                                <td><?= htmlspecialchars($m['location']); ?></td>
                                <td>
                                    <?php if ($m['total_roster'] > 0): ?>
                                        <?= $m['present_count']; ?> / <?= $m['total_roster']; ?> Present
                                    <?php else: ?>
                                        <span style="color: #a0aec0;">Not marked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($m['decisions'])): ?>
                                        <span class="badge badge-completed">Recorded</span>
                                    <?php else: ?>
                                        <span style="color: #a0aec0;">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower($m['status']); ?>">
                                        <?= htmlspecialchars($m['status']); ?>
                                    </span>
                                </td>
                                <td class="no-print" style="text-align: right;">
                                    <a href="meeting_details.php?id=<?= $m['meeting_id']; ?>" class="btn btn-secondary btn-sm">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="padding: 30px; text-align: center; color: #718096;">
                No meeting records match the specified query parameters.
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Department Staff Attendance Summary Table -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h2>👥 Staff Attendance Compliance Breakdown</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php
        $att_summary_sql = "
            SELECT s.staff_id, s.name, s.role, s.email,
                   COUNT(a.attendance_id) AS total_sessions,
                   SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                   SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
                   SUM(CASE WHEN a.status = 'Excused' THEN 1 ELSE 0 END) AS excused_count
            FROM Staff s
            LEFT JOIN Attendance a ON s.staff_id = a.staff_id
            GROUP BY s.staff_id, s.name, s.role, s.email
            ORDER BY s.staff_id ASC
        ";
        $att_summary_res = $conn->query($att_summary_sql);
        ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Staff Member</th>
                        <th>Role / Designation</th>
                        <th>Total Tracked</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Excused</th>
                        <th style="text-align: right;">Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($att_summary_res && $att_summary_res->num_rows > 0): ?>
                        <?php while ($as = $att_summary_res->fetch_assoc()): ?>
                            <?php
                            $rate = ($as['total_sessions'] > 0) ? round(($as['present_count'] / $as['total_sessions']) * 100) : 0;
                            ?>
                            <tr>
                                <td><?= $as['staff_id']; ?></td>
                                <td><strong><?= htmlspecialchars($as['name']); ?></strong></td>
                                <td><?= htmlspecialchars($as['role']); ?></td>
                                <td><?= $as['total_sessions']; ?></td>
                                <td><span style="color: #16a34a; font-weight: bold;"><?= $as['present_count']; ?></span></td>
                                <td><span style="color: #dc2626; font-weight: bold;"><?= $as['absent_count']; ?></span></td>
                                <td><span style="color: #d97706; font-weight: bold;"><?= $as['excused_count']; ?></span></td>
                                <td style="text-align: right;">
                                    <strong><?= $rate; ?>%</strong>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
