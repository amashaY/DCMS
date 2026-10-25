<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Course: ICT 1407 - Database Systems
 * Common Header File: includes/header.php
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/auth_check.php';

// Count unread notifications for current user
$unread_notif_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM Notification WHERE staff_id = ? AND is_read = 0");
if ($stmt) {
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $unread_notif_count = (int)$row['total'];
    }
    $stmt->close();
}

// Compute role-based theme class for dynamic color schemes
$theme_class = 'theme-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $current_user_role));

$page_title = isset($page_title) 
    ? $page_title . ' - Department Communication Management System' 
    : 'Department Communication Management System - Department of Computing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/crest.png">
</head>
<body class="<?= htmlspecialchars($theme_class); ?>">
<?php include_once __DIR__ . '/navbar.php'; ?>
<div class="container">
