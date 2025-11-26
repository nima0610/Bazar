<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
require_once 'database.php';
require_once 'details_product.php';
require_once 'product.php';

$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

$db = new Database($host, $dbname, $user, $pass);

$details = new Details($db);
$productnikal = new Product($db);



// ----- ADD TO CART HANDLER -----
if (isset($_GET['add_cart'])) {

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $product_id = $_GET['product_id'];
    $sql = "SELECT variant_id, size, color, stock 
        FROM product_variants 
        WHERE product_id = ? AND stock > 0";

    $stmt = $db->query($sql, [$product_id]);
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create arrays for size and color dropdowns
    $sizes = [];
    $colors = [];

    foreach ($variants as $v) {
        if ($v['size'])
            $sizes[$v['size_id']] = $v['size'];
        if ($v['color'])
            $colors[$v['color_id']] = $v['color'];
    }


    // Fetch available sizes and colors for this product
    $availableSizes = $productnikal->getAvailableSizes($product_id); // returns array of sizes
    $availableColors = $productnikal->getAvailableColors($product_id); // returns array of colors

    // quantity default = 1
    $quantity = isset($_GET['quantity']) ? (int) $_GET['quantity'] : 1;

    // 1️⃣ Check if this product is already in cart for this user
    $stmt = $db->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // ✅ Product already in cart, just show success message
        header("Location: product_details.php?id=$product_id&added=1");
        exit;
    }

    // 2️⃣ Otherwise, insert into cart table
    $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $product_id, $quantity]);

    // 3️⃣ Redirect with success message
    header("Location: product_details.php?id=$product_id&added=1");
    exit;
}



if (isset($_GET['id']) || isset($_GET['product_id'])) {
    $product_id = isset($_GET['id']) ? $_GET['id'] : $_GET['product_id'];

    // Fetch available sizes and colors for this product
    $availableSizes = $productnikal->getAvailableSizes($product_id); // returns array of sizes
    $availableColors = $productnikal->getAvailableColors($product_id); // returns array of colors

    $product_info = $details->getProductDetails($product_id);

    if (!$product_info) {
        echo "No product selected.";
        exit;
    }

    // Now safe
    $product_seller = $product_info['seller_id'];
    $category_id = $product_info['category_id'];


    $productdetail = $details->getProductDetails($product_id);
    $seller_info = $details->getSellerName($product_seller);

    $product_review = $details->getProductReview($product_id);

    $seller_name = $seller_info['shop_name'];
    $seller_naam = $seller_info['shop_location'];
    $seller_phone = $seller_info['phone_number'];
    echo "<script>
 
      console.log('Category ID :', " . json_encode($category_id) . ");
 </script>";
    $products = $productnikal->getProductByCategory($category_id);
    $product_sale = $productnikal->getTopProductsBySold();

    echo "<script>
 
      console.log('seller name is :', " . json_encode($seller_name) . ");
 </script>";


} else {
    echo "No product selected.";
    exit;
}





?>

<?php
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
// ----- PAGINATION LOGIC -----
$reviewsPerPage = 5; // Number of reviews per page
$totalReviews = count($product_review); // total reviews for this product
$totalPages = ceil($totalReviews / $reviewsPerPage);

// Get current page from URL ?page=1, default is 1
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$currentPage = max(1, min($currentPage, $totalPages)); // safety check

$startIndex = ($currentPage - 1) * $reviewsPerPage;
$currentReviews = array_slice($product_review, $startIndex, $reviewsPerPage);
?>


<html>

<head>
    <title>Product Detail</title>
    <link rel="stylesheet" href="productstyle.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add Font Awesome for icon -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
</head>

<body>
    <?php if (isset($_GET['added'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Added to Cart!',
                html: '🛒 The product has been added to your cart.<br>✅ You can check your cart anytime.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            // Remove 'added' from URL immediately
            const url = new URL(window.location.href);
            url.searchParams.delete('added');
            window.history.replaceState({}, document.title, url);
        </script>
    <?php endif; ?>



    <?php if (isset($_GET['success'])): ?>
        <div id="success-message" style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border-radius:5px;">
            ✅ Successfully purchased product!
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] == 1): ?>
        <div id="success-message" style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border-radius:5px;">
            ❌ Not enough stock available!
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] == 2): ?>
        <div id="success-message" style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border-radius:5px;">
            ❌ Product not found.
        </div>
    <?php endif; ?>
    <div class="main">
        <div class="side_options">
            <a href="activity.php" class="activity-link">My Activity</a>
            <a href="login.php">Login</a>
            <a href="registration.php">Signup</a>
            <a href="index.php">Home</a>
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

    <div class="product_storage">
        <div class="product_image">

            <!-- Swiper -->

            <div class="swiper mySwiper">
                <div class="swiper-wrapper">
                    <?php
                    // Example: get all product images from database as an array
                    // Suppose your database has images stored in a comma-separated string
                    $images = explode(',', $productdetail['product_image']); // product_images: "img1.jpg,img2.jpg,img3.jpg"
                    
                    foreach ($images as $image) {
                        $image = trim($image); // remove whitespace
                        if (!empty($image)) {
                            echo '<div class="swiper-slide">';
                            echo '<img src="seller/' . htmlspecialchars($image) . '" alt="Product Image">';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>

                <!-- Optional navigation buttons -->
                <div class="swiper-button-next"><i class="fas fa-chevron-right"></i></div>
                <div class="swiper-button-prev"><i class="fas fa-chevron-left"></i></div>

                <!-- Optional pagination -->
                <div class="swiper-pagination"></div>
            </div>


        </div>

        <div class="product_descript">
            <h1>
                <h1><?php echo htmlspecialchars($productdetail['product_name']); ?></h1>
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
                <div class="sold_left">
                    <p style="color:blue; font-size: 18px; position: relative; left: 10px;">
                        <?php echo htmlspecialchars($productdetail['sold']); ?> sold
                    </p>
                    <p style="color:blue; font-size: 18px; position: relative; left: 10px;">
                        Remaining : <?php echo htmlspecialchars($productdetail['product_stock']); ?>
                    </p>
                </div>
                <hr style="border: 1px solid #000; width: 100%; text-align: center;">
                <p style="color:red; font-size: 24px; position: relative; left: 10px;">
                    Price: Rs
                    <?php
                    $price = $productdetail['product_amount'];
                    $discount = $productdetail['discount_percent'];
                    $discounted_price = $price - ($price * $discount / 100);
                    echo htmlspecialchars(number_format($discounted_price, 2));
                    ?>
                </p>
                <div class="discount">

                    <?php if ((float) $discount > 0): ?>
                        <p style="color:red; font-size: 20px; text-decoration: line-through; color: gray;">
                            Rs <?php echo htmlspecialchars($productdetail['product_amount']); ?>
                        </p>
                        <p style="color:blue; font-size: 18px; position: relative; left: 10px;">
                            (<?php echo htmlspecialchars($productdetail['discount_percent']); ?>% discount)
                        </p>
                    <?php else: ?>
                        <p style="color:gray; position: relative; left:15px; font-size: 16px; font-style: italic;">
                            No discount available for this product.
                        </p>
                    <?php endif; ?>

                </div>
            </h1>

            <form id="product-form" method="GET" action="product_details.php">
                <div class="quantity-wrapper">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                    <!--
                    <label>Quantity :</label>
                    <div class="quantity">
                        <button type="button" class="decrease">-</button>
                        <input type="text" name="quantity" value="1">
                        <button type="button" class="increase">+</button>
                    </div>
                    !-->

                </div>
                <div class="variant-options">
                    <?php if (!empty($availableSizes)): ?>
                        <div class="sizes">
                            <label><strong>Size:</strong></label>
                            <div class="size-options">
                                <?php foreach ($availableSizes as $size): ?>
                                    <div class="option-box" data-value="<?= htmlspecialchars($size) ?>"
                                        onclick="selectOption(this, 'size')">
                                        <?= htmlspecialchars($size) ?>
                                        <input type="radio" name="selected_size" value="<?= htmlspecialchars($size) ?>"
                                            style="display:none;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($availableColors)): ?>
                        <div class="colors" style="margin-top:10px;">
                            <label><strong>Color:</strong></label>
                            <div class="color-options">
                                <?php foreach ($availableColors as $color): ?>
                                    <div class="option-box" data-value="<?= htmlspecialchars($color) ?>"
                                        onclick="selectOption(this, 'color')">
                                        <?= htmlspecialchars($color) ?>
                                        <input type="radio" name="selected_color" value="<?= htmlspecialchars($color) ?>"
                                            style="display:none;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="action-buttons">
                    <button class="buy-now">Buy Product</button>
                    <button type="submit" name="add_cart" id="add-to-cart" class="add-to-cart">Add to Cart</button>
                </div>
            </form>

        </div>
        <div class="product_second_descript">
            <div class="for_gap">
                <h1>Description</h1>
                <p>
                    <?php echo htmlspecialchars(ucfirst($productdetail['description'])); ?>
                </p>

                <div class="seller_home">

                    <h1>Seller :</h1>
                    <h2> <img src="img/shopp.png" alt="Shop Logo">
                        <?php
                        echo ucwords($seller_name);
                        ?>
                    </h2>
                    <h2> <img src="img/mapp.png" alt="Shop Logo">
                        <?php
                        echo ucwords($seller_naam);
                        ?>
                    </h2>
                    <h2> <img src="img/phone.jpeg" alt="Shop Logo">
                        <?php
                        echo ucwords($seller_phone);
                        ?>
                    </h2>
                </div>

            </div>
        </div>
    </div>

    <?php
    // ----- PAGINATION LOGIC -----
    $reviewsPerPage = 5;
    $totalReviews = count($product_review);
    $totalPages = ceil($totalReviews / $reviewsPerPage);

    $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $currentPage = max(1, min($currentPage, $totalPages));

    $startIndex = ($currentPage - 1) * $reviewsPerPage;
    $currentReviews = array_slice($product_review, $startIndex, $reviewsPerPage);
    ?>

    <div class="review-section">
        <h2>Ratings & Reviews of <?php echo htmlspecialchars($product_info['product_name']); ?></h2>

        <?php if (!empty($currentReviews)): ?>
            <?php foreach ($currentReviews as $rev): ?>
                <div class="review-box">
                    <div class="review-header">
                        <!-- ⭐ RATING STARS -->
                        <div class="review-stars">
                            <?php
                            $rating = (int) $rev['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating
                                    ? "<span class='star filled'>★</span>"
                                    : "<span class='star empty'>☆</span>";
                            }
                            ?>
                        </div>
                        <span class="review-date"><?php echo htmlspecialchars($rev['created_at']); ?></span>
                    </div>

                    <div class="review-user">
                        User: <?php
                        $cusid = $rev['user_id'];
                        $cusdet = $details->getUserData($cusid);
                        // Capitalize first letter of each word
                        $fullName = ucwords(strtolower($cusdet['full_name']));
                        echo htmlspecialchars($fullName);
                        ?>
                        <span style="color: green; font-weight: bold; margin-left: 10px;">✔ Verified Purchase</span>
                    </div>

                    <p class="review-text"><?php echo nl2br(htmlspecialchars($rev['review_text'])); ?></p>
                </div>

                <hr class="review-divider">
            <?php endforeach; ?>
        <?php else: ?>
            <p>No reviews yet.</p>
        <?php endif; ?>

        <!-- Pagination Links -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a class="page-link <?php echo $p == $currentPage ? 'active' : ''; ?>"
                        href="?id=<?php echo $product_id; ?>&page=<?php echo $p; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>




    <div class="flash">
        <h1>You may also like</h1>
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

    <div class="flash">
        <h1>Most Sold Items</h1>
    </div>

    <div class="advertisement">
        <div class="grid-container dashboard-grid">
            <?php foreach ($product_sale as $product): ?>

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





    <script>



        function selectOption(element, type) {
            // Deselect all options of this type
            const container = element.parentElement;
            container.querySelectorAll('.option-box').forEach(box => box.classList.remove('selected'));
            container.querySelectorAll('input[type="radio"]').forEach(input => input.checked = false);

            // Select clicked option
            element.classList.add('selected');
            const input = element.querySelector('input[type="radio"]');
            input.checked = true;
        }

        var swiper = new Swiper(".mySwiper", {
            loop: true,              // infinite loop
            navigation: {            // arrows
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            pagination: {            // pagination dots
                el: ".swiper-pagination",
                clickable: true,
            },
            slidesPerView: 1,        // one image at a time
            spaceBetween: 10,        // space between slides
        });



        const productImage = "<?php echo htmlspecialchars($productdetail['product_image']); ?>";
        console.log("Product Image URL:", 'seller/' + productImage);


        /*
        document.querySelector(".increase").addEventListener("click", function () {
            let input = document.querySelector(".quantity input");
            input.value = parseInt(input.value) + 1;
        });

        document.querySelector(".decrease").addEventListener("click", function () {
            let input = document.querySelector(".quantity input");
            let value = parseInt(input.value);
            if (value > 1) { // prevent going below 1
                input.value = value - 1;
            }
        });
        */

        document.querySelector('.buy-now').addEventListener('click', (e) => {
            e.preventDefault(); // ✅ stop the form from submitting immediately

            const isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;

            if (!isLoggedIn) {
                alert("You have to login first.");
                window.location.href = "login.php"; // redirect to login
                return;
            }

            // If logged in -> submit form
            const form = document.getElementById('product-form');
            form.action = 'purchase.php';
            form.submit();
        });




        setTimeout(() => {
            const msg = document.getElementById('success-message');
            if (msg) msg.style.display = 'none';
        }, 2000);

    </script>
    <script src="app.js"></script>
</body>

</html>