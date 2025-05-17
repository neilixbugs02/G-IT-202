<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$record_id = $_GET['id'] ?? null;

if (!$record_id) {
    echo "Invalid record ID.";
    exit();
}

// Admins can delete anything; Users can only delete their own records
if ($role === 'admin') {
    $stmt = $conn->prepare("DELETE FROM records WHERE id = ?");
    $stmt->bind_param("i", $record_id);
} else {
    $stmt = $conn->prepare("DELETE FROM records WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $record_id, $user_id);
}

if ($stmt->execute() && $stmt->affected_rows > 0) {
    header("Location: " . ($role === 'admin' ? "admin_dashboard.php" : "user_dashboard.php"));
    exit();
} else {
    echo "Failed to delete record or you are not authorized.";
}
?>
