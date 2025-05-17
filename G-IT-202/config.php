<?php
// Only start session if none exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'advance_system_design';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    // Optional: only show during testing
    // echo "Database connected!";
}
?>
