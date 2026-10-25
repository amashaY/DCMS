<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * 
 * Database Connection File: config/db_connect.php
 */

// Database configuration settings (Default for standard XAMPP / WampServer environments)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'dcms_db';

// Establish connection with MySQL server using MySQLi
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check if connection was successful
if ($conn->connect_error) {
    // If the database does not exist yet, attempt auto-initialization or display friendly instructions
    if ($conn->connect_errno == 1049) { // Unknown database error code
        $temp_conn = new mysqli($db_host, $db_user, $db_pass);
        if (!$temp_conn->connect_error) {
            $sql_file = __DIR__ . '/../database.sql';
            if (file_exists($sql_file)) {
                $sql_content = file_get_contents($sql_file);
                if ($temp_conn->multi_query($sql_content)) {
                    do {
                        if ($result = $temp_conn->store_result()) {
                            $result->free();
                        }
                    } while ($temp_conn->next_result());
                    $temp_conn->close();
                    // Reconnect to newly created database
                    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                }
            }
        }
    }
    
    // If still failing, provide student-friendly message
    if ($conn->connect_error) {
        die("<div style='font-family:sans-serif; padding:20px; max-width:600px; margin:50px auto; border:1px solid #f87171; background:#fef2f2; border-radius:8px;'>
            <h2 style='color:#b91c1c; margin-top:0;'>Database Connection Error</h2>
            <p>Could not connect to the database <strong>{$db_name}</strong> on <strong>{$db_host}</strong>.</p>
            <p><strong>Error Details:</strong> " . htmlspecialchars($conn->connect_error) . "</p>
            <hr style='border:none; border-top:1px solid #fca5a5;'>
            <p><strong>Quick Fix Instructions:</strong></p>
            <ol>
                <li>Start <strong>Apache</strong> and <strong>MySQL</strong> from your XAMPP Control Panel.</li>
                <li>Open <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a> in your browser.</li>
                <li>Import the <code>database.sql</code> file included in this project folder.</li>
                <li>Refresh this page.</li>
            </ol>
        </div>");
    }
}

// Set charset to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");

// Helper function to sanitize user input strings
function sanitize_input($data) {
    if ($data === null) return '';
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Helper function to check if current user has administrative permissions (HOD or Management Assistant)
function is_admin_user() {
    return (isset($_SESSION['role']) && ($_SESSION['role'] === 'HOD' || $_SESSION['role'] === 'Management Assistant'));
}

// Helper function to create an automated notification for staff members
function add_system_notification($conn, $staff_id, $message) {
    $stmt = $conn->prepare("INSERT INTO Notification (staff_id, message, date_sent) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("is", $staff_id, $message);
        $stmt->execute();
        $stmt->close();
    }
}

// Helper function to broadcast notification to all department staff
function broadcast_notification($conn, $message) {
    $result = $conn->query("SELECT staff_id FROM Staff");
    if ($result) {
        $stmt = $conn->prepare("INSERT INTO Notification (staff_id, message, date_sent) VALUES (?, ?, NOW())");
        if ($stmt) {
            while ($row = $result->fetch_assoc()) {
                $sid = $row['staff_id'];
                $stmt->bind_param("is", $sid, $message);
                $stmt->execute();
            }
            $stmt->close();
        }
    }
}
?>
