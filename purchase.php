<?php
session_start();
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $product_id = $_GET['product_id'] ?? null;
    $quantity = (int) ($_GET['quantity'] ?? 0);
}



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'] ?? null; //?? null =>exists and is not null, use its value; otherwise, use null
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $d_price = (int) ($_POST['discounted_price'] ?? 0);
    $d_percent = (int) ($_POST['discount_percent'] ?? 0);
    $userID = $_SESSION['user_id'];
    echo "<script>
        console.log('Product ID:', " . json_encode($product_id) . ");
        console.log('Quantity:', " . json_encode($quantity) . ");
        console.log('D_price:', " . json_encode($d_price) . ");
    </script>";
    if ($product_id && $quantity > 0) {
        $db = new Database('localhost', 'bazar', 'root', '');

        // 1️⃣ Check current stock
        $sql = "SELECT product_stock FROM product WHERE product_id = ?";
        $stmt = $db->query($sql, [$product_id]);
        $row = $stmt->fetch();



        if ($row) {
            $current_stock = (int) $row['product_stock'];

            if ($current_stock >= $quantity) {
                // 2️⃣ Enough stock → update
                $updateSql = "
    UPDATE product 
    SET 
        product_stock = product_stock - ?, 
        sold = sold + ? 
    WHERE product_id = ?
";

                $insertSql = "
    INSERT INTO purchase_history (
        user_id,
        product_id,
        cost,
        sold,
        discount
    ) VALUES (?, ?, ?, ?, ?)
";
                $db->query($insertSql, [$userID, $product_id, $d_price, $quantity, $d_percent]);
                $db->query($updateSql, [$quantity, $quantity, $product_id]);
                // ✅ Success message
                header("Location: purchase.php?product_id=$product_id&success=1");
                exit;
            } else {
                // ❌ Not enough stock
                header("Location: purchase.php?product_id=$product_id&error=1");
                exit;
            }


        }
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

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $customer_info = $customer->getCustomerDetails($user_id);
    $product_info = $details->getProductDetails($product_id);
    $product_seller = $product_info['seller_id'];
    $seller_info = $details->getSellerName($product_seller);

    // Fetch district_id and location_id into separate variables
    $seller_name = $seller_info['shop_name'];
    $seller_location = $seller_info['shop_location'];
    $district_id = $customer_info['district_id'];
    $location_id = $customer_info['location_id'];
    $customer_name = $customer_info['full_name'];
    $customer_phone = $customer_info['phone_number'];
    $district_name = $customer->getDistrictName($district_id);
    $location_name = $customer->getLocationName($location_id);

    echo "<script>
    console.log('seller id is :', " . json_encode($product_seller) . ");
      console.log('seller name is :', " . json_encode($seller_name) . ");
            console.log('District ID:', " . json_encode($district_id) . ");
            console.log('Location ID:', " . json_encode($location_id) . ");
            console.log('District ID:', " . json_encode($district_name) . ");
            console.log('Location ID:', " . json_encode($location_name) . ");
            
            
            console.log('customer name:', " . json_encode($customer_name) . ");
        </script>";



} else {
    echo "NO CUSTOMER FOUND OF THIS USER ID";
}



if (isset($product_id)) {
    $productt_id = $product_id;

    // Example: show the product ID

    $productdetail = $details->getProductDetails($productt_id);
} else {
    echo "No product selected.";
    exit;
}
?>


<html>

<head>



    <title>Product Detail</title>
    <link rel="stylesheet" href="purchasestyle.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <!-- Add Font Awesome for icon -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
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

    <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Purchase Failed!',
                text: '❌ Not enough stock available!',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>



    <div class="product_storage">

        <div class="seller_home">
            <h2> <img src="img/shopp.png" alt="Shop Logo">
                <?php
                echo ucwords($seller_name) . "        - " . ucwords($seller_location);
                ?>
            </h2>
        </div>

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
            <h1><?php echo htmlspecialchars($productdetail['product_name']); ?></h1>
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
                <p style="color:red; font-size: 20px;text-decoration: line-through;
    color: gray;"> Rs <?php echo htmlspecialchars($productdetail['product_amount']); ?></p>
                <p style="color:blue; font-size: 18px; position: relative; left: 10px;">
                    (<?php echo htmlspecialchars($productdetail['discount_percent']); ?>% discount)
                </p>
            </div>
            </h1>

            <form id="product-form" method="POST" action="purchase.php">

                <!-- Discounted price inside form -->
                <?php
                $price = $productdetail['product_amount'];
                $discount = $productdetail['discount_percent'];
                $discounted_price = $price - ($price * $discount / 100);
                ?>

                <!-- Hidden input to POST discounted price -->
                <input type="hidden" name="discounted_price" value="<?php echo $discounted_price; ?>">
                <input type="hidden" name="discount_percent" value="<?php echo $discount; ?>">

                <div class="quantity-wrapper">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                    <label>Quantity :</label>
                    <div class="quantity">
                        <button type="button" class="decrease">-</button>
                        <input type="text" name="quantity" value="1">
                        <button type="button" class="increase">+</button>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="button" class="buy-now">Buy Product</button>
                    <button type="button" class="add-to-cart">Add to Cart</button>
                </div>
            </form>
            <?php if (isset($_GET['success'])): ?>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Purchase Successful!',
                        // text: '✅ Successfully purchased product!',
                        html: '✅ Successfully purchased product.<br>🛒 Check your list items!',
                        showConfirmButton: false,
                        timer: 4000
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                </script>
            <?php endif; ?>
        </div>

    </div>








    <script>

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


        document.querySelector('.buy-now').addEventListener('click', () => {
            const form = document.getElementById('product-form');
            // Optional: you can modify hidden input or validate here
            form.action = 'purchase.php';   // target PHP file
            form.submit();             // submit the form with POST
        });

        document.querySelector('.add-to-cart').addEventListener('click', () => {
            const form = document.getElementById('product-form');
            form.action = 'add_to_cart.php'; // separate PHP file for cart
            form.submit();
        });

        setTimeout(() => {
            const msg = document.getElementById('success-message');
            if (msg) msg.style.display = 'none';
        }, 2000);

    </script>

</body>

</html>