<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_GET['product_id'] ?? null;
$quantity = isset($_GET['quantity']) ? (int) $_GET['quantity'] : 1;

if (!$product_id || $quantity <= 0) {
    header("Location: product_details.php?id={$product_id}&error=1");
    exit;
}

$db = new Database('localhost', 'bazar', 'root', '');

// 1️⃣ Check if product is already in cart
$stmt = $db->prepare("SELECT * FROM cart WHERE user_id=? AND product_id=?");
$stmt->execute([$user_id, $product_id]);
$existing = $stmt->fetch();

if ($existing) {
    // Already in cart, redirect
    header("Location: product_details.php?id={$product_id}&added=1");
    exit;
}

// 2️⃣ Insert into cart
$stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $product_id, $quantity]);

// 3️⃣ Redirect with success
header("Location: product_details.php?id={$product_id}&added=1");
exit;
?>