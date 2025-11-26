<?php
session_start();
require_once 'database.php';

$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

$db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents('php://input'), true);
$cart_id = $data['cart_id'] ?? null;

if ($cart_id && isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
    $success = $stmt->execute([$cart_id, $_SESSION['user_id']]);
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete item.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
