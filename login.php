<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    $hashedPassword = hash('sha256', $password);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :email AND password = :password AND user_role = :role");
    $stmt->execute([
        'email' => $email,
        'password' => $hashedPassword,
        'role' => $role
    ]);

    $user = $stmt->fetch();

    if ($user) {
        $success = "✅ Login successful. Welcome, " . htmlspecialchars($user['username']);
        $user_role = $role;
        $_SESSION['user_id'] = $user['user_id'];  // or your primary key column
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['user_role'];
    } else {
        $error = "❌ Invalid email or password or role.";
    }
}
?>




<!DOCTYPE HTML>
<html>

<head>
    <title>LOGIN PAGE</title>
    <style>
        body {
            color: white;
            background-image: url('img/bg.jpg');
            background-size: cover;
            /* makes it fill the screen */
            background-repeat: no-repeat;
            /* prevents repeating */
            background-position: center;
            /* centers the image */
            display: flex;
            justify-content: center;
            /*This centers the child elements horizontally within the body*/
            height: 100vh;
            /* required to horizontally center because without this it wont know height. */
            align-items: center;

        }

        form {
            position: relative;
            background-color: ;
            width: 300px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 5px;
            text-align: center;
        }

        input {
            width: 94%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        #role {
            position: relative;
            left: 20%;

            justify-content: center;
            text-align: center;
            text-align-last: center;
            width: 60%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 3px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }
    </style>
</head>

<body>
    <div class="register">

        <form method="POST" action="login.php">
            <h1>
                LOGIN<br>
            </h1>
            <label for="role">Select Role:</label>
            <select name="role" id="role">
                <option value="admin">Admin</option>
                <option value="seller">Seller</option>
                <option value="customer">Customer</option>
            </select>
            <label>
                Email :
            </label>
            <input type="email" name="email" placeholder="abc@xyz"><br>
            <label>
                Password :
            </label>
            <input type="password" name="password"><br>
            <input type="submit" value="SUBMIT">
            <?php if (!empty($error)) { ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php } ?>
        </form>

        <?php if (!empty($success)) {
            echo "<p style='color: lightgreen;'>$success</p>";
            // Role-based redirection after 3 seconds
            if ($user_role === 'admin') {
                $redirectPage = 'admin.php';
            } elseif ($user_role === 'seller') {
                $redirectPage = 'seller/dashboard.php';
            } elseif ($user_role === 'customer') {
                $redirectPage = 'customer.php';
            } else {
                $redirectPage = 'index.php'; // fallback page
            }
            echo "
    <script>
        setTimeout(function() {
            window.location.href = '$redirectPage';
        }, 3000);
    </script>";
        } ?>
    </div>
    <?php if ($_SERVER["REQUEST_METHOD"] === "POST") { ?>
        <script>
            console.log("Email: <?php echo $email; ?>");
            console.log("Password: <?php echo $password; ?>");
            console.log("Role: <?php echo $role; ?>");
        </script>
    <?php } ?>
</body>

</html>
</DOCTYPE>