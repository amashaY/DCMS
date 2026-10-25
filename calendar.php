<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Monthly Meeting Calendar: calendar.php
 */

$page_title = 'Annual Meeting Calendar';
require_once __DIR__ . '/includes/header.php';

// Get current month and year from GET params or default to current date
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Validate month and year boundaries
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

// Calculate previous and next month parameters
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

// Query database for all meetings in this specific month and year
$cal_sql = "
    SELECT sm.meeting_id, sm.date, sm.time, sm.location, sm.status, mt.type_name
    FROM Scheduled_Meeting sm
    LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
    WHERE MONTH(sm.date) = ? AND YEAR(sm.date) = ?
    ORDER BY sm.date ASC, sm.time ASC
";
$stmt = $conn->prepare($cal_sql);
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$cal_res = $stmt->get_result();

// Group meetings by date (e.g. '2026-10-15' => [array of meetings])
$meetings_by_date = [];
$month_meetings_list = [];
while ($m = $cal_res->fetch_assoc()) {
    $meetings_by_date[$m['date']][] = $m;
    $month_meetings_list[] = $m;
}
$stmt->close();

// Calendar Calculations using PHP Date
$first_day_timestamp = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day_timestamp);
$first_day_of_week = date('w', $first_day_timestamp); // 0 (Sun) to 6 (Sat)
$month_name = date('F', $first_day_timestamp);
$today_str = date('Y-m-d');
?>

<div class="page-header">
    <div class="page-title">
        <h1>Department Meeting Calendar</h1>
        <p>Interactive schedule for monthly and recurring department meetings</p>
    </div>
    <div>
        <?php if (is_admin()): ?>
            <a href="schedule_meeting.php" class="btn btn-primary">+ Schedule Meeting</a>
        <?php endif; ?>
        <a href="meetings.php" class="btn btn-secondary">📋 Table View</a>
    </div>
</div>

<!-- Calendar Month Navigation Card -->
<div class="card">
    <div class="card-header">
        <h2><?= $month_name . ' ' . $year; ?></h2>
        <div style="display: flex; gap: 8px;">
            <a href="calendar.php?month=<?= $prev_month; ?>&year=<?= $prev_year; ?>" class="btn btn-secondary btn-sm">&larr; Previous</a>
            <a href="calendar.php?month=<?= date('n'); ?>&year=<?= date('Y'); ?>" class="btn btn-secondary btn-sm">Current Month</a>
            <a href="calendar.php?month=<?= $next_month; ?>&year=<?= $next_year; ?>" class="btn btn-secondary btn-sm">Next &rarr;</a>
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Sun</th>
                    <th>Mon</th>
                    <th>Tue</th>
                    <th>Wed</th>
                    <th>Thu</th>
                    <th>Fri</th>
                    <th>Sat</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                <?php
                // Print blank cells for days before the 1st of the month
                for ($blank = 0; $blank < $first_day_of_week; $blank++) {
                    echo '<td class="empty-day"></td>';
                }

                $current_day_of_week = $first_day_of_week;

                // Loop through all days of the month
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $day_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $is_today = ($day_date === $today_str);
                    $td_class = $is_today ? 'today' : '';

                    echo "<td class='{$td_class}'>";
                    echo "<span class='day-number'>{$day}</span>";

                    // Check if any meetings fall on this day
                    if (isset($meetings_by_date[$day_date])) {
                        foreach ($meetings_by_date[$day_date] as $meeting) {
                            $time_fmt = date('h:i A', strtotime($meeting['time']));
                            $title_str = htmlspecialchars($meeting['type_name'] . ' at ' . $time_fmt);
                            echo "<a href='meeting_details.php?id={$meeting['meeting_id']}' class='cal-meeting-pill' title='{$title_str}'>";
                            echo $time_fmt . ' ' . htmlspecialchars($meeting['type_name']);
                            echo "</a>";
                        }
                    }

                    echo "</td>";

                    $current_day_of_week++;

                    // If week is complete (7 days), start a new table row
                    if ($current_day_of_week % 7 == 0 && $day < $days_in_month) {
                        echo "</tr><tr>";
                    }
                }

                // Fill remaining cells in the last week row
                while ($current_day_of_week % 7 != 0) {
                    echo '<td class="empty-day"></td>';
                    $current_day_of_week++;
                }
                ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- List of Sessions for Selected Month -->
<div class="card">
    <div class="card-header">
        <h2>Sessions Scheduled for <?= $month_name . ' ' . $year; ?> (<?= count($month_meetings_list); ?> Meetings)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($month_meetings_list)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Meeting Type</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($month_meetings_list as $m): ?>
                            <tr>
                                <td>
                                    <strong><?= date('l, M d, Y', strtotime($m['date'])); ?></strong><br>
                                    <small style="color: #718096;"><?= date('h:i A', strtotime($m['time'])); ?></small>
                                </td>
                                <td><?= htmlspecialchars($m['type_name']); ?></td>
                                <td><?= htmlspecialchars($m['location'] ?: 'Department Boardroom'); ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($m['status']); ?>">
                                        <?= htmlspecialchars($m['status']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="meeting_details.php?id=<?= $m['meeting_id']; ?>" class="btn btn-secondary btn-sm">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="padding: 24px; text-align: center; color: #718096;">
                No department meetings scheduled for <?= $month_name . ' ' . $year; ?>.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
