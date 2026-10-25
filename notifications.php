<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Notifications & Department Alerts: notifications.php
 */

$page_title = 'Department Notifications';
require_once __DIR__ . '/includes/header.php';

$msg = $_GET['msg'] ?? '';

// Fetch all notifications for current logged-in user
$stmt = $conn->prepare("
    SELECT notification_id, message, is_read, date_sent
    FROM Notification
    WHERE staff_id = ?
    ORDER BY date_sent DESC
");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$notifs_res = $stmt->get_result();
?>

<!-- Feedback Message -->
<?php if (!empty($msg)): ?>
    <div class="alert alert-success">
        <span>✓ <?= htmlspecialchars($msg); ?></span>
    </div>
<?php endif; ?>

<div class="page-header">
    <div class="page-title">
        <h1>Department Alerts & Notifications</h1>
        <p>Automated alerts for meeting schedules, status changes, and announcements</p>
    </div>
    <div>
        <a href="meeting_actions.php?action=mark_notifs_read" class="btn btn-secondary btn-sm">
            ✓ Mark All as Read
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>🔔 My Notifications (<?= ($notifs_res) ? $notifs_res->num_rows : 0; ?> Total)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if ($notifs_res && $notifs_res->num_rows > 0): ?>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php while ($n = $notifs_res->fetch_assoc()): ?>
                    <li style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background-color: <?= ($n['is_read'] == 0) ? '#ebf8ff' : '#ffffff'; ?>; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                        <div>
                            <p style="margin-bottom: 4px; color: #2d3748; <?= ($n['is_read'] == 0) ? 'font-weight: bold;' : ''; ?>">
                                <?= ($n['is_read'] == 0) ? '📩 ' : '📬 '; ?>
                                <?= htmlspecialchars($n['message']); ?>
                            </p>
                            <small style="color: #718096; font-size: 11px;">
                                <?= date('l, F j, Y \a\t g:i A', strtotime($n['date_sent'])); ?>
                            </small>
                        </div>
                        <?php if ($n['is_read'] == 0): ?>
                            <span class="badge" style="background: #bee3f8; color: #2b6cb0;">New</span>
                        <?php endif; ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <div style="padding: 30px; text-align: center; color: #718096;">
                <p style="font-size: 28px;">🔔</p>
                <p>No notifications in your inbox.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
