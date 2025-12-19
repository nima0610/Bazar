<?php
session_start();
require_once 'database.php';
require_once 'details_product.php';
require_once 'customer_details.php';
require_once 'database.php';


$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

$db = new Database($host, $dbname, $user, $pass);

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $product_id = $_GET['product_id'] ?? null;
    $quantity = (int) ($_GET['quantity'] ?? 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $userID = $_SESSION['user_id'] ?? null;
    if ($userID) {
        $customer = new CustomerDetails($db);
        $customer_info = $customer->getCustomerDetails($userID);
        $customer_phone = $customer_info['phone_number'] ?? '';
        $district_name = $customer->getDistrictName($customer_info['district_id'] ?? null);
        $location_name = $customer->getLocationName($customer_info['location_id'] ?? null);
        $customer_name = $customer_info['full_name'] ?? '';

    }

    $product_id = $_POST['product_id'] ?? null;
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $d_price = (float) ($_POST['discounted_price'] ?? 0);
    $d_percent = (float) ($_POST['discount_percent'] ?? 0);
    $userID = $_SESSION['user_id'] ?? null;

    // optional: values sent by JS when selecting options
    $selected_variant_id = isset($_POST['selected_variant_id']) && $_POST['selected_variant_id'] !== '' ? (int) $_POST['selected_variant_id'] : null;
    $selected_size = $_POST['selected_size'] ?? null;
    $selected_color = $_POST['selected_color'] ?? null;

    if (!$userID) {
        header("Location: login.php");
        exit;
    }

    if ($product_id && $quantity > 0) {
        $db = new Database('localhost', 'bazar', 'root', '');

        // Load product basic info
        $sql = "SELECT product_stock, seller_id FROM product WHERE product_id = ?";
        $stmt = $db->query($sql, [$product_id]);
        $productRow = $stmt->fetch();

        if (!$productRow) {
            header("Location: purchase.php?product_id={$product_id}&error=2"); // product not found
            exit;
        }

        $product_stock = (int) $productRow['product_stock'];
        $seller_id = (int) $productRow['seller_id'];

        // Variant detection
        $variantRow = null;

        if ($selected_variant_id) {
            $stmt = $db->query("SELECT * FROM product_variants WHERE variant_id=? AND product_id=?", [$selected_variant_id, $product_id]);
            $variantRow = $stmt->fetch();
        } elseif ($selected_size || $selected_color) {
            $query = "SELECT * FROM product_variants WHERE product_id = ?";
            $params = [$product_id];

            if ($selected_size && $selected_color) {
                $query .= " AND size=? AND color=? LIMIT 1";
                $params[] = $selected_size;
                $params[] = $selected_color;
            } elseif ($selected_size) {
                $query .= " AND size=? LIMIT 1";
                $params[] = $selected_size;
            } elseif ($selected_color) {
                $query .= " AND color=? LIMIT 1";
                $params[] = $selected_color;
            }

            $stmt = $db->query($query, $params);
            $variantRow = $stmt->fetch();
        }

        // Determine available stock dynamically
        if ($variantRow) {
            $available_stock = (int) $variantRow['stock'];
            if ($available_stock <= 0) {
                header("Location: purchase.php?product_id={$product_id}&error=1"); // variant out of stock
                exit;
            }
            if ($available_stock < $quantity) {
                header("Location: purchase.php?product_id={$product_id}&error=1"); // not enough variant stock
                exit;
            }
        } else {
            // No variant selected, use main product stock
            if ($product_stock <= 0) {
                header("Location: purchase.php?product_id={$product_id}&error=1"); // product out of stock
                exit;
            }
            if ($product_stock < $quantity) {
                header("Location: purchase.php?product_id={$product_id}&error=1"); // not enough product stock
                exit;
            }
        }


        // Reduce stock
        if ($variantRow) {
            $db->query("UPDATE product_variants SET stock = stock - ? WHERE variant_id=?", [$quantity, $variantRow['variant_id']]);
        }

        $db->query("UPDATE product SET product_stock = product_stock - ?, sold = sold + ? WHERE product_id=?", [$quantity, $quantity, $product_id]);

        // Insert into purchase_history
        $total_cost = $d_price * $quantity;
        $delivery_address = $_SESSION['temp_location'] ?? ($location_name . ', ' . $district_name);
        $delivery_phone = $customer_phone; // use customer phone displayed on page
        $address_type = isset($_SESSION['temp_location']) ? 'temporary' : 'permanent';


        try {
            if ($variantRow) {
                $insertSql = "INSERT INTO purchase_history 
    (user_id, product_id, variant_size, variant_color, cost, sold, discount, seller_id, delivery_address, delivery_phone, type, name)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $db->query($insertSql, [
                    $userID,
                    $product_id,
                    $selected_size,
                    $selected_color,
                    $total_cost,
                    $quantity,
                    $d_percent,
                    $seller_id,
                    $delivery_address,
                    $delivery_phone,
                    $address_type,
                    $customer_name
                ]);

            } else {
                $insertSql = "INSERT INTO purchase_history 
    (user_id, product_id, cost, sold, discount, seller_id, delivery_address, delivery_phone, type, name)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $db->query($insertSql, [
                    $userID,
                    $product_id,
                    $total_cost,
                    $quantity,
                    $d_percent,
                    $seller_id,
                    $delivery_address,
                    $delivery_phone,
                    $address_type,
                    $customer_name
                ]);

            }
        } catch (Exception $e) {

            $insertSql = "INSERT INTO purchase_history 
    (user_id, product_id, cost, sold, discount, seller_id, delivery_address, delivery_phone, type, name)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $db->query($insertSql, [
                $userID,
                $product_id,
                $total_cost,
                $quantity,
                $d_percent,
                $seller_id,
                $delivery_address,
                $delivery_phone,
                $address_type,
                $customer_name
            ]);

        }

        unset($_SESSION['temp_location']);

        header("Location: purchase.php?product_id={$product_id}&success=1");
        exit;
    }
}
?>


<?php
require_once 'database.php';
require_once 'details_product.php';
require_once 'customer_details.php';

$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

$db = new Database($host, $dbname, $user, $pass);
$details = new Details($db);
$customer = new CustomerDetails($db);

// Ensure $product_id exists from GET (or POST earlier)
$product_id = $product_id ?? ($_GET['product_id'] ?? null);

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $customer_info = $customer->getCustomerDetails($user_id);
    $product_info = $details->getProductDetails($product_id);
    $product_seller = $product_info['seller_id'];
    $seller_info = $details->getSellerName($product_seller);

    $seller_name = $seller_info['shop_name'] ?? '';
    $seller_location = $seller_info['shop_location'] ?? '';
    $district_id = $customer_info['district_id'] ?? null;
    $location_id = $customer_info['location_id'] ?? null;
    $customer_name = $customer_info['full_name'] ?? '';
    $customer_phone = $customer_info['phone_number'] ?? '';
    $district_name = $customer->getDistrictName($district_id);
    $location_name = $customer->getLocationName($location_id);
} else {
    echo "NO CUSTOMER FOUND OF THIS USER ID";
    exit;
}

if ($product_id) {
    $productdetail = $details->getProductDetails($product_id);
} else {
    echo "No product selected.";
    exit;
}

// --- Fetch available variants (size/color) with stock > 0 ---
$variantsStmt = $db->query("SELECT variant_id, size, color, stock FROM product_variants WHERE product_id = ?", [$product_id]);
$variants = $variantsStmt->fetchAll(PDO::FETCH_ASSOC);
// Build unique sizes and colors (preserving first occurrence)
$sizes = [];
$colors = [];
foreach ($variants as $v) {
    if (!empty($v['size'])) {
        // use the size string as key
        if (!array_key_exists($v['size'], $sizes)) {
            $sizes[$v['size']] = true;
        }
    }
    if (!empty($v['color'])) {
        if (!array_key_exists($v['color'], $colors)) {
            $colors[$v['color']] = true;
        }
    }
}
$availableSizes = array_keys($sizes);
$availableColors = array_keys($colors);

?>



<?php
$product_review = $details->getProductReview($product_id);
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



<!doctype html>
<html>

<head>
    <title>Product Detail</title>
    <link rel="stylesheet" href="purchasestyle.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>


    <style>
        /* Minimal inline styling for option boxes (you can move to your CSS) */
        .option-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .option-box {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            cursor: pointer;
            user-select: none;
            min-width: 40px;
            text-align: center;
        }

        .option-box.selected {
            border-color: #007bff;
            background: #e7f1ff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.08);
        }

        .variant-label {
            margin-right: 8px;
            font-weight: 600;
        }
    </style>
</head>

<body>
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

    <div class="customer_detail">
        <div class="map_img" style="cursor:pointer;" onclick="setTemporaryLocation()">
            <img src="img/mapp.png" alt="map" />
        </div>
        <div class="user_detail">
            <div class="first_line">
                <h2><?php echo ucwords(htmlspecialchars($customer_name)); ?></h2>
                <h3><?php echo htmlspecialchars($customer_phone); ?></h3>
            </div>
            <div class="second_line">
                <h3>
                    <p>HOME</p>
                </h3>
                <h3 id="customer_location_display">
                    <?php
                    // Show temporary location if session exists, otherwise default
                    echo htmlspecialchars($_SESSION['temp_location'] ?? ($location_name . ', ' . $district_name));
                    ?>
                </h3>
            </div>
            <div class="third_line">
                <h3>Collect your parcel from the nearest Bazar Pickup point with a reduced shipping fee.</h3>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
        <script>
            Swal.fire({ icon: 'error', title: 'Purchase Failed!', text: '❌ Not enough stock available!', confirmButtonText: 'OK' });
        </script>
    <?php endif; ?>

    <div class="product_storage">
        <div class="seller_home">
            <h2><img src="img/shopp.png"
                    alt="shop"><?php echo ucwords(htmlspecialchars($seller_name . ' - ' . $seller_location)); ?></h2>
        </div>

        <div class="product_image">
            <div class="swiper mySwiper">
                <div class="swiper-wrapper">
                    <?php
                    $images = explode(',', $productdetail['product_image']);
                    foreach ($images as $image) {
                        $image = trim($image);
                        if (!empty($image)) {
                            echo '<div class="swiper-slide"><img src="seller/' . htmlspecialchars($image) . '" alt="Product Image"></div>';
                        }
                    }
                    ?>
                </div>
                <div class="swiper-button-next"><i class="fas fa-chevron-right"></i></div>
                <div class="swiper-button-prev"><i class="fas fa-chevron-left"></i></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>

        <div class="product_descript">
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
                <p style="color:blue;"><?php echo (int) $productdetail['sold']; ?> sold</p>
                <p style="color:blue;" id="remaining_stock_text">
                    Remaining :
                    <?php
                    if (!empty($availableSizes) || !empty($availableColors)) {
                        echo "--"; // show empty until size/color is selected
                    } else {
                        echo (int) $productdetail['product_stock']; // normal product
                    }
                    ?>
                </p>
            </div>
            <hr>

            <?php
            $price = $productdetail['product_amount'];
            $discount = $productdetail['discount_percent'];
            $discounted_price = $price - ($price * $discount / 100);
            ?>
            <div id="price-info" data-price="<?php echo $price; ?>" data-discount="<?php echo $discount; ?>">
                <?php if ((float) $discount > 0): ?>
                    <p style="color:red;" id="final-price">Price: Rs <?php echo number_format($discounted_price, 2); ?></p>
                    <div class="discount">
                        <p style="text-decoration:line-through;color:gray;" id="original-price">Rs
                            <?php echo number_format($price, 2); ?>
                        </p>
                        <p style="color:blue;">(<?php echo htmlspecialchars($discount); ?>% discount)</p>
                    </div>
                <?php else: ?>
                    <p style="color:red;" id="final-price">Price: Rs <?php echo number_format($price, 2); ?></p>
                <?php endif; ?>
            </div>

            <form id="product-form" method="POST" action="purchase.php">
                <input type="hidden" name="discounted_price" value="<?php echo htmlspecialchars($discounted_price); ?>">
                <input type="hidden" name="discount_percent" value="<?php echo htmlspecialchars($discount); ?>">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">

                <div class="quantity-wrapper">
                    <label>Quantity :</label>
                    <div class="quantity">
                        <button type="button" class="decrease">-</button>
                        <input type="text" name="quantity" value="1">
                        <button type="button" class="increase">+</button>
                    </div>
                </div>

                <!-- Variant UI -->
                <?php if (!empty($availableSizes) || !empty($availableColors)): ?>
                    <div class="variant-section" style="margin-top:12px;">

                        <?php if (!empty($availableSizes)): ?>
                            <div class="variant-row sizes-row">
                                <span class="variant-label">Size:</span>
                                <div class="option-row" id="sizeOptions">
                                    <?php foreach ($availableSizes as $size): ?>
                                        <div class="option-box" data-size="<?php echo htmlspecialchars($size); ?>"
                                            onclick="selectVariantOption(this, 'size')">
                                            <?php echo htmlspecialchars($size); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($availableColors)): ?>
                            <div class="variant-row colors-row" style="margin-top:10px;">
                                <span class="variant-label">Color:</span>
                                <div class="option-row" id="colorOptions">
                                    <?php foreach ($availableColors as $color): ?>
                                        <div class="option-box" data-color="<?php echo htmlspecialchars($color); ?>"
                                            onclick="selectVariantOption(this, 'color')">
                                            <?php echo htmlspecialchars($color); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="selected_size" id="selected_size">
                        <input type="hidden" name="selected_color" id="selected_color">
                        <input type="hidden" name="selected_variant_id" id="selected_variant_id">
                    </div>

                <?php endif; ?>

                <div class="action-buttons" style="margin-top:12px;">
                    <button type="button" class="buy-now">Buy Product</button>
                    <button type="button" class="add-to-cart">Add to Cart</button>
                </div>
            </form>

            <?php if (isset($_GET['success'])): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Purchase Successful!',
                        html: '✅ Successfully purchased product.<br>🛒 Check your list items!',
                        showConfirmButton: false,
                        timer: 4000
                    }).then(() => { window.location.href = 'index.php'; });
                </script>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Swiper init
        var swiper = new Swiper(".mySwiper", {
            loop: true,
            navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
            pagination: { el: ".swiper-pagination", clickable: true },
            slidesPerView: 1,
            spaceBetween: 10,
        });

        // Quantity buttons
        document.querySelector(".increase").addEventListener("click", function () {
            let input = document.querySelector(".quantity input");
            input.value = parseInt(input.value) + 1;
        });
        document.querySelector(".decrease").addEventListener("click", function () {
            let input = document.querySelector(".quantity input");
            let v = parseInt(input.value);
            if (v > 1) input.value = v - 1;
        });

        // Variant selection logic
        // We keep a local cache of variants (variant rows) to look up variant_id when both size & color selected.
        const variants = <?php echo json_encode($variants); ?>;

        function selectVariantOption(element, type) {
            const container = element.parentElement;
            // Deselect siblings
            container.querySelectorAll('.option-box').forEach(box => box.classList.remove('selected'));
            element.classList.add('selected');

            if (type === 'size') {
                document.getElementById('selected_size').value = element.dataset.size || '';
            } else if (type === 'color') {
                document.getElementById('selected_color').value = element.dataset.color || '';
            }

            // Try to resolve variant_id based on currently selected size/color
            resolveVariantId();
        }
        function resolveVariantId() {
            const size = document.getElementById('selected_size').value || null;
            const color = document.getElementById('selected_color').value || null;

            let found = null;

            if (size && color) {
                found = variants.find(v => v.size == size && v.color == color);
            } else if (size) {
                found = variants.filter(v => v.size == size)[0];
            } else if (color) {
                found = variants.filter(v => v.color == color)[0];
            }

            document.getElementById('selected_variant_id').value = found ? found.variant_id : '';

            updateRemainingStock();
        }




        function updateRemainingStock() {
            const size = document.getElementById('selected_size').value;
            const color = document.getElementById('selected_color').value;

            if (!size && !color) {
                document.getElementById('remaining_stock_text').innerHTML = "Remaining : --";
                return;
            }

            const match = variants.find(v =>
                (!size || v.size == size) &&
                (!color || v.color == color)
            );

            if (!match) {
                document.getElementById('remaining_stock_text').innerHTML = "Remaining : 0";
                return;
            }

            document.getElementById('remaining_stock_text').innerHTML = "Remaining : " + match.stock;
        }

        function setTemporaryLocation() {
            Swal.fire({
                title: 'Enter your temporary delivery location',
                input: 'text',
                inputPlaceholder: 'e.g., Kathmandu, Thamel',
                showCancelButton: true,
                confirmButtonText: 'Save',
                cancelButtonText: 'Cancel',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please enter a location!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const tempLocation = result.value;

                    // Send it to server via AJAX
                    fetch('set_temp_location.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ location: tempLocation })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update the displayed location dynamically
                                document.getElementById('customer_location_display').innerText = tempLocation;
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Location updated!',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire('Error', 'Failed to save location. Please try again.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('Error', 'Something went wrong.', 'error');
                        });
                }
            });
        }


        // Buy and Add to Cart handlers - unchanged behavior (Buy -> POST to purchase.php; Add to Cart -> goes to add_to_cart.php)
        document.querySelector('.buy-now').addEventListener('click', (e) => {
            e.preventDefault();
            const form = document.getElementById('product-form');

            if (variants.length > 0) {
                // Product has variants
                const selectedVariantId = document.getElementById('selected_variant_id').value;
                if (!selectedVariantId) {
                    alert('Please select a variant.');
                    return;
                }

                const variant = variants.find(v => v.variant_id == selectedVariantId);
                if (!variant || parseInt(variant.stock) <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Purchase Failed!',
                        text: '❌ Selected variant is out of stock!'
                    });
                    return;
                }
            }

            // Product has no variants OR variant checks passed
            form.action = 'purchase.php';
            form.method = 'POST';
            form.submit();
        });

        // Grab the inputs
        const quantityInput = document.querySelector(".quantity input");
        const finalPriceEl = document.getElementById("final-price");
        const originalPriceEl = document.getElementById("original-price");
        const priceInfoEl = document.getElementById("price-info");

        // Grab price data from PHP
        const basePrice = parseFloat(priceInfoEl.dataset.price);
        const discountPercent = parseFloat(priceInfoEl.dataset.discount) || 0;

        function updatePrice() {
            const qty = parseInt(quantityInput.value) || 1;
            let discountedPrice = basePrice - (basePrice * discountPercent / 100);
            let totalPrice = discountedPrice * qty;
            finalPriceEl.innerText = `Price: Rs ${totalPrice.toFixed(2)}`;

            if (discountPercent > 0 && originalPriceEl) {
                let totalOriginal = basePrice * qty;
                originalPriceEl.innerText = `Rs ${totalOriginal.toFixed(2)}`;
            }
        }

        // Attach event listeners to quantity buttons and input change
        document.querySelector(".increase").addEventListener("click", () => { updatePrice(); });
        document.querySelector(".decrease").addEventListener("click", () => { updatePrice(); });
        quantityInput.addEventListener("input", () => { updatePrice(); });

        // Initial update
        updatePrice();


        document.querySelector('.add-to-cart').addEventListener('click', (e) => {
            e.preventDefault();
            const form = document.getElementById('product-form');
            // add-to-cart expects GET in your earlier code (add_to_cart.php handles POST in other flow) — your existing app changed form.action via JS
            form.method = 'GET';
            form.action = 'add_to_cart.php';
            // include variant info as query parameters: use hidden inputs (they are included) but because method=GET, browser will append them
            form.submit();
        });

        // Hide any flash messages after a short time (unchanged)
        setTimeout(() => {
            const msg = document.getElementById('success-message');
            if (msg) msg.style.display = 'none';
        }, 2000);
    </script>



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
                    on Bazar!"
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

</body>

</html>