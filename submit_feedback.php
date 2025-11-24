<?php
session_start();
require_once 'database.php';

$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

$db = new Database($host, $dbname, $user, $pass);

if (!isset($_POST['product_id'], $_POST['rating'])) {
    die("Invalid request");
}

$purchase_id = $_POST['purchase_id'];
$product_id = $_POST['product_id'];
$user_id = $_SESSION['user_id'];
$rating = $_POST['rating'];
$review = $_POST['review_text'];

// Insert into reviews table
$db->query("
    INSERT INTO reviews (user_id, product_id, rating, review_text)
    VALUES (?, ?, ?, ?)
", [$user_id, $product_id, $rating, $review]);

// Update purchase status
$db->query("
    UPDATE purchase_history
    SET review_status = 'done'
    WHERE id = ?
", [$purchase_id]);

header("Location: feedback.php?success=1");
exit;
