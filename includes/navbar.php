<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Simple Navigation Bar: includes/navbar.php
 */

$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="navbar">
    <div class="nav-container">
        <!-- Brand / University Logo -->
        <a href="index.php" class="nav-brand">
            <img src="assets/images/crest.png" alt="Rajarata University Crest">
            <span>Department Communication Management System</span>
        </a>

        <!-- Main Navigation Links -->
        <ul class="nav-menu">
            <li class="<?= ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a href="index.php">Dashboard</a>
            </li>
            <li class="<?= ($current_page == 'meetings.php' || $current_page == 'schedule_meeting.php' || $current_page == 'meeting_details.php') ? 'active' : ''; ?>">
                <a href="meetings.php">Meetings</a>
            </li>
            <li class="<?= ($current_page == 'calendar.php') ? 'active' : ''; ?>">
                <a href="calendar.php">Calendar</a>
            </li>
            <li class="<?= ($current_page == 'attendance.php') ? 'active' : ''; ?>">
                <a href="attendance.php">Attendance</a>
            </li>
            <li class="<?= ($current_page == 'documents.php') ? 'active' : ''; ?>">
                <a href="documents.php">Documents</a>
            </li>
            <li class="<?= ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <a href="reports.php">Reports</a>
            </li>
        </ul>

        <!-- User Information & Actions -->
        <div class="nav-user">
            <!-- Notifications Link -->
            <a href="notifications.php" style="color: #ffffff; text-decoration: none; position: relative; font-size: 14px;" title="Notifications">
                🔔
                <?php if (!empty($unread_notif_count) && $unread_notif_count > 0): ?>
                    <span style="background: #dc2626; color: #fff; font-size: 10px; font-weight: bold; border-radius: 10px; padding: 2px 5px; vertical-align: top;">
                        <?= $unread_notif_count; ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- User Name and Role Pill -->
            <span class="user-badge" title="Logged in as <?= htmlspecialchars($current_user_name); ?>">
                <?= htmlspecialchars($current_user_name); ?> 
                <span class="role-pill"><?= htmlspecialchars($current_user_role); ?></span>
            </span>

            <!-- Logout -->
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
</header>
