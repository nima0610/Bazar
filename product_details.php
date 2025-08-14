<?php
require_once 'database.php';
require_once 'details_product.php';

$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

$db = new Database($host, $dbname, $user, $pass);

$details = new Details($db);


if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Example: show the product ID

    $productdetail = $details->getProductDetails($product_id);
} else {
    echo "No product selected.";
    exit;
}
?>


<html>

<head>
    <title>Product Detail</title>
    <link rel="stylesheet" href="productstyle.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add Font Awesome for icon -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
</head>

<body>

    <div class="main">
        <div class="side_options">
            <a href="#">Become a Seller</a>
            <a href="login.php">Login</a>
            <a href="registration.php">Signup</a>
            <a href="#">Help and Support</a>
        </div>
        <div class="logo_main">
            <img src="assets/bazari.png">
        </div>
        <div class="search_bar">
            <form method="POST">
                <input type="text" name="searcher" placeholder="Search Products in Bazar">
                <button class="search-button">🔍</button>
            </form>
        </div>
    </div>

    <div class="product_storage">
        <div class="product_image">
            <img src="<?php echo htmlspecialchars('seller/' . $productdetail['product_image']); ?>" alt="Product Image">
        </div>
        <div class="product_descript">
            <h1>
                <h1><?php echo htmlspecialchars($productdetail['product_name']); ?></h1>
                <p style="color:blue; font-size: 18px; position: relative; left: 10px;">
                    <?php echo htmlspecialchars($productdetail['sold']); ?> sold
                </p>
                <hr style="border: 1px solid #000; width: 100%; text-align: center;">
                <p style="color:red; font-size: 24px; position: relative; left: 10px;">Price: Rs
                    <?php echo htmlspecialchars($productdetail['product_amount']); ?>
                </p>
                <p style="color:red; font-size: 20px;text-decoration: line-through;
    color: gray;"> Rs <?php echo htmlspecialchars($productdetail['product_amount']); ?></p>
            </h1>

        </div>
        <div class="product_second_descript">
            <h1>WAITING FOR PROGRESS</h1>
        </div>
    </div>








    <script>
        const productImage = "<?php echo htmlspecialchars($productdetail['product_image']); ?>";
        console.log("Product Image URL:", 'seller/' + productImage);
    </script>

</body>

</html>