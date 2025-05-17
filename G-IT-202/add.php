<?php 
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];

    // Initialize all fields to null
    $card_name = $cc_number = $cvv = null;
    $gov_id = $gov_id_number = $gov_id_place = $gov_id_expiry = null;
    $credentials = $online_email = $online_username = $online_password = null;

    // Validation based on selected type
    if ($type === 'card') {
        $card_name = trim($_POST['card_name'] ?? '');
        $cc_number = trim($_POST['cc_number'] ?? '');
        $cvv = trim($_POST['cvv'] ?? '');

        if (empty($card_name) || empty($cc_number) || empty($cvv)) {
            $error = "Please fill in all Card details.";
        } elseif (!preg_match('/^\d{16}$/', $cc_number)) {
            $error = "Credit card number must be 16 digits.";
        } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
            $error = "CVV must be 3 or 4 digits.";
        }

    } elseif ($type === 'gov_id') {
        $gov_id = trim($_POST['gov_id'] ?? '');
        $gov_id_number = trim($_POST['gov_id_number'] ?? '');
        $gov_id_place = trim($_POST['gov_id_place'] ?? '');
        $gov_id_expiry = trim($_POST['gov_id_expiry'] ?? '');

        if (empty($gov_id) || empty($gov_id_number) || empty($gov_id_place) || empty($gov_id_expiry)) {
            $error = "Please fill in all Government ID details.";
        } elseif (!preg_match('/^[\w\-\/]{3,30}$/', $gov_id_number)) {
            $error = "Invalid Government ID Number format.";
        } elseif (strtotime($gov_id_expiry) < time()) {
            $error = "Expiration date cannot be in the past.";
        }

    } elseif ($type === 'credentials') {
        $credentials = trim($_POST['credentials'] ?? '');
        $online_email = trim($_POST['online_email'] ?? '');
        $online_username = trim($_POST['online_username'] ?? '');
        $online_password = trim($_POST['online_password'] ?? '');

        if (empty($credentials) || empty($online_email) || empty($online_username) || empty($online_password)) {
            $error = "Please fill in all Online Credentials details.";
        } elseif (!filter_var($online_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (strlen($online_password) < 6) {
            $error = "Password should be at least 6 characters.";
        }

    } else {
        $error = "Please select a record type.";
    }

    // If no validation errors, insert record
    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO records (
            user_id, card_name, cc_number, cvv,
            gov_id, gov_id_number, gov_id_place, gov_id_expiry,
            credentials, online_email, online_username, online_password
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "isssssssssss",
            $user_id, $card_name, $cc_number, $cvv,
            $gov_id, $gov_id_number, $gov_id_place, $gov_id_expiry,
            $credentials, $online_email, $online_username, $online_password
        );

        if ($stmt->execute()) {
            $success = "Record added successfully!";
            $_POST = []; // clear fields
        } else {
            $error = "Error adding record: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Record</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            background: white;
            margin: 50px auto;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
            color: #555;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="submit"] {
            margin-top: 25px;
            width: 100%;
            background-color: #007BFF;
            border: none;
            color: white;
            padding: 12px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }

        .success {
            color: green;
            margin-bottom: 15px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .hidden {
            display: none;
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
        <h1>Add New Record</h1>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="type">Select Record Type:</label>
            <select name="type" id="type" onchange="showFields()" required>
                <option value="">-- Select --</option>
                <option value="card" <?= isset($_POST['type']) && $_POST['type'] === 'card' ? 'selected' : '' ?>>Card Name</option>
                <option value="gov_id" <?= isset($_POST['type']) && $_POST['type'] === 'gov_id' ? 'selected' : '' ?>>Government ID</option>
                <option value="credentials" <?= isset($_POST['type']) && $_POST['type'] === 'credentials' ? 'selected' : '' ?>>Online Credentials</option>
            </select>

            <!-- Card Info -->
            <div id="cardFields" class="form-group hidden">
                <label>Card Name:</label>
                <input type="text" name="card_name" value="<?= $_POST['card_name'] ?? '' ?>">

                <label>Credit Card Number:</label>
                <input type="text" name="cc_number" maxlength="16" value="<?= $_POST['cc_number'] ?? '' ?>">

                <label>CVV:</label>
                <input type="text" name="cvv" maxlength="4" value="<?= $_POST['cvv'] ?? '' ?>">
            </div>

            <!-- Government ID Info -->
            <div id="govIDField" class="form-group hidden">
                <label>Government ID:</label>
                <input type="text" name="gov_id" value="<?= $_POST['gov_id'] ?? '' ?>">

                <label>ID Number:</label>
                <input type="text" name="gov_id_number" value="<?= $_POST['gov_id_number'] ?? '' ?>">

                <label>Place of Issue:</label>
                <input type="text" name="gov_id_place" value="<?= $_POST['gov_id_place'] ?? '' ?>">

                <label>Expiration Date:</label>
                <input type="date" name="gov_id_expiry" value="<?= $_POST['gov_id_expiry'] ?? '' ?>">
            </div>

            <!-- Online Credentials -->
            <div id="credentialsField" class="form-group hidden">
                <label>Credential Description:</label>
                <input type="text" name="credentials" value="<?= $_POST['credentials'] ?? '' ?>">

                <label>Email Address:</label>
                <input type="email" name="online_email" value="<?= $_POST['online_email'] ?? '' ?>">

                <label>Username:</label>
                <input type="text" name="online_username" value="<?= $_POST['online_username'] ?? '' ?>">

                <label>Password:</label>
                <input type="password" name="online_password" value="<?= $_POST['online_password'] ?? '' ?>">
            </div>

            <input type="submit" value="Add Record">
        </form>

        <a href="<?= $role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php' ?>">Back to Dashboard</a>
    </div>
</body>
</html>
