<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
require_once "database.php";
require_once "product.php";
require_once "categories.php";

// Change these to your DB credentials
$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

// Create DB connection
$db = new Database($host, $dbname, $user, $pass);

// Create Product instance
$productObj = new Product($db);

$categoryobj = new Categories($db);

// Fetch products ordered by sold quantity descending
$products = $productObj->getTopProductsBySold();
$product_category = $productObj->getTopProductByDiscount();
$home_essential = $productObj->getTopHomeDecor();
$beauty_products = $productObj->getTopBeauty();
$home_decorations = $productObj->getTopHome();
$utensils = $productObj->getTopUtensils();
$nb_set = $productObj->getTopBeautyNB();
$watch = $productObj->getTopWatch();
$electronics = $productObj->getTopElectronics();
$toys = $productObj->getTopToys();
$categories = $categoryobj->getCategories();



//for review part....
$pendingReview = false;

if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];

    // Fetch all delivered orders that are pending review
    $stmt = $db->prepare("
    SELECT ph.id, p.product_name
    FROM purchase_history ph
    JOIN product p ON ph.product_id = p.product_id
    WHERE ph.user_id = :user_id AND ph.status = 'delivered' AND ph.review_status = 'pending'
    ORDER BY ph.id ASC
");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $pendingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pendingReview = count($pendingOrders) > 0;
}




?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop From Home</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" rel="stylesheet" />
    </style>
    <!-- Add Font Awesome for icon -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

</head>

<body>

    <script>
        console.log("the user id is ", <?php echo $_SESSION['user_id']; ?>);
        console.log("the user name is", <?php echo json_encode($_SESSION['username']); ?>);    </script>
    <div class="main">
        <div class="side_options">
            <a href="activity.php" class="activity-link">My Activity</a> <a href="login.php">Login</a>
            <a href="registration.php">Signup</a>
            <a href="cart.php">My Cart 🛒</a>
        </div>
        <div class="logo_main">
            <img src="assets/bazari.png">
        </div>
        <div class="search_bar">
            <form method="GET" action="search.php">
                <input type="text" name="searcher" placeholder="Search Products in Bazar">
                <button class="search-button">🔍</button>
            </form>
        </div>
    </div>

    <section id="slider-1">
        <div class="swiper" id="swiper-1">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <img src="img/shop.jpg" alt="Spain" />
                </div> <!-- end swiper-slide -->
                <div class="swiper-slide">
                    <img src="img/shoppp.webp" alt="Spain" />
                </div> <!-- end swiper-slide -->
                <div class="swiper-slide">
                    <img src="img/toys.jpg" alt="Spain" />
                </div> <!-- end swiper-slide -->
                <div class="swiper-slide">
                    <img src="img/game.jpg" alt="Spain" />
                </div> <!-- end swiper-slide -->
            </div>
            <div class="swiper-pagination"></div>
        </div> <!-- end swiper -->
    </section>

    <div class="categorize">
        <div class="box1">
            <h2>Shop for Beauty Products</h2>

            <div class="show_productbox">
                <?php
                // Loop through products dynamically
                foreach ($beauty_products as $product):
                    $folder = 'seller/uploads/products/';
                    $filename = basename($product['product_image']);
                    $price = $product['product_amount'];
                    $discount = $product['discount_percent'];
                    $discounted_price = $price - ($price * $discount / 100);
                    ?>
                    <div class="grid-item" data-id="<?php echo $product['product_id']; ?>" style="cursor:pointer;">
                        <div class="image-container">
                            <img src="<?php echo $folder . rawurlencode($filename); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                        </div>
                        <div class="info-container">
                            <h3 style="text-align: center;"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <div class="box2">
            <h2>Shop for Cars</h2>

            <div class="show_productbox">
                <?php
                // Loop through products dynamically
                foreach ($home_essential as $product):
                    $folder = 'seller/uploads/products/';
                    $filename = basename($product['product_image']);
                    $price = $product['product_amount'];
                    $discount = $product['discount_percent'];
                    $discounted_price = $price - ($price * $discount / 100);
                    ?>
                    <div class="grid-item" data-id="<?php echo $product['product_id']; ?>" style="cursor:pointer;">
                        <div class="image-container">
                            <img src="<?php echo $folder . rawurlencode($filename); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                        </div>
                        <div class="info-container">
                            <h3 style="text-align: center;"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>


        </div>
        <div class="box3">
            <h2>Shop Home Decorations</h2>

            <div class="show_productbox">
                <?php
                // Loop through products dynamically
                foreach ($home_decorations as $product):
                    $folder = 'seller/uploads/products/';
                    $filename = basename($product['product_image']);
                    $price = $product['product_amount'];
                    $discount = $product['discount_percent'];
                    $discounted_price = $price - ($price * $discount / 100);
                    ?>
                    <div class="grid-item" data-id="<?php echo $product['product_id']; ?>" style="cursor:pointer;">
                        <div class="image-container">
                            <img src="<?php echo $folder . rawurlencode($filename); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                        </div>
                        <div class="info-container">
                            <h3 style="text-align: center;"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <div class="box4">
            <h2>Shop for Utensils</h2>
            <div class="show_productbox">
                <?php
                // Loop through products dynamically
                foreach ($utensils as $product):
                    $folder = 'seller/uploads/products/';
                    $filename = basename($product['product_image']);
                    $price = $product['product_amount'];
                    $discount = $product['discount_percent'];
                    $discounted_price = $price - ($price * $discount / 100);
                    ?>
                    <div class="grid-item" data-id="<?php echo $product['product_id']; ?>" style="cursor:pointer;">
                        <div class="image-container">
                            <img src="<?php echo $folder . rawurlencode($filename); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                        </div>
                        <div class="info-container">
                            <h3 style="text-align: center;"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>



    <div class="categorize2">
        <div class="box1">
            <h2>Shop for NB Sets</h2>

            <div class="show_productbox">
                <?php
                // Loop through products dynamically
                foreach ($nb_set as $product):
                    $folder = 'seller/uploads/products/';
                    $filename = basename($product['product_image']);
                    $price = $product['product_amount'];
                    $discount = $product['discount_percent'];
                    $discounted_price = $price - ($price * $discount / 100);
                    ?>
                    <div class="grid-item" data-id="<?php echo $product['product_id']; ?>" style="cursor:pointer;">
                        <div class="image-container">
                            <img src="<?php echo $folder . rawurlencode($filename); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                        </div>
                        <div class="info-container">
                            <h3 style="text-align: center;"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <div class="box2">
            <h2>Shop Watches</h2>

            <div class="show_productbox">
                <?php
                // Loop through products dynamically
                foreach ($watch as $product):
                    $folder = 'seller/uploads/products/';
                    $filename = basename($product['product_image']);
                    $price = $product['product_amount'];
                    $discount = $product['discount_percent'];
                    $discounted_price = $price - ($price * $discount / 100);
                    ?>
                    <div class="grid-item" data-id="<?php echo $product['product_id']; ?>" style="cursor:pointer;">
                        <div class="image-container">
                            <img src="<?php echo $folder . rawurlencode($filename); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                        </div>
                        <div class="info-container">
                            <h3 style="text-align: center;"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>


        </div>
        <div class="box3">
            <h2>Shop for Electronics</h2>

            <div class="show_productbox">
                <?php
                // Loop through products dynamically
                foreach ($electronics as $product):
                    $folder = 'seller/uploads/products/';
                    $filename = basename($product['product_image']);
                    $price = $product['product_amount'];
                    $discount = $product['discount_percent'];
                    $discounted_price = $price - ($price * $discount / 100);
                    ?>
                    <div class="grid-item" data-id="<?php echo $product['product_id']; ?>" style="cursor:pointer;">
                        <div class="image-container">
                            <img src="<?php echo $folder . rawurlencode($filename); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                        </div>
                        <div class="info-container">
                            <h3 style="text-align: center;"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <div class="box4">
            <h2>Shop for Toys</h2>
            <div class="show_productbox">
                <?php
                // Loop through products dynamically
                foreach ($toys as $product):
                    $folder = 'seller/uploads/products/';
                    $filename = basename($product['product_image']);
                    $price = $product['product_amount'];
                    $discount = $product['discount_percent'];
                    $discounted_price = $price - ($price * $discount / 100);
                    ?>
                    <div class="grid-item" data-id="<?php echo $product['product_id']; ?>" style="cursor:pointer;">
                        <div class="image-container">
                            <img src="<?php echo $folder . rawurlencode($filename); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                        </div>
                        <div class="info-container">
                            <h3 style="text-align: center;"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="flash">
        <h1>Flash Sale</h1>
    </div>

    <div class="advertisement">
        <div class="grid-container dashboard-grid">
            <?php foreach ($product_category as $product): ?>

                <div class="grid-item" data-id="<?php echo $product['product_id']; ?>" style="cursor:pointer;">

                    <div class="image-container">
                        <?php
                        // Separate folder and filename
                        $folder = 'seller/uploads/products/';
                        $filename = basename($product['product_image']); // 1754894290_Screenshot (3).png
                        ?>
                        <img src="<?php echo $folder . rawurlencode($filename); ?>"
                            alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                    </div>
                    <div class="info-container">
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>

                        <p style="color:red; font-size: 24px; position: relative; left: 10px;">
                            Rs
                            <?php
                            $price = $product['product_amount'];
                            $discount = $product['discount_percent'];
                            $discounted_price = $price - ($price * $discount / 100);
                            echo htmlspecialchars(number_format($discounted_price, 2));
                            ?>
                        </p>

                        <?php if ((float) $discount > 0): ?>
                            <p style="font-size: 20px; text-decoration: line-through; color: gray;">
                                Rs <?php echo number_format($price); ?>
                            </p>
                        <?php endif; ?>

                        <p><strong>Sold: </strong><?php echo (int) $product['sold']; ?></p>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>
    </div>

    <br>


    <div class="flash">
        <h1>Most Sold Items</h1>
    </div>

    <div class="advertisement">
        <div class="grid-container dashboard-grid">
            <?php foreach ($products as $product): ?>

                <div class="grid-item" data-id="<?php echo $product['product_id']; ?>" style="cursor:pointer;">

                    <div class="image-container">
                        <?php
                        // Separate folder and filename
                        $folder = 'seller/uploads/products/';
                        $filename = basename($product['product_image']); // 1754894290_Screenshot (3).png
                        ?>
                        <img src="<?php echo $folder . rawurlencode($filename); ?>"
                            alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                    </div>
                    <div class="info-container">
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>

                        <p style="color:red; font-size: 24px; position: relative; left: 10px;">
                            Rs
                            <?php
                            $price = $product['product_amount'];
                            $discount = $product['discount_percent'];
                            $discounted_price = $price - ($price * $discount / 100);
                            echo htmlspecialchars(number_format($discounted_price, 2));
                            ?>
                        </p>

                        <?php if ((float) $discount > 0): ?>
                            <p style="font-size: 20px; text-decoration: line-through; color: gray;">
                                Rs <?php echo number_format($price); ?>
                            </p>
                        <?php endif; ?>

                        <p><strong>Sold: </strong><?php echo (int) $product['sold']; ?></p>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>
    </div>

    <!--<div class="flash">
        <h1>Categories</h1>
    </div>

    <div class="advertisement">
        <div class="grid-container dashboard-grid">
            <?php foreach ($categories as $category): ?>
                <div class="grid-item">
                    <div class="image-container">
                        <?php
                        // Separate folder and filename
                        $folder = 'seller/uploads/products/';
                        $filename = basename($category['category_image']); // 1754894290_Screenshot (3).png
                        ?>
                        <img src="<?php echo $folder . rawurlencode($filename); ?>"
                            alt="<?php echo htmlspecialchars($categoryt['category_name']); ?>" />
                    </div>
                    <div class="info-container">
                        <h3><?php echo htmlspecialchars($category['category_name']); ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

    !-->

    <script>
        const pendingReview = <?php echo json_encode($pendingReview); ?>;
        const pendingOrders = <?php echo json_encode($pendingOrders ?? []); ?>; // safe fallback for guests
    </script>

    <div id="blur-overlay"></div>
    <div id="review-popup">
        <div class="popup-content">
            <h3 id="review-title">Would you like to provide feedback for:</h3>
            <div id="pending-products"></div>

            <div class="popup-buttons">
                <button id="review-now" class="btn btn-primary">Give Feedback</button>
                <button id="review-later" class="btn btn-secondary">Later</button>
                <button id="no_review" class="btn btn-danger">Not Interested</button>
            </div>
        </div>
    </div>
    <script>
        document.querySelector('.activity-link').addEventListener('click', function (e) {
            const isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;

            if (!isLoggedIn) {
                e.preventDefault(); // stop navigating
                alert("You have to login first.");
                window.location.href = "login.php";
            }
        });


        //for reviewe .....
        document.addEventListener("DOMContentLoaded", function () {

            const popup = document.getElementById('review-popup');
            const blur = document.getElementById('blur-overlay');
            const listDiv = document.getElementById('pending-products');

            // Only run if there are pending reviews
            if (pendingReview && pendingOrders.length > 0) {

                // Show popup & blur
                popup.style.display = 'block';
                blur.style.display = 'block';

                // Create product list
                let html = "<ul>";
                pendingOrders.forEach(order => {
                    html += `<li>${order.product_name}</li>`;
                });
                html += "</ul>";
                listDiv.innerHTML = html;

                // ========= BUTTON HANDLERS =========

                // 1. WHEN USER WANTS TO GIVE FEEDBACK
                document.getElementById('review-now').addEventListener('click', function () {
                    window.location.href = 'feedback.php'; // go to feedback page
                });

                // 2. WHEN USER WANTS TO REVIEW LATER
                document.getElementById('review-later').addEventListener('click', function () {
                    popup.style.display = 'none';
                    blur.style.display = 'none';
                });

                // 3. WHEN USER IS NOT INTERESTED (MARK ALL AS SKIPPED)
                document.getElementById('no_review').addEventListener('click', function () {

                    const orderIds = pendingOrders.map(o => o.id).join(',');

                    fetch('update_review.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'order_ids=' + encodeURIComponent(orderIds)
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                popup.style.display = 'none';
                                blur.style.display = 'none';
                                alert('All pending orders have been marked as skipped.');
                            } else {
                                alert('Something went wrong while updating.');
                            }
                        })
                        .catch(err => console.error(err));
                });
            }
        });


    </script>
    <script src="app.js"></script>


    <footer class="footer">
        <div class="section__container footer__container">
            <div class="footer__col">
                <div class="footer__logo">
                    <a href="#" class="logo">
                        <img src="assets/bazari.png" alt="logo" />

                    </a>
                </div>
                <p>
                    "We're here to bring you the best online shopping experience with a wide range of products, great
                    deals, and fast delivery. Stay tuned for updates, exclusive offers, and more. Shop with confidence
                    on Daraz!"
                </p>
                <ul class="footer__socials">
                    <li>
                        <a href="#"><i class="ri-facebook-fill"></i></a>
                    </li>
                    <li>
                        <a href="#"><i class="ri-twitter-fill"></i></a>
                    </li>
                    <li>
                        <a href="#"><i class="ri-linkedin-fill"></i></a>
                    </li>
                    <li>
                        <a href="#"><i class="ri-instagram-line"></i></a>
                    </li>
                    <li>
                        <a href="#"><i class="ri-youtube-fill"></i></a>
                    </li>
                </ul>
            </div>
            <div class="footer__col">
                <h4>Our Services</h4>
                <ul class="footer__links">
                    <li>
                        Online Shopping
                    </li>
                    <li>
                        Fast Delivery
                    </li>
                    <li>
                        Cash on Delivery
                    </li>
                    <li>
                        Flash Sale
                    </li>
                    <li>
                        Testimonials
                    </li>
                </ul>
            </div>

            <div class="footer__col">
                <h4>Contact</h4>
                <ul class="footer__links">
                    <li>
                        <a href="#">
                            <span><i class="ri-phone-fill"></i></span> +9825085032
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <span><i class="ri-map-pin-fill"></i></span> Putalisadak, Kathmandu
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <span><i class="ri-mail-fill"></i></span> nima19bit2021@kcc.edu.np
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer__bar">
            Copyright ©. All rights reserved.
        </div>
    </footer>

    <script src="https://unpkg.com/scrollreveal"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>


</body>

</html>