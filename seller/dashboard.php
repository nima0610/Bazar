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


// --- Sales Chart Data (Last 7 Days or 30 Days) ---
$sellerId = $_SESSION['user_id'];

$selleris = $product->getSellerMu($sellerId);
$muji = $selleris['seller_id'];

echo "<script>console.log('muji: " . $muji . "');</script>";
// Fetch last 30 days of orders
$stmt = $db->getConnection()->prepare("
    SELECT DATE(delivered_at) AS order_date, SUM(cost) AS total_sales
    FROM purchase_history
    WHERE seller_id = :seller_id
    AND status = 'delivered'
    AND delivered_at >= DATE(NOW()) - INTERVAL 30 DAY
    GROUP BY DATE(delivered_at)
    ORDER BY order_date ASC
");
$stmt->execute(['seller_id' => $muji]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Prepare arrays
$dates = [];
$sales = [];

for ($i = 30; $i >= 0; $i--) {
    $date = date("Y-m-d", strtotime("-$i day"));
    $dates[] = $date;

    // Find sale for this date
    $found = false;
    foreach ($results as $row) {
        if ($row['order_date'] == $date) {
            $sales[] = (int) $row['total_sales'];
            $found = true;
            break;
        }
    }
    if (!$found)
        $sales[] = 0; // No sales this day
}


$seller_id = $_SESSION['user_id'];

// Current Orders: placed / confirmed / packed
$stmtCurrent = $db->getConnection()->prepare("
    SELECT ph.*, p.product_name, p.product_image, u.username
    FROM purchase_history ph
    JOIN product p ON ph.product_id = p.product_id
    JOIN users u ON ph.user_id = u.user_id
    WHERE ph.seller_id = :seller_id
    AND ph.status = 'placed'
    ORDER BY ph.id DESC
");
$stmtCurrent->execute(['seller_id' => $muji]);
$currentOrders = $stmtCurrent->fetchAll(PDO::FETCH_ASSOC);

// Shipped Orders
$stmtShipped = $db->getConnection()->prepare("
    SELECT ph.*, p.product_name, p.product_image, u.username
    FROM purchase_history ph
    JOIN product p ON ph.product_id = p.product_id
    JOIN users u ON ph.user_id = u.user_id
    WHERE ph.seller_id = :seller_id
    AND ph.status = 'shipped'
    ORDER BY ph.id DESC
");
$stmtShipped->execute(['seller_id' => $muji]);
$shippedOrders = $stmtShipped->fetchAll(PDO::FETCH_ASSOC);

// Completed Orders
$stmtCompleted = $db->getConnection()->prepare("
    SELECT ph.*, p.product_name, p.product_image, u.username
    FROM purchase_history ph
    JOIN product p ON ph.product_id = p.product_id
    JOIN users u ON ph.user_id = u.user_id
    WHERE ph.seller_id = :seller_id
    AND ph.status = 'delivered'
    ORDER BY ph.id DESC
");
$stmtCompleted->execute(['seller_id' => $muji]);
$completedOrders = $stmtCompleted->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['update_status_btn'])) {
    $orderId = $_POST['update_order_id'];
    $newStatus = $_POST['new_status'];

    $timestampField = '';
    if ($newStatus === 'shipped')
        $timestampField = ', shipped_at = NOW()';
    if ($newStatus === 'delivered')
        $timestampField = ', delivered_at = NOW()';

    $stmt = $db->getConnection()->prepare("
        UPDATE purchase_history
        SET status = :status
        $timestampField
        WHERE id = :id AND seller_id = :seller_id
    ");
    $stmt->execute([
        'status' => $newStatus,
        'id' => $orderId,
        'seller_id' => $muji
    ]);

    // Remember which tab was active (sent via POST)
    $activeTab = $_POST['new_status'] === 'shipped' ? 'shipped' : ($_POST['new_status'] === 'delivered' ? 'completed' : 'current');
    echo "<script>window.location='dashboard.php#orders?tab={$activeTab}';</script>";
    exit;

}




// Soft Delete Products
if (isset($_GET['delete_id'])) {
    $product_id = $_GET['delete_id'];

    if ($product->hasOrders($product_id)) {
        echo "<script>alert('Cannot delete! Product has existing orders.');</script>";
    } else {
        if ($product->softDeleteProduct($product_id)) {
            echo "<script>alert('Product deleted successfully.'); window.location='dashboard.php#products'</script>";
        } else {
            echo "<script>alert('Failed to delete product.');</script>";
        }
    }
}

// Fetch seller profile
$seller_details = $product->getSellerDetails($_SESSION['user_id']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['update_profile'])) {
    $shop_name = $_POST['shop_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $shop_location = $_POST['shop_location'] ?? '';

    $qr_code_path = $seller_details['qr_code'];

    $stmt = $db->getConnection()->prepare("
        UPDATE seller 
        SET shop_name = :shop_name, phone_number = :phone_number, shop_location = :shop_location, qr_code = :qr_code
        WHERE user_id = :user_id
    ");

    $updated = $stmt->execute([
        'shop_name' => $shop_name,
        'phone_number' => $phone_number,
        'shop_location' => $shop_location,
        'qr_code' => $qr_code_path,
        'user_id' => $_SESSION['user_id']
    ]);

    if ($updated) {
        $_SESSION['successMessage'] = "Profile updated successfully!";
        header("Location: dashboard.php#profile");
        exit;
    } else {
        $_SESSION['errorMessage'] = "Failed to update profile.";
        header("Location: dashboard.php#profile");
        exit;
    }

}

$seller_id = $muji;
$sellerProducts = $product->getProductsBySeller($seller_id);



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



<?php
// SELLER ID
$sellerId = $_SESSION['user_id'];

// === Total Sales (This Month) ===
$stmt = $db->getConnection()->prepare("
    SELECT SUM(cost) AS total_sales 
    FROM purchase_history 
    WHERE seller_id = :seller_id 
    AND status = 'delivered'
    AND MONTH(delivered_at) = MONTH(NOW()) 
    AND YEAR(delivered_at) = YEAR(NOW())
");
$stmt->execute(['seller_id' => $muji]);
$totalSales = $stmt->fetchColumn() ?? 0;

// === Total Orders ===
$stmt = $db->getConnection()->prepare("
    SELECT COUNT(*) FROM purchase_history WHERE seller_id = :seller_id
");
$stmt->execute(['seller_id' => $muji]);
$totalOrders = $stmt->fetchColumn();

// === Pending Orders ===
$stmt = $db->getConnection()->prepare("
    SELECT COUNT(*) FROM purchase_history 
    WHERE seller_id = :seller_id AND status = 'placed'
");
$stmt->execute(['seller_id' => $muji]);
$pendingOrders = $stmt->fetchColumn();

// === Total Products ===

$stmt = $db->getConnection()->prepare("SELECT seller_id FROM seller WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$mujii = $stmt->fetchColumn();

$stmt = $db->getConnection()->prepare("
    SELECT COUNT(*) FROM product 
    WHERE seller_id = :seller_id AND is_active='1'
");
$stmt->execute(['seller_id' => $muji]);
$totalProducts = $stmt->fetchColumn();

// === Revenue Growth % ===
// Last Month Sales
$stmt = $db->getConnection()->prepare("
    SELECT SUM(cost) 
    FROM purchase_history
    WHERE seller_id = :seller_id
    AND status = 'delivered'
    AND MONTH(delivered_at) = MONTH(NOW()) - 1
    AND YEAR(delivered_at) = YEAR(NOW())
");
$stmt->execute(['seller_id' => $muji]);
$lastMonthSale = $stmt->fetchColumn() ?? 0;

// Current month already fixed above (use delivered_at)
if ($lastMonthSale > 0) {
    $revenueGrowth = (($totalSales - $lastMonthSale) / $lastMonthSale) * 100;
} else {
    $revenueGrowth = 100;
}


?>




<!DOCTYPE HTML>
<html>

<head>
    <title>Seller Dashboard</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

                <h2>Dashboard Overview</h2>

                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card yellow">
                        <h4>Total Sales (This Month)</h4>
                        <p>Rs. <?= number_format($totalSales) ?></p>
                    </div>

                    <div class="stat-card grey">
                        <h4>Total Orders</h4>
                        <p><?= $totalOrders ?></p>
                    </div>

                    <div class="stat-card white">
                        <h4>Total Products</h4>
                        <p><?= $totalProducts ?></p>
                    </div>

                    <div class="stat-card yellow">
                        <h4>Pending Orders</h4>
                        <p><?= $pendingOrders ?></p>
                    </div>

                    <div class="stat-card green">
                        <h4>Revenue Growth</h4>
                        <p><?= number_format($revenueGrowth, 2) ?>%</p>
                    </div>
                </div>

                <br>

                <h2>Sales Trend</h2>
                <div class="sales-chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div id="orders" class="content-section">
                <h2>Orders</h2>

                <!-- Tabs -->
                <div class="order-tabs">
                    <button class="tab-btn" data-status="current">Current Orders<span class="dot"
                            id="currentDot"></span></button>
                    <button class="tab-btn" data-status="shipped">Shipped Orders<span class="dot"
                            id="shippedDot"></span></button>
                    <button class="tab-btn" data-status="completed">Completed Orders</button>
                </div>

                <!-- Orders Tables will be inserted here -->
                <div id="ordersContainer">
                    <p class="no-orders-message">Select a button above to view orders.</p>
                </div>
            </div>



            <!-- Products -->
            <div id="products" class="content-section">
                <h2 style="margin-bottom: 20px;">Your Products</h2>
                <?php if (empty($sellerProducts)): ?>
                    <p>No products added yet.</p>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($sellerProducts as $prod):
                            $images = explode(',', $prod['product_image']);
                            $firstImage = $images[0] ?? "no_image.png"; ?>
                            <div class="product-card">
                                <div class="product-image"><img src="<?= htmlspecialchars($firstImage) ?>"></div>
                                <div class="product-details">
                                    <h3><?= htmlspecialchars($prod['product_name']) ?></h3>
                                    <p class="price">Rs. <?= htmlspecialchars($prod['product_amount']) ?></p>
                                    <p class="category"><?= htmlspecialchars($prod['categories']) ?></p>
                                    <p class="stock">Stock: <?= htmlspecialchars($prod['product_stock']) ?></p>
                                    <div class="product-actions">
                                        <a href="edit_product.php?product_id=<?= $prod['product_id'] ?>"
                                            class="edit-btn">Edit</a>
                                        <a href="delete_product.php?delete_id=<?= $prod['product_id'] ?>" class="delete-btn"
                                            onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

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
            <!-- Customers Section -->
            <div id="customers" class="content-section">
                <h2>Customers who purchased your products</h2>

                <?php
                // Fetch all orders for this seller
                $stmt = $db->getConnection()->prepare("
        SELECT ph.id as purchase_id, ph.user_id, ph.product_id, ph.cost, ph.status,
               p.product_name, p.product_amount
        FROM purchase_history ph
        JOIN product p ON ph.product_id = p.product_id
        WHERE ph.seller_id = :seller_id
        ORDER BY ph.id DESC
    ");
                $stmt->execute(['seller_id' => $muji]);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($orders)) {
                    echo "<p>No customer orders yet.</p>";
                } else {
                    echo '<div class="customer-orders">';
                    foreach ($orders as $order) {
                        ?>
                        <div class="order-card">
                            <p><strong>Customer ID:</strong> <?= htmlspecialchars($order['user_id']) ?></p>
                            <p><strong>Product:</strong> <?= htmlspecialchars($order['product_name']) ?></p>
                            <p><strong>Price:</strong> Rs. <?= htmlspecialchars($order['product_amount']) ?></p>
                            <p><strong>Cost:</strong> Rs. <?= htmlspecialchars($order['cost']) ?></p>
                            <p><strong>Status:</strong></p>
                            <form method="POST" action="" class="status-form">
                                <input type="hidden" name="purchase_id" value="<?= $order['purchase_id'] ?>">
                                <?php
                                $statuses = ['placed', 'shipped', 'delivered'];
                                foreach ($statuses as $status) {
                                    $activeClass = ($order['status'] == $status) ? 'active-status' : '';
                                    echo "<button type='submit' name='update_status' value='{$status}' class='status-btn {$activeClass}'>" . ucfirst($status) . "</button>";
                                }
                                ?>
                            </form>
                        </div>
                        <?php
                    }
                    echo '</div>';
                }

                // Handle status update
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_id'], $_POST['update_status'])) {
                    $updateStmt = $db->getConnection()->prepare("
            UPDATE purchase_history
            SET status = :status
            WHERE id = :purchase_id AND seller_id = :seller_id
        ");
                    $updateStmt->execute([
                        'status' => $_POST['update_status'],
                        'purchase_id' => $_POST['purchase_id'],
                        'seller_id' => $_SESSION['user_id']
                    ]);
                    echo "<script>window.location=window.location.href;</script>"; // Refresh page to reflect change
                }
                ?>
            </div>

            <style>
                .customer-orders {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .order-card {
                    border: 1px solid #ccc;
                    padding: 10px;
                    border-radius: 5px;
                    background: #f9f9f9;
                }

                .order-card p {
                    margin: 5px 0;
                }

                /* Status buttons */
                .status-form {
                    display: flex;
                    gap: 5px;
                    margin-top: 5px;
                }

                .status-btn {
                    padding: 5px 10px;
                    border: 1px solid #ccc;
                    background: #eee;
                    cursor: pointer;
                    border-radius: 5px;
                    transition: background 0.3s, color 0.3s;
                }

                .status-btn:hover {
                    background: #ddd;
                }

                .active-status {
                    background: #4CAF50;
                    color: white;
                    border-color: #4CAF50;
                }
            </style>



            <!-- Profile -->
            <div id="profile" class="content-section">
                <h2>Seller Profile</h2>
                <div class="profile-card">
                    <div class="profile-left">
                        <form method="POST" action="">
                            <div class="form-row"><label>Shop Name</label><input type="text" name="shop_name"
                                    value="<?= htmlspecialchars($seller_details['shop_name']) ?>" required></div>
                            <div class="form-row"><label>Phone Number</label><input type="text" name="phone_number"
                                    value="<?= htmlspecialchars($seller_details['phone_number']) ?>" required></div>
                            <div class="form-row"><label>Shop Location</label><input type="text" name="shop_location"
                                    value="<?= htmlspecialchars($seller_details['shop_location']) ?>" required></div>
                            <div class="form-row"><button type="submit" name="update_profile">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
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

                if (window.location.hash === "#products") {
                    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
                    const productsSection = document.getElementById("products");
                    if (productsSection) productsSection.classList.add('active');
                }

                // Show profile section if URL hash
                if (window.location.hash === "#profile") {
                    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
                    const profileSection = document.getElementById("profile");
                    if (profileSection) profileSection.classList.add('active');
                }

                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                        document.querySelectorAll('.orders-tab').forEach(t => t.classList.remove('active'));

                        btn.classList.add('active');
                        const tabId = btn.getAttribute('data-tab');
                        document.getElementById(tabId).classList.add('active');
                    });
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
        <script>
            const chartLabels = <?= json_encode($dates) ?>;
            const salesData = <?= json_encode($sales) ?>;

            const ctx = document.getElementById('salesChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Sales (Last 30 Days)',
                        data: salesData,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true },
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        </script>


        <script>
            const ordersData = {
                current: <?= json_encode($currentOrders) ?>,
                shipped: <?= json_encode($shippedOrders) ?>,
                completed: <?= json_encode($completedOrders) ?>
            };

            function renderOrdersTable(status) {
                const container = document.getElementById('ordersContainer');
                container.innerHTML = ''; // Clear previous content

                const orders = ordersData[status];
                if (!orders || orders.length === 0) {
                    container.innerHTML = '<p class="no-orders-message">No orders found for this status.</p>';
                    return;
                }

                let tableHtml = `
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Customer Info</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    ${status === 'current' || status === 'shipped' ? '<th>Action</th>' : ''}
                </tr>
            </thead>
            <tbody>
    `;

                orders.forEach(o => {
                    const img = o.product_image.split(',')[0] || 'no_image.png';
                    let orderDate = '';
                    if (status === 'current') orderDate = o.placed_at;
                    if (status === 'shipped') orderDate = o.shipped_at;
                    if (status === 'completed') orderDate = `Placed: ${o.placed_at}, Shipped: ${o.shipped_at}, Delivered: ${o.delivered_at}`;
                    tableHtml += `
            <tr>
                <td>#${o.id}</td>
                <td class="product-cell">
                    <img src="${img}" class="order-img">
                    ${o.product_name}
                </td>
                <td>${o.username}</td>
                <td>
                    <form method="POST" style="display:flex; gap:5px; flex-direction: column;">

                        Phone No:<input type="text" name="delivery_phone" value="${o.delivery_phone ?? ''}" >
                        Address:<input type="text" name="delivery_address" value="${o.delivery_address ?? ''}">
                    
                    </form>
                </td>
                <td>Rs. ${o.cost}</td>
                <td>${o.status.charAt(0).toUpperCase() + o.status.slice(1)}</td>
                <td>${orderDate}</td>
                ${status === 'current' ? `
                <td>
                    <form method="POST">
                        <input type="hidden" name="update_order_id" value="${o.id}">
                        <select name="new_status">
                            <option value="placed" ${o.status === 'placed' ? 'selected' : ''}>Placed</option>
                            <option value="shipped" ${o.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                        </select>
                        <button type="submit" name="update_status_btn">Update</button>
                    </form>
                </td>` : ''}
                ${status === 'shipped' ? `
                <td>
                    <form method="POST">
                        <input type="hidden" name="update_order_id" value="${o.id}">
                        <select name="new_status">
                            <option value="shipped" ${o.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                            <option value="delivered" ${o.status === 'delivered' ? 'selected' : ''}>Completed</option>
                        </select>
                        <button type="submit" name="update_status_btn">Update</button>
                    </form>
                </td>` : ''}
            </tr>
        `;
                });

                tableHtml += `</tbody></table>`;
                container.innerHTML = tableHtml;
            }

            // Attach click events
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');

                    const status = btn.getAttribute('data-status');
                    renderOrdersTable(status);
                });
            });

            window.addEventListener('load', () => {
                let defaultStatus = 'current';

                if (window.location.hash.startsWith('#orders')) {
                    // Check if a tab is specified
                    const hashParts = window.location.hash.split('?tab=');
                    if (hashParts[1]) {
                        defaultStatus = hashParts[1];
                    }
                }

                // Render the correct tab
                renderOrdersTable(defaultStatus);

                // Highlight the correct tab button
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.getAttribute('data-status') === defaultStatus) {
                        btn.classList.add('active');
                    }
                });
            });


            // Show red dot if the section has orders
            function updateDots() {
                const currentDot = document.getElementById('currentDot');
                const shippedDot = document.getElementById('shippedDot');

                currentDot.style.visibility = ordersData.current && ordersData.current.length > 0 ? 'visible' : 'hidden';
                shippedDot.style.visibility = ordersData.shipped && ordersData.shipped.length > 0 ? 'visible' : 'hidden';
            }

            // Call it initially
            updateDots();


        </script>



</body>

</html>