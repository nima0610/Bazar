<?php
session_start();
require 'db.php';
try {
    $stmt = $pdo->query("SELECT category_id, category_name FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $sellerid = $_POST['seller'] ?? '';
    $categoryname = $_POST['category'] ?? '';

    //to get the category id from categories

    if ($categoryname) {
        $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $stmt->execute([$categoryname]);
        $categoryRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($categoryRow) {
            $categoryid = $categoryRow['category_id'];
        } else {
            die("Invalid category selected.");
        }
    } else {
        die("Category not provided.");
    }

    //continue

    $productname = $_POST['product'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $description = $_POST['description'] ?? '';
    $images = $_FILES['images'] ?? null;
    // Folder where images will be saved
    $uploadDir = 'uploads/products/';

    // Create folder if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if ($images) {
        foreach ($images['tmp_name'] as $index => $tmpName) {
            $fileName = basename($images['name'][$index]);

            // You may want to rename the file to avoid conflicts, e.g. add timestamp:
            $newFileName = time() . '_' . $fileName;

            $targetFilePath = $uploadDir . $newFileName;

            // Move the uploaded file to your target folder
            if (move_uploaded_file($tmpName, $targetFilePath)) {
                $imageupload = "Uploaded: " . htmlspecialchars($newFileName) . "<br>";

                $stmt = $pdo->prepare("INSERT INTO product (seller_id, category_id, product_name, product_amount, product_stock, description, product_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $sellerid,
                    $categoryid,
                    $productname,
                    $amount,
                    $quantity,
                    $description,
                    $targetFilePath // This is the image path to store
                ]);

                $database_saved_mesg = "Product saved to database.<br>";

                // Here you can save $targetFilePath to database as the image path
            } else {
                echo "Failed to upload: " . htmlspecialchars($fileName) . "<br>";
            }
        }
    }

}

?>



<!DOCTYPE HTML>
<html>

<head>
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="../seller/style.css">
    <style>
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }
    </style>
</head>

<body>

    <?php if (isset($database_saved_mesg)): ?>
        <div id="popupMessage" class="popup-message">
            <?= $database_saved_mesg ?>
        </div>
    <?php endif; ?>

    <div class="main_box">
        <div class="internal_box">
            <img src="../seller/img/bazari.png">
            <h2>Seller Dashboard</h2>
            <form method="POST" action="dashboard.php">
                <input type="text" name="searchbox" placeholder="🔍 Search for Products">
            </form>
            <p id="currentDate"></p>
        </div>
    </div>

    <div class="page_divider">
        <div class="left_divided">
            <button class="sidebar-btn" data-target="dashboard">Dashboards</button>
            <button class="sidebar-btn" data-target="orders">Orders</button>
            <button class="sidebar-btn" data-target="products">Products</button>
            <button class="sidebar-btn" data-target="sell">Sell Product</button>
            <button class="sidebar-btn" data-target="customers">Customers</button>
            <button class="sidebar-btn" data-target="profile">Profile</button>
        </div>

        <div class="main_part">
            <div id="dashboard" class="content-section active">
                <div class="grid-container dashboard-grid">
                    <!-- to put grid item in left side of upper part -->
                    <div class="grid-item"><img src="../img/chart1.webp"></div>
                    <!-- to put grid item in right side of upper part (4 images) -->
                    <div class="grid-item nested-grid-container">
                        <div class="nested-grid">
                            <div class="nested-item"><img src="../img/chart2.webp"></div>
                            <div class="nested-item"><img src="../img/chart3.jpg"></div>
                            <div class="nested-item"><img src="../img/chart4.jpg"></div>
                            <div class="nested-item"><img src="../img/chart5.jpg"></div>
                        </div>
                    </div>
                    <div class="grid-item"><img src="../img/chart6.jpg"></div>
                    <div class="grid-item"><img src="../img/chart7.jpg"></div>
                </div>
            </div>
            <div id="orders" class="content-section">
                <div class="grid-container orders-grid">
                    <div class="grid-item"><img src="../img/game.jpg" width="300">
                    </div>
                    <div class="grid-item"><img src="../img/game.jpg" width="300">
                    </div>
                    <div class="grid-item"><img src="../img/game.jpg" width="300">
                    </div>
                </div>
            </div>
            <!-- Add other sections if needed -->

            <div id="sell" class="content-section">
                <div class="form-container">
                    <form method="POST" action="dashboard.php" enctype="multipart/form-data">

                        <div class="form-row">
                            <label for="seller">Seller ID:</label>
                            <input type="number" name="seller" id="seller" required readonly
                                value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
                        </div>

                        <div class="form-row">
                            <label for="category">Select Category:</label>
                            <select name="category" id="category" required>
                                <option value="">--Select Category--</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['category_name']) ?>">
                                        <?= htmlspecialchars($category['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>



                        <div class="form-row">
                            <label for="product">Product Name:</label>
                            <input type="text" name="product" id="product" required>
                        </div>

                        <div class="form-row">
                            <label for="amount">Selling Amount:</label>
                            <input type="number" name="amount" id="amount" required>
                        </div>

                        <div class="form-row">
                            <label for="quantity">Quantity:</label>
                            <input type="number" name="quantity" id="quantity" required>
                        </div>



                        <div class="form-row">
                            <label for="description">Description:</label>
                            <input type="text" name="description" id="description" required>
                        </div>

                        <div class="form-row">
                            <label for="image">Product Image:</label>
                            <input type="file" accept="image/*" name="images[]" id="images" multiple required>
                        </div>



                        <div class="form-row">
                            <input type="submit" value="Submit">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.sidebar-btn').forEach(button => {
            button.addEventListener('click', () => {
                console.log("Button Clicked: " + button.innerText);
                console.log("Target Section: " + button.getAttribute('data-target'));

                // Hide all content sections
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.remove('active');
                });

                // Show the target section
                const target = button.getAttribute('data-target');
                const targetSection = document.getElementById(target);
                if (targetSection) {
                    targetSection.classList.add('active');
                } else {
                    console.warn("No section found with id: " + target);
                }
            });
        });
    </script>

    <script>
        // Date Script
        const today = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = today.toLocaleDateString(undefined, options);
        document.getElementById("currentDate").innerText = formattedDate;
    </script>


    <script>
        window.onload = function () {
            var popup = document.getElementById('popupMessage');
            if (popup) {
                popup.classList.add('show');
                setTimeout(function () {
                    popup.classList.remove('show');
                }, 2000); // 2 seconds
            }
        };
    </script>
</body>

</html>