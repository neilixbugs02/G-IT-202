<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT * FROM records WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if ($filter === 'card') {
    $sql .= " AND card_name != ''";
} elseif ($filter === 'gov_id') {
    $sql .= " AND gov_id != ''";
} elseif ($filter === 'credentials') {
    $sql .= " AND (online_email != '' OR online_username != '' OR online_password != '')";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
      body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f0f2f5;
        margin: 0;
        padding: 20px;
      }

      .container {
        max-width: 1200px;
        margin: auto;
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      }

      h2, h3 {
        text-align: center;
        color: #333;
      }

      form {
        margin-bottom: 20px;
        text-align: center;
      }

      select {
        padding: 8px 12px;
        border-radius: 5px;
        border: 1px solid #ccc;
      }

      table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
      }

      th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
      }

      th {
        background-color: #f8f9fa;
      }

      tr:hover {
        background-color: #f1f1f1;
      }

      .actions a {
        text-decoration: none;
        margin: 0 5px;
        color: #007BFF;
      }

      .actions a:hover {
        text-decoration: underline;
      }

      .btn-link {
        display: inline-block;
        margin-top: 20px;
        text-align: center;
      }

      .btn-link a {
        margin: 0 10px;
        text-decoration: none;
        background: #007BFF;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
      }

      .btn-link a:hover {
        background: #0056b3;
      }

      /* Category Badge Styles */
      .badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 12px;
        font-size: 12px;
        color: white;
        font-weight: bold;
      }

      .badge-card {
        background-color: #4caf50; /* Green */
      }

      .badge-gov {
        background-color: #2196f3; /* Blue */
      }

      .badge-cred {
        background-color: #ff9800; /* Orange */
      }
    </style>
</head>
<body>
<div class="container">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>

    <form method="GET" action="user_dashboard.php">
        <label for="filter">Filter by:</label>
        <select name="filter" id="filter" onchange="this.form.submit()">
            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="card" <?= $filter === 'card' ? 'selected' : '' ?>>Card Name</option>
            <option value="gov_id" <?= $filter === 'gov_id' ? 'selected' : '' ?>>Government ID</option>
            <option value="credentials" <?= $filter === 'credentials' ? 'selected' : '' ?>>Online Credentials</option>
        </select>
    </form>

    <h3>Your Records (<?= ucfirst($filter) ?>)</h3>

    <table>
        <thead>
        <tr>
            <th>Category</th>
            <th>Details</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php if (!empty($row['card_name'])): ?>
                <tr>
                    <td><span class="badge badge-card">CARD</span></td>
                    <td>
                        <strong>Card Name:</strong> <?= htmlspecialchars($row['card_name']) ?><br>
                        <strong>CC Number:</strong> <?= htmlspecialchars($row['cc_number']) ?><br>
                        <strong>CVV:</strong> <?= htmlspecialchars($row['cvv']) ?>
                    </td>
                    <td class="actions">
                        <a href="edit.php?id=<?= $row['id'] ?>">Edit</a> |
                        <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($row['gov_id'])): ?>
                <tr>
                    <td><span class="badge badge-gov">GOV ID</span></td>
                    <td>
                        <strong>ID Name:</strong> <?= htmlspecialchars($row['gov_id']) ?><br>
                        <strong>ID Number:</strong> <?= htmlspecialchars($row['gov_id_number']) ?><br>
                        <strong>Place of Issue:</strong> <?= htmlspecialchars($row['gov_id_place']) ?><br>
                        <strong>Expiry:</strong> <?= htmlspecialchars($row['gov_id_expiry']) ?>
                    </td>
                    <td class="actions">
                        <a href="edit.php?id=<?= $row['id'] ?>">Edit</a> |
                        <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($row['online_email']) || !empty($row['online_username']) || !empty($row['online_password'])): ?>
                <tr>
                    <td><span class="badge badge-cred">CREDENTIAL</span></td>
                    <td>
                        <strong>Description:</strong> <?= htmlspecialchars($row['credentials']) ?><br>
                        <strong>Email:</strong> <?= htmlspecialchars($row['online_email']) ?><br>
                        <strong>Username:</strong> <?= htmlspecialchars($row['online_username']) ?><br>
                        <strong>Password:</strong> <?= htmlspecialchars($row['online_password']) ?>
                    </td>
                    <td class="actions">
                        <a href="edit.php?id=<?= $row['id'] ?>">Edit</a> |
                        <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="btn-link">
        <a href="add.php">Add New Record</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
</body>
</html>
