<?php
session_start();
require 'db.php';
require 'category.php';
require 'product.php';

// DB connection
$db = new Database("localhost", "bazar", "root", "");
$category = new Category($db);
$product = new Product($db);

// ---- Add this delete logic here ----
if (isset($_GET['delete_id'])) {
    $product_id = $_GET['delete_id'];

    // Check if product has orders
    if ($product->hasOrders($product_id)) {
        echo "<script>alert('Cannot delete! Product has existing orders.');</script>";
    } else {
        // Soft delete the product
        if ($product->softDeleteProduct($product_id)) {
            echo "<script>alert('Product deleted successfully.');
            window.location.href='dashboard.php#products';</script>";
        } else {
            echo "<script>alert('Failed to delete product.');
            window.location.href='seller_dashboard.php#products';
            </script>";
        }
    }
}
// ---- Delete logic ends ----

// Now fetch seller products
$seller_id = $_SESSION['user_id']; // or however you get seller id
$sellerProducts = $product->getProductsBySeller($seller_id);
