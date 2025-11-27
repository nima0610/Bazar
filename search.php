<?php
require_once "database.php";
require_once 'details_product.php';
$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

$db = new Database($host, $dbname, $user, $pass);
$details = new Details($db);
// Fetch all categories for dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get GET parameters
$search = $_GET['searcher'] ?? '';
$selectedCategory = $_GET['category_id'] ?? '';
$sort = $_GET['sort'] ?? '';

// Build query
$sql = "SELECT * FROM product WHERE 1=1";
$params = [];

// Filter by search term
if (!empty($search)) {
    $sql .= " AND product_name LIKE :search";
    $params['search'] = "%$search%";
}

// Filter by selected category
if (!empty($selectedCategory)) {
    $sql .= " AND category_id = :category_id";
    $params['category_id'] = $selectedCategory;
}

// Apply sorting
if ($sort === 'price_asc') {
    $sql .= " ORDER BY product_amount ASC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY product_amount DESC";
} elseif ($sort === 'price_disc') {
    $sql .= " ORDER BY discount_percent DESC";
}

// Execute query
$result = $db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop From Home</title>
    <link rel="stylesheet" href="styles.css">
    </style>
    <!-- Add Font Awesome for icon -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

</head>

<body>

    <div class="main">
        <div class="side_options">
            <a href="activity.php" class="activity-link">My Activity</a> <a href="login.php">Login</a>
            <a href="registration.php">Signup</a>
            <a href="index.php">Home</a>
        </div>
        <div class="logo_main">
            <img src="assets/bazari.png">
        </div>
        <div class="search_bar">
            <form method="GET" action="search.php">
                <input type="text" name="searcher" value="<?= htmlspecialchars($search) ?>">
                <button class="search-button">🔍</button>
            </form>
        </div>
    </div>


    <div class="advertisement">

        <div class="categories-bar">
            <form method="GET" class="category-form">
                <!-- Preserve search term -->
                <input type="hidden" name="searcher" value="<?= htmlspecialchars($search) ?>">

                <!-- Preserve sort selection -->
                <input type="hidden" name="sort"
                    value="<?= isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : '' ?>">

                <select name="category_id" id="category" onchange="this.form.submit()">
                    <option value="">-- All Categories --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['category_id'] ?>" <?= ($selectedCategory == $category['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="optioning">
            <h2>
                <?= htmlspecialchars($search) ?>
                (<?= count($result) ?> products found)
            </h2>

            <form method="GET" class="sort-form">
                <!-- Preserve search term -->
                <input type="hidden" name="searcher" value="<?= htmlspecialchars($search) ?>">

                <!-- Preserve selected category -->
                <input type="hidden" name="category_id" value="<?= htmlspecialchars($selectedCategory) ?>">

                <select name="sort" id="sort" onchange="this.form.submit()">
                    <option value="">-- Select --</option>
                    <option value="price_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="price_disc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'price_disc') ? 'selected' : '' ?>>Price: Discount</option>
                </select>
            </form>
        </div>

        <div class="grid-container dashboard-grid">

            <?php if (!empty($result)): ?>

                <?php foreach ($result as $row): ?>

                    <?php


                    $product_review = $details->getProductReview($row['product_id']);
                    // Calculate average rating for this product
                    $averageRating = 0;
                    if (!empty($product_review)) {
                        $totalRating = 0;
                        $reviewCount = count($product_review);
                        foreach ($product_review as $rev) {
                            $totalRating += (float) $rev['rating'];
                        }
                        $averageRating = $totalRating / $reviewCount;
                    }
                    $roundedRating = round($averageRating, 1); // round to 1 decimal
                    $reviewCount = count($product_review);
                    ?>
                    <?php
                    $folder = 'seller/uploads/products/';
                    $filename = basename($row['product_image']);
                    ?>
                    <div class="grid-item" data-id="<?= $row['product_id']; ?>" style="cursor:pointer;">


                        <div class="image-container">
                            <img src="<?= $folder . rawurlencode($filename) ?>"
                                alt="<?= htmlspecialchars($row['product_name']) ?>">
                        </div>


                        <div class="info-container">
                            <h3><?= htmlspecialchars($row['product_name']) ?></h3>
                            <div class="average-rating" style="display:flex; align-items:center; gap:5px; margin-top:5px;">
                                <?php
                                // Display 5 stars
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= floor($averageRating)) {
                                        echo "<span class='star filled'>★</span>"; // full star
                                    } elseif ($i - $averageRating < 1) {
                                        echo "<span class='star filled' style='clip-path: inset(0 " . (100 - (($averageRating - floor($averageRating)) * 100)) . "% 0 0);'>★</span>"; // partial star
                                    } else {
                                        echo "<span class='star empty'>☆</span>"; // empty star
                                    }
                                }
                                ?>
                                <span style="font-size:16px; color:#555;"><?php echo $roundedRating; ?> / 5</span>
                                <span style="font-size:14px; color:#777;">(<?php echo $reviewCount; ?> ratings)</span>
                            </div>

                            <?php
                            $price = $row['product_amount'];
                            $discount = $row['discount_percent'];
                            $discounted_price = $price - ($price * $discount / 100);
                            ?>

                            <p style="color:red; font-size:20px;">
                                Rs <?= number_format($discounted_price, 2) ?>
                            </p>

                            <?php if ($discount > 0): ?>
                                <p style="text-decoration:line-through; color:gray;">
                                    Rs <?= number_format($price) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>

        </div>

    </div>




    <script>

        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll('.grid-item').forEach(item => {
                item.addEventListener('click', () => {
                    const id = item.dataset.id;
                    if (id) {
                        window.location.href = `product_details.php?id=${id}`;
                    }
                });
            });
        });

    </script>
</body>

</html>