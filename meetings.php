<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Meetings List & Management: meetings.php
 */

$page_title = 'Department Meetings';
require_once __DIR__ . '/includes/header.php';

// Success and Error banners
$msg = $_GET['msg'] ?? '';
$err = $_GET['error'] ?? '';

// Filters
$filter_type = (int)($_GET['type_id'] ?? 0);
$filter_status = sanitize_input($_GET['status'] ?? '');
$search_query = sanitize_input($_GET['q'] ?? '');

// Base SQL query linking Scheduled_Meeting with Meeting_Type, Agenda, Materials, Attendance, and Decision_Report
$sql = "
    SELECT sm.meeting_id, sm.type_id, sm.date, sm.time, sm.location, sm.status,
           mt.type_name,
           (SELECT COUNT(*) FROM Agenda WHERE meeting_id = sm.meeting_id) AS has_agenda,
           (SELECT COUNT(*) FROM Meeting_Materials WHERE meeting_id = sm.meeting_id) AS materials_count,
           (SELECT COUNT(*) FROM Attendance WHERE meeting_id = sm.meeting_id AND status = 'Present') AS present_count,
           (SELECT COUNT(*) FROM Decision_Report WHERE meeting_id = sm.meeting_id) AS has_decisions
    FROM Scheduled_Meeting sm
    LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
    WHERE 1=1
";

$params = [];
$types = '';

if ($filter_type > 0) {
    $sql .= " AND sm.type_id = ?";
    $params[] = $filter_type;
    $types .= 'i';
}

if (!empty($filter_status)) {
    $sql .= " AND sm.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($search_query)) {
    $sql .= " AND (mt.type_name LIKE ? OR sm.location LIKE ?)";
    $like_str = "%{$search_query}%";
    $params[] = $like_str;
    $params[] = $like_str;
    $types .= 'ss';
}

$sql .= " ORDER BY sm.date DESC, sm.time DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$meetings_res = $stmt->get_result();
?>

<!-- Feedback messages -->
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
        <h1>Department Meeting Management</h1>
        <p>Annual meeting schedules, agendas, materials, and session statuses</p>
    </div>
    <div>
        <?php if (is_admin()): ?>
            <a href="schedule_meeting.php" class="btn btn-primary">+ Schedule Meeting</a>
        <?php endif; ?>
        <a href="calendar.php" class="btn btn-secondary">🗓️ View Calendar</a>
    </div>
</div>

<!-- Search & Filtering Bar -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding: 12px 18px;">
        <form action="meetings.php" method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="text" name="q" class="form-control" placeholder="Search by title or venue..." value="<?= htmlspecialchars($search_query); ?>" style="width: 200px;">
            
            <select name="type_id" class="form-control" style="width: 250px;" onchange="this.form.submit()">
                <option value="">All 13 Meeting Types</option>
                <?php
                $types_res = $conn->query("SELECT type_id, type_name FROM Meeting_Type ORDER BY type_id ASC");
                if ($types_res) {
                    while ($t = $types_res->fetch_assoc()) {
                        $selected = ($filter_type == $t['type_id']) ? 'selected' : '';
                        echo "<option value='{$t['type_id']}' {$selected}>" . htmlspecialchars($t['type_name']) . "</option>";
                    }
                }
                ?>
            </select>

            <select name="status" class="form-control" style="width: 140px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="Scheduled" <?= ($filter_status === 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                <option value="Completed" <?= ($filter_status === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                <option value="Cancelled" <?= ($filter_status === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                <option value="Rescheduled" <?= ($filter_status === 'Rescheduled') ? 'selected' : ''; ?>>Rescheduled</option>
            </select>

            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if ($filter_type > 0 || !empty($filter_status) || !empty($search_query)): ?>
                <a href="meetings.php" class="btn btn-secondary btn-sm" style="color: #c53030;">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Meetings Listing Table -->
<div class="card">
    <div class="card-header">
        <h2>Department Meeting Sessions (<?= ($meetings_res) ? $meetings_res->num_rows : 0; ?> Records)</h2>
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
                            <th>Agenda</th>
                            <th>Files</th>
                            <th>Decisions</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($m = $meetings_res->fetch_assoc()): ?>
                            <tr>
                                <td><strong style="color: #718096;">#<?= $m['meeting_id']; ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($m['type_name'] ?? 'General Meeting'); ?></strong>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($m['date'])); ?><br>
                                    <small style="color: #718096;"><?= date('h:i A', strtotime($m['time'])); ?></small>
                                </td>
                                <td><?= htmlspecialchars($m['location'] ?? 'Department Boardroom'); ?></td>
                                <td>
                                    <?php if ($m['has_agenda'] > 0): ?>
                                        <span class="badge" style="background: #ebf8ff; color: #2b6cb0;">✓ Added</span>
                                    <?php else: ?>
                                        <span style="color: #a0aec0; font-size: 11px;">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($m['materials_count'] > 0): ?>
                                        <span class="badge" style="background: #faf5ff; color: #6b46c1;">📎 <?= $m['materials_count']; ?></span>
                                    <?php else: ?>
                                        <span style="color: #a0aec0; font-size: 11px;">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($m['has_decisions'] > 0): ?>
                                        <span class="badge badge-completed">Recorded</span>
                                    <?php else: ?>
                                        <span style="color: #a0aec0; font-size: 11px;">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower($m['status']); ?>">
                                        <?= htmlspecialchars($m['status']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="meeting_details.php?id=<?= $m['meeting_id']; ?>" class="btn btn-secondary btn-sm">
                                        View Details
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
            <div style="padding: 40px; text-align: center; color: #718096;">
                <p style="font-size: 32px; margin-bottom: 8px;">📅</p>
                <h3 style="color: #2d3748; margin-bottom: 6px;">No Meetings Found</h3>
                <p>No department meetings match your search or filter criteria.</p>
                <?php if (is_admin()): ?>
                    <a href="schedule_meeting.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">+ Schedule Meeting</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
