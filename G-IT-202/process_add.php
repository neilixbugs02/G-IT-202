<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id         = $_SESSION['user_id'];
$gov_id          = $_POST['gov_id'];
$gov_id_number   = $_POST['gov_id_number'];
$gov_id_place    = $_POST['gov_id_place'];
$gov_id_expiry   = $_POST['gov_id_expiry'];

$stmt = $conn->prepare("INSERT INTO records (user_id, gov_id, gov_id_number, gov_id_place, gov_id_expiry) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $user_id, $gov_id, $gov_id_number, $gov_id_place, $gov_id_expiry);
$stmt->execute();

header("Location: user_dashboard.php");
exit();
