<?php
session_start();
require 'db.php';
require 'category.php';
require 'product.php';

// Create DB connection
$db = new Database("localhost", "bazar", "root", "");

// Create category and product objects
$category = new Category($db);
$product = new Product($db);



try {
    // Fetch categories for dropdown or listing
    $categories = $category->getAllCategories();

    if ($_SERVER['REQUEST_METHOD'] === "POST") {

        $sellerId = $_POST['seller'] ?? '';
        $seller_details = $product->getSellerDetails($sellerId);
        $seller_id = $seller_details['seller_id'];

        $categoryName = $_POST['category'] ?? '';
        $productName = $_POST['product'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $quantity = $_POST['quantity'] ?? '';
        $description = $_POST['description'] ?? '';
        $uploadedFiles = $_FILES['images'];

        $imageNames = [];
        $uploadDir = 'uploads/products/';

        foreach ($uploadedFiles['tmp_name'] as $index => $tmpName) {
            if ($uploadedFiles['error'][$index] === UPLOAD_ERR_OK) {
                $originalName = basename($uploadedFiles['name'][$index]);
                $newFileName = uniqid() . '_' . $originalName;
                $targetPath = $uploadDir . $newFileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $imageNames[] = $uploadDir . $newFileName;
                }
            }
        }

        $imagesCommaSeparated = implode(',', $imageNames);

        // Get category ID
        $categoryId = $category->getCategoryIdByName($categoryName);

        if (!$categoryId) {
            throw new Exception("Invalid category selected.");
        }

        // Add product
        $results = $product->addProduct(
            $seller_id,
            $categoryId,
            $productName,
            $amount,
            $quantity,
            $description,
            $imagesCommaSeparated
        );

        // GET NEW PRODUCT ID
        $product_id = $results['product_id'];

        // ------------------------------
        // INSERT PRODUCT VARIANTS
        // ------------------------------

        if (isset($_POST['variant_size']) && isset($_POST['variant_color']) && isset($_POST['variant_stock'])) {

            foreach ($_POST['variant_size'] as $index => $sizeId) {

                $colorId = $_POST['variant_color'][$index] ?? null;
                $stock = $_POST['variant_stock'][$index] ?? 0;

                // Insert variant row
                $db->query("
               INSERT INTO product_variants (product_id, size, color, stock, price)
                VALUES (?, ?, ?, ?, ?)
            ", [
                    $product_id,
                    !empty($sizeId) ? $sizeId : null,
                    !empty($colorId) ? $colorId : null,
                    $stock,
                    $amount  // same price as base product
                ]);
            }
        }

        foreach ($results as $msg) {
            echo "<script>console.log(" . json_encode($msg) . ");</script>";
        }
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
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
                            <label for="quantity">Total Quantity:</label>
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

                        <!-- NEW LOGIC STARTS HERE -->
                        <hr>
                        <h3>Product Variants</h3>

                        <div class="form-row">
                            <label>Has Size Options?</label>
                            <select id="hasSize" name="has_size">
                                <option value="no">No</option>
                                <option value="yes">Yes</option>
                            </select>
                        </div>

                        <div id="sizeOptions" style="display:none;">
                            <label>Select Available Sizes:</label><br>
                            <?php
                            $sizes = ["S", "M", "L", "XL", "XXL"];
                            foreach ($sizes as $s): ?>
                                <label><input type="checkbox" name="sizes[]" value="<?= $s ?>"> <?= $s ?></label>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-row">
                            <label>Has Color Options?</label>
                            <select id="hasColor" name="has_color">
                                <option value="no">No</option>
                                <option value="yes">Yes</option>
                            </select>
                        </div>

                        <div id="colorOptions" style="display:none;">
                            <label>Select Available Colors:</label><br>
                            <?php
                            $colors = ["Black", "White", "Blue", "Red", "Green"];
                            foreach ($colors as $c): ?>
                                <label><input type="checkbox" name="colors[]" value="<?= $c ?>"> <?= $c ?></label>
                            <?php endforeach; ?>
                        </div>

                        <div id="variantTable"></div>
                        <!-- NEW LOGIC ENDS HERE -->

                        <div class="form-row">
                            <input type="submit" value="Submit">
                        </div>

                    </form>
                </div>
            </div>
            <script>
                window.onload = function () {
                    // Popup message logic
                    var popup = document.getElementById('popupMessage');
                    if (popup) {
                        popup.classList.add('show');
                        setTimeout(function () {
                            popup.classList.remove('show');
                        }, 2000);
                    }

                    // Attach event listeners for Size and Color dropdowns
                    document.getElementById("hasSize").addEventListener("change", function () {
                        document.getElementById("sizeOptions").style.display = (this.value === "yes") ? "block" : "none";
                        generateVariantTable();
                    });

                    document.getElementById("hasColor").addEventListener("change", function () {
                        document.getElementById("colorOptions").style.display = (this.value === "yes") ? "block" : "none";
                        generateVariantTable();
                    });

                    // When seller selects size or color checkboxes
                    document.querySelectorAll("#sizeOptions input, #colorOptions input").forEach(chk => {
                        chk.addEventListener("change", generateVariantTable);
                    });
                };

                // Variant table generator
                function generateVariantTable() {
                    let sizeSelected = [...document.querySelectorAll("input[name='sizes[]']:checked")].map(i => i.value);
                    let colorSelected = [...document.querySelectorAll("input[name='colors[]']:checked")].map(i => i.value);

                    let tableHTML = "<h3>Stock per Variant</h3><table border='1' cellpadding='8'>";
                    tableHTML += "<tr><th>Size</th><th>Color</th><th>Stock</th></tr>";

                    if (sizeSelected.length === 0 && colorSelected.length === 0) {
                        document.getElementById("variantTable").innerHTML = "";
                        return;
                    }

                    if (sizeSelected.length === 0) sizeSelected = ["-"];
                    if (colorSelected.length === 0) colorSelected = ["-"];

                    sizeSelected.forEach(size => {
                        colorSelected.forEach(color => {
                            tableHTML += `
            <tr>
                <td>${size}</td>
                <td>${color}</td>
                <td>
                    <input type="hidden" name="variant_size[]" value="${size}">
                    <input type="hidden" name="variant_color[]" value="${color}">
                    <input type="number" name="variant_stock[]" required>
                </td>
            </tr>`;
                        });
                    });

                    tableHTML += "</table>";
                    document.getElementById("variantTable").innerHTML = tableHTML;
                }
            </script>

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


</body>

</html>