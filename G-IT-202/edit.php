<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    if ($role === 'admin') {
        // Admin can fetch any record
        $stmt = $conn->prepare("SELECT * FROM records WHERE id = ?");
        $stmt->bind_param("i", $id);
    } else {
        // Regular user can only fetch their own record
        $stmt = $conn->prepare("SELECT * FROM records WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();

    if (!$record) {
        echo "Record not found or you don't have permission to edit it.";
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];

    // Set all variables to null by default
    $card_name = $cc_number = $cvv = null;
    $gov_id = $gov_id_number = $gov_id_place = $gov_id_expiry = null;
    $credentials = $online_email = $online_username = $online_password = null;

    // Form validation
    $errors = [];

    if ($type === 'card') {
        $card_name = $_POST['card_name'] ?? null;
        $cc_number = $_POST['cc_number'] ?? null;
        $cvv = $_POST['cvv'] ?? null;

        // Validate card number (length and format)
        if (!preg_match("/^\d{16}$/", $cc_number)) {
            $errors[] = "Invalid credit card number. It must be exactly 16 digits.";
        }

        // Validate CVV (length and format)
        if (!preg_match("/^\d{3}$/", $cvv)) {
            $errors[] = "Invalid CVV. It must be exactly 3 digits.";
        }
    } elseif ($type === 'gov_id') {
        $gov_id = $_POST['gov_id'] ?? null;
        $gov_id_number = $_POST['gov_id_number'] ?? null;
        $gov_id_place = $_POST['gov_id_place'] ?? null;
        $gov_id_expiry = $_POST['gov_id_expiry'] ?? null;

        // Validate expiry date (must be in future)
        if ($gov_id_expiry && strtotime($gov_id_expiry) < time()) {
            $errors[] = "The expiry date must be in the future.";
        }
    } elseif ($type === 'credentials') {
        $credentials = $_POST['credentials'] ?? null;
        $online_email = $_POST['online_email'] ?? null;
        $online_username = $_POST['online_username'] ?? null;
        $online_password = $_POST['online_password'] ?? null;

        // Validate email format
        if (!filter_var($online_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email address.";
        }

        // Validate password strength (at least 8 characters, one uppercase, one number)
        if (strlen($online_password) < 8 || !preg_match("/[A-Z]/", $online_password) || !preg_match("/\d/", $online_password)) {
            $errors[] = "Password must be at least 8 characters long, contain at least one uppercase letter, and one number.";
        }
    }

    // If there are any errors, display them and do not proceed
    if (count($errors) > 0) {
        foreach ($errors as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
    } else {
        // Update statement
        $stmt = $conn->prepare("UPDATE records SET
            card_name = ?, cc_number = ?, cvv = ?, 
            gov_id = ?, gov_id_number = ?, gov_id_place = ?, gov_id_expiry = ?, 
            credentials = ?, online_email = ?, online_username = ?, online_password = ? 
            WHERE id = ?" . ($role !== 'admin' ? " AND user_id = ?" : ""));

        if ($role === 'admin') {
            $stmt->bind_param(
                "sssssssssssi",
                $card_name, $cc_number, $cvv,
                $gov_id, $gov_id_number, $gov_id_place, $gov_id_expiry,
                $credentials, $online_email, $online_username, $online_password,
                $id
            );
        } else {
            $stmt->bind_param(
                "ssssssssssssi",
                $card_name, $cc_number, $cvv,
                $gov_id, $gov_id_number, $gov_id_place, $gov_id_expiry,
                $credentials, $online_email, $online_username, $online_password,
                $id, $user_id
            );
        }

        if ($stmt->execute()) {
            header("Location: " . ($role === 'admin' ? "admin_dashboard.php" : "user_dashboard.php"));
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Record</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fa;
            color: #333;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            color: #4CAF50;
            margin: 20px 0;
        }

        .container {
            width: 50%;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-size: 1.1em;
            margin-bottom: 8px;
            display: block;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-top: 5px;
        }

        select {
            cursor: pointer;
        }

        .form-group button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 1.2em;
            width: 100%;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 20px;
        }

        .form-group button:hover {
            background-color: #45a049;
        }

        .back-link {
            display: inline-block;
            text-align: center;
            margin-top: 20px;
            font-size: 1.1em;
            text-decoration: none;
            color: #333;
            border: 1px solid #ccc;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .back-link:hover {
            background-color: #f1f1f1;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group input[type="date"]:focus,
        .form-group select:focus {
            border-color: #4CAF50;
            outline: none;
        }
    </style>
    <script>
        function showFields() {
            const type = document.getElementById("type").value;
            document.getElementById("cardFields").style.display = type === 'card' ? 'block' : 'none';
            document.getElementById("govIDField").style.display = type === 'gov_id' ? 'block' : 'none';
            document.getElementById("credentialsField").style.display = type === 'credentials' ? 'block' : 'none';
        }
    </script>
</head>
<body onload="showFields()">

<div class="container">
    <h1>Edit Record</h1>
    <form method="POST">
        <div class="form-group">
            <label for="type">Select Record Type:</label>
            <select name="type" id="type" onchange="showFields()" required>
                <option value="">-- Select --</option>
                <option value="card" <?= $record['card_name'] ? 'selected' : '' ?>>Card Name</option>
                <option value="gov_id" <?= $record['gov_id'] ? 'selected' : '' ?>>Government ID</option>
                <option value="credentials" <?= $record['credentials'] ? 'selected' : '' ?>>Online Credentials</option>
            </select>
        </div>

        <!-- Card Info -->
        <div id="cardFields" style="display:<?= $record['card_name'] ? 'block' : 'none' ?>;">
            <div class="form-group">
                <label>Card Name:</label>
                <input type="text" name="card_name" value="<?= htmlspecialchars($record['card_name']) ?>">
            </div>

            <div class="form-group">
                <label>Credit Card Number:</label>
                <input type="text" name="cc_number" value="<?= htmlspecialchars($record['cc_number']) ?>">
            </div>

            <div class="form-group">
                <label>CVV:</label>
                <input type="text" name="cvv" value="<?= htmlspecialchars($record['cvv']) ?>">
            </div>
        </div>

        <!-- Government ID Info -->
        <div id="govIDField" style="display:<?= $record['gov_id'] ? 'block' : 'none' ?>;">
            <div class="form-group">
                <label>Government ID:</label>
                <input type="text" name="gov_id" value="<?= htmlspecialchars($record['gov_id']) ?>">
            </div>

            <div class="form-group">
                <label>ID Number:</label>
                <input type="text" name="gov_id_number" value="<?= htmlspecialchars($record['gov_id_number']) ?>">
            </div>

            <div class="form-group">
                <label>Place of Issue:</label>
                <input type="text" name="gov_id_place" value="<?= htmlspecialchars($record['gov_id_place']) ?>">
            </div>

            <div class="form-group">
                <label>Expiry Date:</label>
                <input type="date" name="gov_id_expiry" value="<?= htmlspecialchars($record['gov_id_expiry']) ?>">
            </div>
        </div>

        <!-- Online Credentials Info -->
        <div id="credentialsField" style="display:<?= $record['credentials'] ? 'block' : 'none' ?>;">
            <div class="form-group">
                <label>Credentials:</label>
                <input type="text" name="credentials" value="<?= htmlspecialchars($record['credentials']) ?>">
            </div>

            <div class="form-group">
                <label>Online Email:</label>
                <input type="email" name="online_email" value="<?= htmlspecialchars($record['online_email']) ?>">
            </div>

            <div class="form-group">
                <label>Online Username:</label>
                <input type="text" name="online_username" value="<?= htmlspecialchars($record['online_username']) ?>">
            </div>

            <div class="form-group">
                <label>Online Password:</label>
                <input type="password" name="online_password" value="<?= htmlspecialchars($record['online_password']) ?>">
            </div>
        </div>

        <div class="form-group">
            <button type="submit">Update Record</button>
        </div>
    </form>
    <a href="<?= $role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php' ?>" class="back-link">Back to Dashboard</a>
</div>

</body>
</html>
