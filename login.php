<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {
        // Fetch user by username and role
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :email AND user_role = :role");
        $stmt->execute(['email' => $email, 'role' => $role]);
        $user = $stmt->fetch();

        if ($user && hash('sha256', $password) === $user['password']) {
            // Login success
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['user_role'];

            // Redirect based on role
            if ($role === 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($role === 'seller') {
                header("Location: seller/dashboard.php");
            } elseif ($role === 'customer') {
                header("Location: customer/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid credentials or role.";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
        }

        form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        button {
            margin-top: 20px;
            width: 100%;
            padding: 10px;
            background: #f1b83bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #4d90e1ff;
        }

        .register-link {
            text-align: center;
            margin-top: 10px;
        }

        .register-link a {
            color: #a07120ff;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link a:hover {
            color: #4b3aa0ff;
        }

        .error {
            margin-top: 15px;
            color: red;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <form method="POST" action="login.php">
        <h2>Login</h2>

        <label for="role">Select Role:</label>
        <select id="role" name="role" required>
            <option value="" disabled selected>-- Select Role --</option>
            <option value="admin">Admin</option>
            <option value="seller">Seller</option>
            <option value="customer">Customer</option>
        </select>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="abc@example.com" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter password" required>

        <button type="submit">Login</button>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="register-link">
            Don't have an account? <a href="registration.php">Register</a>
        </div>
    </form>
</body>

</html>