<?php
session_start();
require_once 'database.php';
require_once 'details_product.php';
require_once 'customer_details.php';

$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

$db = new Database($host, $dbname, $user, $pass);
$productDetails = new Details($db);
$customer = new CustomerDetails($db);

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];

// FETCH PURCHASES REQUIRING FEEDBACK
$stmt = $db->query("
    SELECT id, product_id
    FROM purchase_history
    WHERE user_id = ?
    AND status = 'delivered'
    AND review_status = 'pending'
", [$user_id]);

$pendingReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<html>

<head>
    <title>Product Feedback</title>
    <link rel="stylesheet" href="feedback.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <style>
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin: 20px auto;
            border-radius: 5px;
            width: fit-content;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <a href="index.php" class="home-btn">🏠 Home</a>

    <h1 style="text-align:center; margin-top:20px;">How Was Your Purchase?</h1>

    <?php
    if (isset($_GET['success']) && $_GET['success'] == 1) {
        echo '<div class="success-message" id="successMessage">Thank you for your feedback!</div>';
    }
    ?>

    <div class="main_container">
        <?php if (!empty($pendingReviews)): ?>
            <?php foreach ($pendingReviews as $row):
                $purchaseId = $row['id'];
                $productId = $row['product_id'];

                $product = $productDetails->getProductDetails($productId);
                $images = explode(",", $product['product_image']);
                ?>
                <div class="divider" id="review-<?php echo $purchaseId; ?>">

                    <!-- Product Images -->
                    <div class="product_image">
                        <div class="swiper mySwiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($images as $img):
                                    $img = trim($img);
                                    if (!empty($img)): ?>
                                        <div class="swiper-slide">
                                            <img src="seller/<?php echo htmlspecialchars($img); ?>" alt="Product Image">
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-pagination"></div>
                        </div>
                    </div>

                    <!-- Product Info and Feedback Form -->
                    <div class="purchase-card">
                        <h1><?php echo ucwords($product['product_name']); ?></h1>
                        <p><?php echo ucfirst($product['description']); ?></p>

                        <form action="submit_feedback.php" method="POST" class="feedback-form">
                            <input type="hidden" name="purchase_id" value="<?php echo $purchaseId; ?>">
                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">

                            <label>Rating:</label><br>
                            <select name="rating" required>
                                <option value="">Select rating</option>
                                <option value="5">⭐ 5 - Excellent</option>
                                <option value="4">⭐ 4 - Good</option>
                                <option value="3">⭐ 3 - Average</option>
                                <option value="2">⭐ 2 - Poor</option>
                                <option value="1">⭐ 1 - Bad</option>
                            </select>
                            <br><br>

                            <label>Write your Review:</label>
                            <textarea name="review_text" placeholder="Type your feedback..." required></textarea>

                            <button type="submit" class="submit-feedback">Submit Review</button>
                        </form>
                    </div>

                </div>
                <hr>
            <?php endforeach; ?>
        <?php else: ?>
            <h2 style="text-align:center;">No pending reviews!</h2>
        <?php endif; ?>
    </div>

    <!-- Swiper Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script>
        // Hide success message after 2 seconds (2000ms)
        const successMsg = document.getElementById('successMessage');
        if (successMsg) {
            setTimeout(() => {
                successMsg.style.display = 'none';
            }, 2000);
        }
    </script>
    <script>
        document.querySelectorAll('.mySwiper').forEach(swiperEl => {
            new Swiper(swiperEl, {
                loop: true,
                navigation: {
                    nextEl: swiperEl.querySelector('.swiper-button-next'),
                    prevEl: swiperEl.querySelector('.swiper-button-prev'),
                },
                pagination: {
                    el: swiperEl.querySelector('.swiper-pagination'),
                    clickable: true,
                },
                slidesPerView: 1,
                spaceBetween: 10,
            });
        });
    </script>

</body>

</html>