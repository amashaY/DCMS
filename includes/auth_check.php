<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * 
 * Session & Authentication Verification: includes/auth_check.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Current logged-in user profile details
$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['name'] ?? 'Staff Member';
$current_user_email = $_SESSION['email'] ?? '';
$current_user_role = $_SESSION['role'] ?? 'Staff';

// Role helper function
function is_admin() {
    global $current_user_role;
    return in_array($current_user_role, ['HOD', 'Management Assistant']);
}
?>
