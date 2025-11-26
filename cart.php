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
$details = new Details($db);
$customer = new CustomerDetails($db);



if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $customer_info = $customer->getCustomerDetails($user_id);
    $district_id = $customer_info['district_id'];
    $location_id = $customer_info['location_id'];
    $customer_name = $customer_info['full_name'];
    $customer_phone = $customer_info['phone_number'];
    $district_name = $customer->getDistrictName($district_id);
    $location_name = $customer->getLocationName($location_id);
    $userrid = $customer->getPurchasedDetails($user_id);


    $customerpurchases = $customer->getCustomerCarts($user_id);

    if ($userrid && isset($userrid['product_id'])) {
        $product_idd = $userrid['product_id'];
        $product_details = $customer->getPurchasedProduct($product_idd);
        $purchase_image = $customer->getPurchasedPic($product_idd);
    } else {
        // Handle the case when there is no purchase
        $product_idd = null;
        $product_details = null;
        $purchase_image = null;
    }
} else {
    echo "NO CUSTOMER FOUND OF THIS USER ID";
}


?>


<html>

<head>
    <title>My Carts</title>
    <link rel="stylesheet" href="activity.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
</head>

<body>

    <div class="main">
        <div class="side_options">
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


    <script>
        console.log("the user id is ", <?php echo $_SESSION['user_id']; ?>)
        console.log("the user name is", <?php echo json_encode($_SESSION['username']); ?>);
    </script>


    <div class="main_container">

        <div class="title_part">
            <h1>Cart Lists 🛒</h1>
        </div>
        <hr>

        <?php
        if (!empty($customerpurchases)) {
            foreach ($customerpurchases as $purchase):

                $purchaseId = $purchase['cart_id'];   // unique cart ID
                $productId = $purchase['product_id'];

                $avgRating = $customer->getAverageRating($productId); // Implement this in your CustomerDetails class
                $avgRating = round($avgRating, 1); // Round to 1 decimal for display
        


                // fetch images for this product
                $productdetail = $customer->getPurchasedPic($productId);
                $purchase_images = explode(',', $productdetail['product_image']);
                $costwithoutdiscount = $productdetail['product_amount'];
                $discount = $productdetail['discount_percent'];
                $discountAmount = ($costwithoutdiscount * $discount) / 100;
                $cost = $costwithoutdiscount - $discountAmount;
                ?>

                <div class="divider_copy" id="purchase-<?php echo $purchaseId; ?>">

                    <!-- Edit / Cancel buttons -->


                    <div class="product_image">
                        <!-- Swiper container for this product -->
                        <div class="swiper mySwiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($purchase_images as $image):
                                    $image = trim($image);
                                    if (!empty($image)):
                                        ?>
                                        <div class="swiper-slide">
                                            <img src="seller/<?php echo htmlspecialchars($image); ?>" alt="Product Image">
                                        </div>
                                    <?php endif; endforeach; ?>
                            </div>

                            <!-- Optional navigation buttons -->
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-pagination"></div>
                        </div>
                    </div>

                    <!-- Purchase info card -->
                    <div class="purchase-card">
                        <h1><?php echo ucfirst($productdetail['product_name']); ?></h1>
                        <!-- Star rating -->
                        <div class="star-rating">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= floor($avgRating)) {
                                    echo '<span class="star filled">&#9733;</span>'; // full yellow star
                                } elseif ($i - $avgRating < 1) {
                                    echo '<span class="star half">&#9733;</span>'; // optional half star if you want
                                } else {
                                    echo '<span class="star">&#9733;</span>'; // empty star
                                }
                            }
                            ?>
                            <span class="rating-value">(<?php echo $avgRating; ?>)</span>
                        </div>
                        <p><?php echo ucfirst($productdetail['description']); ?></p>
                        <p> Rs <?php echo $cost; ?></p>
                        <p>Total Sold: <?php echo $productdetail['sold']; ?></p>




                        <div class="purchase-actions">
                            <form class="buy-now-form" method="GET" action="purchase.php">
                                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                <input type="hidden" name="quantity" value="<?php echo $productdetail['sold']; ?>">
                                <button type="submit" class="buy-now-btn">Buy Product</button>
                            </form>

                            <button class="remove-cart-btn" data-cart-id="<?php echo $purchaseId; ?>">Remove from Cart</button>
                        </div>
                    </div>
                </div>
                <hr>

                <?php
            endforeach;
        } else {
            echo "No purchased product found for this user.";
        }
        ?>

    </div>

    <!-- Swiper Initialization -->
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

    <!-- AJAX Cancel Order -->
    <script>
        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function () {
                const purchaseId = this.dataset.purchaseId;

                if (!confirm("Are you sure you want to cancel this order?")) return;

                fetch('delete_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ purchase_id: purchaseId })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the product row
                            const row = document.getElementById('purchase-' + purchaseId);
                            row.remove();
                            alert("Order canceled successfully!");
                        } else {
                            alert("Error: " + data.message);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert("Something went wrong!");
                    });
            });
        });


    </script>

    <!-- Edit Popup Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Order</h2>

            <label>New Quantity:</label>
            <input type="number" id="editQty" min="1">

            <button id="saveEdit">Save</button>
            <button id="closeEdit">Cancel</button>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            width: 300px;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }
    </style>

    <script>

        let currentPurchaseId = null;

        // OPEN POPUP
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                currentPurchaseId = this.dataset.purchaseId;
                const qty = this.dataset.quantity;

                document.getElementById('editQty').value = qty;
                document.getElementById('editModal').style.display = "flex";
            });
        });

        // CLOSE POPUP
        document.getElementById('closeEdit').addEventListener('click', function () {
            document.getElementById('editModal').style.display = "none";
        });

        // SAVE EDIT
        document.getElementById('saveEdit').addEventListener('click', function () {
            const newQty = document.getElementById('editQty').value;

            fetch('update_purchase_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    purchase_id: currentPurchaseId,
                    new_qty: newQty
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Order updated!");

                        location.reload(); // reload page to update quantity
                    } else {
                        alert("Error: " + data.message);
                    }
                });
        });



        document.querySelectorAll('.remove-cart-btn').forEach(button => {
            button.addEventListener('click', function () {
                const cartId = this.dataset.cartId;

                if (!confirm("Are you sure you want to remove this item from cart?")) return;

                fetch('delete_cart_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart_id: cartId })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from DOM
                            const row = document.getElementById('purchase-' + cartId);
                            if (row) row.remove();
                            alert("Item removed from cart successfully!");
                        } else {
                            alert("Error: " + data.message);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert("Something went wrong!");
                    });
            });
        });
    </script>
</body>

</html>