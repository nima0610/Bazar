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


    $customerpurchases = $customer->getCustomerPurchases($user_id);

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
    <title>My Acitivity</title>
    <link rel="stylesheet" href="activity.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
</head>

<body>
    <div class="customer_detail">
        <div class="map_img">
            <img src="img/mapp.png" alt="Product Image" />
        </div>
        <div class="user_detail">
            <div class="first_line">
                <h2>
                    <?php
                    echo ucwords($customer_name);
                    ?>
                </h2>

                <h3>
                    <?php
                    echo $customer_phone;
                    ?>
                </h3>
            </div>

            <div class="second_line">
                <h3>
                    <p>HOME</p>
                </h3>
                <h3>
                    <?php
                    echo $location_name, ",", $district_name;
                    ?>
                </h3>
            </div>

            <div class="third_line">
                <h3>Collect your parcel from the nearest Bazar Pickup point with a reduced shipping fee.</h3>
            </div>

        </div>
    </div>

    <script>
        console.log("the user id is ", <?php echo $_SESSION['user_id']; ?>)
        console.log("the user name is", <?php echo json_encode($_SESSION['username']); ?>);
    </script>


    <div class="main_container">

        <?php
        if (!empty($customerpurchases)) {
            foreach ($customerpurchases as $purchase):

                $purchaseId = $purchase['id'];   // unique purchase ID
                $productId = $purchase['product_id'];
                $cost = $purchase['cost'];

                // fetch images for this product
                $productdetail = $customer->getPurchasedPic($productId);
                $purchase_images = explode(',', $productdetail['product_image']);
                ?>

                <div class="divider" id="purchase-<?php echo $purchaseId; ?>">

                    <!-- Edit / Cancel buttons -->
                    <div class="action-buttons">
                        <a href="edit.php?id=<?php echo $productId; ?>" class="edit-btn">Edit Order</a>
                        <button class="cancel-btn" data-purchase-id="<?php echo $purchaseId; ?>">Cancel Order</button>
                    </div>

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
                        <p><?php echo ucfirst($productdetail['description']); ?></p>
                        <p> Rs <?php echo $cost; ?></p>
                        <p>Quantity: <?php echo $purchase['sold']; ?></p>

                        <div class="similarity">
                            <a href="your-link-here" class="similar-link">Find Similar</a>
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




</body>

</html>