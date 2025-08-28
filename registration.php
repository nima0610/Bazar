<?php
require 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $hashedPassword = hash('sha256', $password);

    $checkStmt = $pdo->prepare("SELECT * FROM users WHERE username = :email");
    $checkStmt->execute([':email' => $email]);

    if ($checkStmt->fetch()) {
        $error = "❌ User already exists.";
    } else {
        $insertUserStmt = $pdo->prepare("INSERT INTO users (username, password, user_role) VALUES (:email, :password, :role)");
        $insertUserStmt->execute([
            ':email' => $email,
            ':password' => $hashedPassword,
            ':role' => $role
        ]);
        $userId = $pdo->lastInsertId();

        if ($role === "Admin") {
            $admin_contact = $_POST['admin_contact'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO admins (user_id, phone_number) VALUES (:user_id, :phone_number)");
            $stmt->execute([
                ':user_id' => $userId,
                ':phone_number' => $admin_contact
            ]);
        } elseif ($role === "Seller") {
            $shop_name = $_POST['shop_name'] ?? '';
            $shop_location = $_POST['shop_location'] ?? '';
            $phone_number = $_POST['phone_number'] ?? '';

            //Handle QR upload
            $qr_code = null;
            if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = "uploads/qr_codes/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 077, true);
                }

                $fileTmpPath = $_FILES['qr_code']['tmp_name'];
                $fileName = time() . "_" . basename($_FILES['qr_code']['name']);
                $destPath = $uploadDir . $fileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $qr_code = $destPath; //Save path into DB
                }

            }

            $stmt = $pdo->prepare("INSERT INTO seller (user_id, shop_name, shop_location, phone_number, qr_code) VALUES (:user_id, :shop_name, :shop_location, :phone_number, :qr_code)");
            $stmt->execute([
                ':user_id' => $userId,
                ':shop_name' => $shop_name,
                ':shop_location' => $shop_location,
                ':phone_number' => $phone_number,
                ':qr_code' => $qr_code
            ]);

        } elseif ($role === "Customer") {
            $full_name = $_POST['full_name'] ?? '';
            $district = $_POST['district'] ?? '';
            $location = $_POST['location'] ?? '';
            $phone_number = $_POST['phone_numberrr'] ?? '';

            $stmt = $pdo->prepare("INSERT INTO customers (user_id, full_name, district_id, location_id, phone_number)
                                   VALUES (:user_id, :full_name, :district_id, :location_id, :phone_number)");
            $stmt->execute([
                ':user_id' => $userId,
                ':full_name' => $full_name,
                ':district_id' => $district,
                ':location_id' => $location,
                ':phone_number' => $phone_number
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

    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
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
        <div class="left-side">
            <img src="assets/bazari.png" alt="Logo" class="logo-img">
        </div>

        <div class="right-side">
            <form method="POST" action="registration.php" enctype="multipart/form-data">
                <h1>Registration</h1>

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

                <!-- Admin Fields -->
                <div class="role-field" id="admin-fields" style="display: none;">
                    <label>Phone Number:</label>
                    <input type="text" name="admin_contact" pattern="[0-9]{10}" placeholder="10-Digit Number" required>
                </div>

                <!-- Seller Fields -->
                <div class="role-field" id="seller-fields" style="display: none;">
                    <label>Shop Name:</label>
                    <input type="text" name="shop_name" placeholder="Enter your shop name" required>

                    <label>Shop Address:</label>
                    <input type="text" name="shop_location" placeholder="Enter your Shop Address" required>

                    <label>Contact No:</label>
                    <input type="tel" name="phone_number" pattern="[0-9]{10}" placeholder="10-digit number" required>

                    <label>Upload QR Code:</label>
                    <input type="file" name="qr_code" accept="image/*" required>
                </div>

                <!-- Customer Fields -->
                <div class="role-field" id="customer-fields" style="display: none;">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" placeholder="Enter your full name" required>

                    <label for="district">District</label>
                    <select name="district" id="district" required>
                        <option value="">-- Select District --</option>
                        <?php
                        $districts = $pdo->query("SELECT * FROM districts ORDER BY name ASC")->fetchAll();
                        foreach ($districts as $d) {
                            echo "<option value='{$d['id']}'>{$d['name']}</option>";
                        }
                        ?>
                    </select>

                    <label for="location">Location</label>
                    <select name="location" id="location" required>
                        <option value="">-- Select Location --</option>
                    </select>

                    <label>Contact No:</label>
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
                    setTimeout(function () { window.location.href = 'login.php'; }, 3000);
                </script>
            <?php } ?>
        </div>
    </div>

    <!-- Include jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        function toggleRoleFields() {
            const role = document.getElementById("role").value;
            const adminFields = document.getElementById("admin-fields");
            const sellerFields = document.getElementById("seller-fields");
            const customerFields = document.getElementById("customer-fields");

            adminFields.style.display = "none";
            sellerFields.style.display = "none";
            customerFields.style.display = "none";

            if (role === "Admin") adminFields.style.display = "block";
            if (role === "Seller") sellerFields.style.display = "block";
            if (role === "Customer") customerFields.style.display = "block";
        }

        window.onload = toggleRoleFields;

        // Initialize Select2 for Location
        $(document).ready(function () {
            $('#location').select2({
                placeholder: "-- Search your Location --",
                allowClear: true
            });
        });

        // AJAX to load locations dynamically
        $('#district').on('change', function () {
            const districtId = $(this).val();
            $('#location').empty().append('<option value="">-- Select Location --</option>');

            if (districtId) {
                $.ajax({
                    url: 'get_locations.php',
                    type: 'GET',
                    data: { district_id: districtId },
                    dataType: 'json',
                    success: function (data) {
                        data.forEach(function (loc) {
                            const newOption = new Option(loc.name, loc.id, false, false);
                            $('#location').append(newOption).trigger('change');
                        });
                    }
                });
            }
        });
    </script>
</body>

</html>