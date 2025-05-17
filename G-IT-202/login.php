<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else if ($user['role'] === 'user') {
            header("Location: user_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid credentials.";
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $new_username = $_POST['new_username'];
    $new_password = $_POST['new_password'];
    $role = 'user'; // default role for new accounts

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
    $stmt->bind_param("s", $new_username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $register_error = "Username already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $new_username, $new_password, $role);

        if ($stmt->execute()) {
            $register_success = "Account created successfully! You can now login.";
        } else {
            $register_error = "Failed to create account.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register</title>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(-45deg, #1e3c72, #2a5298, #6dd5ed, #2193b0);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            background: #ffffff;
            padding: 30px 25px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .input-group {
            display: flex;
            align-items: center;
            background: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
        }

        .input-group i {
            margin-right: 10px;
            color: #777;
        }

        .input-group input {
            border: none;
            background: transparent;
            outline: none;
            flex: 1;
            font-size: 16px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        button {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: none;
            background: #007BFF;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 10px;
        }

        button:hover {
            background: #0056b3;
        }

        .error, .success {
            text-align: center;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .error { color: #cc0000; }
        .success { color: #28a745; }

        .toggle-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #007BFF;
            cursor: pointer;
            font-size: 14px;
        }

        .toggle-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 id="form-title">Login</h2>

        <?php 
        if (!empty($error)) echo "<div class='error'>$error</div>"; 
        if (!empty($register_error)) echo "<div class='error'>$register_error</div>"; 
        if (!empty($register_success)) echo "<div class='success'>$register_success</div>"; 
        ?>

        <!-- Login Form -->
        <form method="POST" id="login-form" novalidate>
            <label for="username">Username:</label>
            <div class="input-group">
                <i class="fa fa-user"></i>
                <input type="text" name="username" id="username" placeholder="Enter your username" required pattern="^[A-Za-z0-9_]{3,15}$">
            </div>

            <label for="password">Password:</label>
            <div class="input-group">
                <i class="fa fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Enter your password" required minlength="6">
            </div>

            <button type="submit" name="login">Login</button>
        </form>

        <!-- Register Form -->
        <form method="POST" id="register-form" style="display: none;" novalidate>
            <label for="new_username">New Username:</label>
            <div class="input-group">
                <i class="fa fa-user-plus"></i>
                <input type="text" name="new_username" id="new_username" placeholder="Create a username" required pattern="^[A-Za-z0-9_]{3,15}$">
            </div>

            <label for="new_password">New Password:</label>
            <div class="input-group">
                <i class="fa fa-lock"></i>
                <input type="password" name="new_password" id="new_password" placeholder="Create a password" required minlength="6">
            </div>

            <button type="submit" name="register">Register</button>
        </form>

        <div class="toggle-link" onclick="toggleForms()">Don't have an account? Register</div>
    </div>

    <script>
        function toggleForms() {
            var loginForm = document.getElementById('login-form');
            var registerForm = document.getElementById('register-form');
            var title = document.getElementById('form-title');
            var toggleLink = document.querySelector('.toggle-link');

            if (loginForm.style.display === "none") {
                loginForm.style.display = "block";
                registerForm.style.display = "none";
                title.innerText = "Login";
                toggleLink.innerText = "Don't have an account? Register";
            } else {
                loginForm.style.display = "none";
                registerForm.style.display = "block";
                title.innerText = "Register";
                toggleLink.innerText = "Already have an account? Login";
            }
        }
    </script>
</body>
</html>
