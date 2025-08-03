<?php
require 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Hash password in PHP
    $hashedPassword = hash('sha256', $password);

    // Check if user already exists
    $checkStmt = $pdo->prepare("SELECT * FROM users WHERE username = :email");
    $checkStmt->execute(['email' => $email]);
    $existingUser = $checkStmt->fetch();

    if ($existingUser) {
        $error = "❌ User already exists.";
    } else {
        // Insert new user with hashed password
        $insertStmt = $pdo->prepare("INSERT INTO users (username, password, user_role) VALUES (:email, :password, :role)");
        $insertStmt->execute([
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ]);

        $success = "✅ Registration successful!";
    }
}

?>





<!DOCTYPE HTML>
<html>

<head>
    <title>REGISTRATION</title>
    <style>
        body {
            color: white;
            background-image: url('seller/img/bg.jpg');
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

        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }

        .success-message {
            color: lightgreen;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="register">

        <form method="POST" action="registration.php">
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

        <?php if (!empty($success)) { ?>
            <div class="success-message"><?php echo $success; ?></div>
            <script>
                setTimeout(function () {
                    window.location.href = 'login.php';
                }, 3000);
            </script>
        <?php } ?>
    </div>

</body>

</html>
</DOCTYPE>