<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * User Authentication Page: login.php
 */

require_once __DIR__ . '/config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username_or_email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $error_msg = 'Please enter both username/email and password.';
    } else {
        // Query Staff table using Prepared Statements (Security: Prevents SQL Injection)
        $stmt = $conn->prepare("SELECT staff_id, name, email, username, password, role FROM Staff WHERE (email = ? OR username = ?) AND password = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("sss", $identifier, $identifier, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Successful authentication: Set session variables
                $_SESSION['user_id'] = $row['staff_id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];

                header("Location: index.php");
                exit();
            } else {
                $error_msg = 'Invalid username/email or password. Please try again.';
            }
            $stmt->close();
        } else {
            $error_msg = 'Database error: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Department Communication Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/crest.png">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-header">
        <img src="assets/images/crest.png" alt="Rajarata University Crest" style="height: 85px; width: auto; margin-bottom: 8px; object-fit: contain;">
        <h1>Staff Portal Login</h1>
        <p><strong>Department Communication Management System</strong></p>
        <p style="margin-top: 2px;">Department of Computing &bull; Faculty of Applied Sciences<br>Rajarata University of Sri Lanka</p>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username_or_email" class="form-label">Username or Email Address</label>
            <input type="text" id="username_or_email" name="username_or_email" class="form-control" 
                   placeholder="e.g. hod or hod@example.com" required autofocus>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" 
                   placeholder="Enter your password" required>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; font-size: 14px;">
                Sign In
            </button>
        </div>
    </form>

    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #718096; text-align: center;">
        Course: ICT 1407 Database Systems Assignment &bull; Team 214.0
    </div>
</div>

</body>
</html>
