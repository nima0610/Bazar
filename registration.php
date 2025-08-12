<?php
require 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect inputs...
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $hashedPassword = hash('sha256', $password);

    // Check if user exists
    $checkStmt = $pdo->prepare("SELECT * FROM users WHERE username = :email");
    $checkStmt->execute(['email' => $email]);
    if ($checkStmt->fetch()) {
        $error = "❌ User already exists.";
    } else {
        // Insert into users table
        $insertUserStmt = $pdo->prepare("INSERT INTO users (username, password, user_role) VALUES (:email, :password, :role)");
        $insertUserStmt->execute([
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ]);
        $userId = $pdo->lastInsertId(); // Get new user's ID to store in seller or admin or customer for storing as foreign key
        //this loc automatically asks the database for the last inserted data and chooses last used table
        //with autoincreement part existing
        // Insert into role-specific table
        if ($role === "Admin") {
            $admin_code = $_POST['admin_contact'] ?? '';


            $stmt = $pdo->prepare("INSERT INTO admins (user_id, phone_number) VALUES (:user_id, :admin_code)");
            $stmt->execute([
                'user_id' => $userId,
                'admin_code' => $admin_code,
            ]);
        } elseif ($role === "Seller") {
            $shop_name = $_POST['shop_name'] ?? '';
            $shop_location = $_POST['shop_location'] ?? '';
            $phone_number = $_POST['phone_number'] ?? '';
            $emaill = $_POST['email'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO seller (name, contact, address, email, user_id) VALUES (:name, :contact, :address, :email, :userid)");
            $stmt->execute([
                'name' => $shop_name,
                'address' => $shop_location,
                'contact' => $phone_number,
                'email' => $emaill,
                'userid' => $userId

            ]);
            echo "<script>console.log('{$shop_name}');</script>";
            echo "<script>console.log('{$phone_number}');</script>";
        } elseif ($role === "Customer") {
            $full_name = $_POST['full_name'] ?? '';
            $address = $_POST['address'] ?? '';
            $phone_number = $_POST['phone_numberrr'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO customers (user_id, full_name, address, phone_number) VALUES (:user_id, :full_name, :address, :phone_number)");
            $stmt->execute([
                'user_id' => $userId,
                'full_name' => $full_name,
                'address' => $address,
                'phone_number' => $phone_number
            ]);

        }

        $success = "✅ Registration successful!";
    }
}

?>





<!DOCTYPE HTML>
<html>

<head>
    <title>REGISTRATION</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body,
        html {
            height: 100%;
            font-family: Arial, sans-serif;
        }

        .register {
            display: flex;
            height: 100vh;
        }

        .left-side {
            width: 50%;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('seller/img/bg.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .left-side img {
            max-width: 300px;
            width: 80%;
        }

        .right-side {
            width: 50%;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        form {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 400px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2)
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        input[type="submit"] {
            background-color: #c38c34ff;
            color: white;
            border: none;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #7267aaff;
        }

        .login-link {
            text-align: center;
            margin-top: 10px;
        }

        .login-link a {
            color: #403288ff;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            color: #977c26ff;
        }



        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }

        .success-message {
            color: green;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="register">
        <!-- left side : logo -->
        <div class="left-side">
            <img src="assets/bazari.png" alt="Logo" class="logo-img">
        </div>

        <!-- Right side: registration form-->
        <div class="right-side">
            <form method="POST" action="registration.php">
                <h1>Registration<br></h1>

                <label for="role">Select Role:</label>
                <select name="role" id="role" onchange="toggleRoleFields()">
                    <option value="Admin">Admin</option>
                    <option value="Seller">Seller</option>
                    <option value="Customer">Customer</option>
                </select>

                <label>Email :</label>
                <input type="email" name="email" placeholder="abc@xyz" required><br>

                <label>Password :</label>
                <input type="password" name="password" required><br>

                <!--Admin-only-filed-->
                <div class="role-field" id="admin-fields" style="display: none;">
                    <label>Phone Number:</label>
                    <input type="text" name="admin_contact" pattern="[0-9]{10}" placeholder="10-Digit Number" required>
                </div>

                <!--Seller-only-Fileds -->
                <div class="role-field" id="seller-fields" style="display: none;">
                    <label>Shop Name:</label>
                    <input type="text" name="shop_name" placeholder="Enter your shop name" required>

                    <label>Shop Address:</label>
                    <input type="text" name="shop_location" placeholder="Enter your Shop Address" required>

                    <label for="phone_number">Contact No:</label>
                    <input type="tel" name="phone_number" pattern="[0-9]{10}" placeholder="10-digit number" required>

                </div>

                <!--Customer-only-fields-->
                <div class="role-field" id="customer-fields" style="display: none;">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" placeholder="Enter your full name" required>

                    <label>Address:</label>
                    <input type="text" name="address" placeholder="Enter your Address" required>

                    <label for="phone_number">Contact No:</label>
                    <input type="text" name="phone_numberrr" pattern="[0-9]{10}" placeholder="10-digit number" required>
                </div>


                <input type="submit" value="REGISTER">

                <?php if (!empty($error)) { ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php } ?>

                <div class="login-link">
                    Already have an account? <a href="login.php">Login</a>
                </div>
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
    </div>
    <script>
        function toggleRoleFields() {
            const role = document.getElementById("role").value;

            const adminFields = document.getElementById("admin-fields");
            const sellerFields = document.getElementById("seller-fields");
            const customerFields = document.getElementById("customer-fields");

            // Hide all first
            adminFields.style.display = "none";
            sellerFields.style.display = "none";
            customerFields.style.display = "none";

            // Remove required from all inputs
            [...adminFields.querySelectorAll("input")].forEach(input => input.required = false);
            [...sellerFields.querySelectorAll("input")].forEach(input => input.required = false);
            [...customerFields.querySelectorAll("input")].forEach(input => input.required = false);

            // Show and add required to relevant section
            if (role === "Admin") {
                adminFields.style.display = "block";
                [...adminFields.querySelectorAll("input")].forEach(input => input.required = true);
            } else if (role === "Seller") {
                sellerFields.style.display = "block";
                [...sellerFields.querySelectorAll("input")].forEach(input => input.required = true);
            } else if (role === "Customer") {
                customerFields.style.display = "block";
                [...customerFields.querySelectorAll("input")].forEach(input => input.required = true);
            }
        }

        //Call it once to set initial visibility when page loads
        window.onload = toggleRoleFields;
    </script>
</body>

</html>
</DOCTYPE>