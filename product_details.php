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
    echo "Product ID: " . $product_id;
    $productdetail = $details->getProductDetails($product_id);
} else {
    echo "No product selected.";
    exit;
}
?>


<html>

<head>
    <title>Product Detail</title>
</head>

<body>

    <?php if ($productdetail): ?>
        <h1><?php echo htmlspecialchars($productdetail['product_name']); ?></h1>
        <p><?php echo htmlspecialchars($productdetail['description']); ?></p>
        <p>Price: <?php echo htmlspecialchars($productdetail['product_amount']); ?></p>
    <?php else: ?>
        <p>Product not found.</p>
    <?php endif; ?>

    <p>hello world this is me nima sherpa from bachelor and this is my first attempt for product
        description page.
    </p>
</body>

</html>