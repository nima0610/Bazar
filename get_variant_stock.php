<?php
require_once 'database.php';
require_once 'details_product.php';

$product_id = $_GET['product_id'] ?? null;
$size = $_GET['size'] ?? null;
$color = $_GET['color'] ?? null;

$db = new Database("localhost", "bazar", "root", "");
$details = new Details($db);

if ($product_id && $size !== null && $color !== null) {
    $row = $details->getStockFromVariants($product_id, $size, $color);
    if ($row) {
        echo $row['stock'];
    } else {
        echo 0;
    }
} else {
    echo 0;
}
?>