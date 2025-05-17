<?php
session_start();
include 'config.php';

// Only admins here
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Build dropdown lists
$user_list = [];
$card_names = [];
$gov_types = [];
$credentials_list = [];

// Users dropdown
$user_query = "SELECT id, username FROM users ORDER BY username";
$user_result = $conn->query($user_query);
while ($u = $user_result->fetch_assoc()) {
    $user_list[$u['id']] = $u['username'];
}

// Card names, Gov ID types, Credentials dropdowns
$type_query = "SELECT DISTINCT card_name, gov_id, credentials FROM records";
$type_res = $conn->query($type_query);
while ($t = $type_res->fetch_assoc()) {
    if (!empty($t['card_name']) && !in_array($t['card_name'], $card_names)) {
        $card_names[] = $t['card_name'];
    }
    if (!empty($t['gov_id']) && !in_array($t['gov_id'], $gov_types)) {
        $gov_types[] = $t['gov_id'];
    }
    if (!empty($t['credentials']) && !in_array($t['credentials'], $credentials_list)) {
        $credentials_list[] = $t['credentials'];
    }
}

// Read filters
$filter_user = $_GET['user_id'] ?? '';
$filter_card = $_GET['card_name'] ?? '';
$filter_gov  = $_GET['gov_id'] ?? '';
$filter_cred = $_GET['credentials'] ?? '';

$show_results = ($filter_user || $filter_card || $filter_gov || $filter_cred);

// Build SQL
$sql = "SELECT records.*, users.username 
        FROM records
        JOIN users ON records.user_id = users.id
        WHERE 1=1";
$params = [];
$types  = "";

if ($filter_user) {
    $sql      .= " AND records.user_id = ?";
    $params[]  = $filter_user;
    $types    .= "i";
}
if ($filter_card) {
    $sql      .= " AND card_name = ?";
    $params[]  = $filter_card;
    $types    .= "s";
}
if ($filter_gov) {
    $sql      .= " AND gov_id = ?";
    $params[]  = $filter_gov;
    $types    .= "s";
}
if ($filter_cred) {
    $sql      .= " AND credentials = ?";
    $params[]  = $filter_cred;
    $types    .= "s";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f7fa;
      color: #333;
      padding: 40px;
    }
    .container {
      max-width: 1000px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1 {
      text-align: center;
      margin-bottom: 20px;
    }
    form {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 30px;
    }
    select, button.clear {
      padding: 8px 12px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }
    button.clear {
      background: #e0e0e0;
      cursor: pointer;
    }
    button.clear:hover {
      background: #d5d5d5;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    thead {
      background: #fafafa;
    }
    th, td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    tbody tr:hover {
      background: #fbfbfb;
    }
    .badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
      color: #fff;
    }
    .badge-card { background: #4caf50; }
    .badge-gov  { background: #2196f3; }
    .badge-cred { background: #ff9800; }
    .actions a {
      color: #3498db;
      text-decoration: none;
      margin-right: 10px;
    }
    .actions a:hover {
      text-decoration: underline;
    }
    .footer-buttons {
      margin-top: 30px;
      text-align: center;
    }
    .footer-buttons a {
      display: inline-block;
      margin: 0 10px;
      padding: 10px 20px;
      background: #3498db;
      color: #fff;
      border-radius: 5px;
      text-decoration: none;
      transition: background 0.3s;
    }
    .footer-buttons a:hover {
      background: #2980b9;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Welcome, Admin!</h1>

    <form method="GET" oninput="manageDropdowns()">
      <select id="user_id" name="user_id" onchange="this.form.submit()">
        <option value="">-- All Users --</option>
        <?php foreach ($user_list as $uid => $uname): ?>
          <option value="<?= $uid ?>" <?= $filter_user == $uid ? 'selected' : '' ?>>
            <?= htmlspecialchars($uname) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select id="card_name" name="card_name" onchange="this.form.submit()">
        <option value="">-- All Card Types --</option>
        <?php foreach ($card_names as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $filter_card === $c ? 'selected' : '' ?>>
            <?= htmlspecialchars($c) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select id="gov_id" name="gov_id" onchange="this.form.submit()">
        <option value="">-- All ID Types --</option>
        <?php foreach ($gov_types as $g): ?>
          <option value="<?= htmlspecialchars($g) ?>" <?= $filter_gov === $g ? 'selected' : '' ?>>
            <?= htmlspecialchars($g) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select id="credentials" name="credentials" onchange="this.form.submit()">
        <option value="">-- All Credentials --</option>
        <?php foreach ($credentials_list as $cr): ?>
          <option value="<?= htmlspecialchars($cr) ?>" <?= $filter_cred === $cr ? 'selected' : '' ?>>
            <?= htmlspecialchars($cr) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="button" class="clear" onclick="window.location.href='admin_dashboard.php'">
        Clear Filters
      </button>
    </form>

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
          <?php 
            // Determine category for this row
            if (!empty($row['card_name'])) {
              $cat = 'CARD'; $cls = 'badge-card';
            } elseif (!empty($row['gov_id'])) {
              $cat = 'GOV ID'; $cls = 'badge-gov';
            } else {
              $cat = 'CREDENTIAL'; $cls = 'badge-cred';
            }
          ?>
          <tr>
            <td><span class="badge <?= $cls ?>"><?= $cat ?></span></td>
            <td>
              <strong>User:</strong> <?= htmlspecialchars($row['username']) ?><br>
              <?php if ($cat === 'CARD'): ?>
                <strong>Card Name:</strong> <?= htmlspecialchars($row['card_name']) ?><br>
                <strong>CC #:</strong> <?= htmlspecialchars($row['cc_number']) ?><br>
                <strong>CVV:</strong> <?= htmlspecialchars($row['cvv']) ?>
              <?php elseif ($cat === 'GOV ID'): ?>
                <strong>ID Name:</strong> <?= htmlspecialchars($row['gov_id']) ?><br>
                <strong>ID #:</strong> <?= htmlspecialchars($row['gov_id_number']) ?><br>
                <strong>Place:</strong> <?= htmlspecialchars($row['gov_id_place']) ?><br>
                <strong>Expires:</strong> <?= htmlspecialchars($row['gov_id_expiry']) ?>
              <?php else: ?>
                <strong>Description:</strong> <?= htmlspecialchars($row['credentials']) ?><br>
                <strong>Email:</strong> <?= htmlspecialchars($row['online_email']) ?><br>
                <strong>Username:</strong> <?= htmlspecialchars($row['online_username']) ?><br>
                <strong>Password:</strong> <?= htmlspecialchars($row['online_password']) ?>
              <?php endif; ?>
            </td>
            <td class="actions">
              <a href="edit.php?id=<?= $row['id'] ?>">Edit</a>
              <a href="javascript:void(0)" onclick="confirmDelete(<?= $row['id'] ?>)">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
        <?php if ($result->num_rows === 0): ?>
          <tr>
            <td colspan="3" style="text-align:center; padding:20px;">
              No records found.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="footer-buttons">
      <a href="add.php">Add New Record</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <script>
    function manageDropdowns() {
      const ids = ["user_id","card_name","gov_id","credentials"];
      const sel = ids.find(id => document.getElementById(id).value!=="");
      ids.forEach(id => {
        document.getElementById(id).disabled = sel && !document.getElementById(id).value;
      });
    }
    function confirmDelete(id) {
      if (confirm("Are you sure you want to delete this record?")) {
        window.location.href = "delete.php?id=" + id;
      }
    }
    window.onload = manageDropdowns;
  </script>
</body>
</html>
