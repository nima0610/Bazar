<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'database.php';

$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

try {
    $db = new Database($host, $dbname, $user, $pass);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $e->getMessage()]);
    exit;
}

// Read JSON input from AJAX
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['purchase_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$purchaseId = intval($input['purchase_id']);
$customerId = $_SESSION['user_id'] ?? 0;

try {
    // Fetch purchase info
    $stmt = $db->prepare("SELECT product_id, sold FROM purchase_history WHERE id = :id AND user_id = :customer");
    $stmt->bindValue(':id', $purchaseId, PDO::PARAM_INT);
    $stmt->bindValue(':customer', $customerId, PDO::PARAM_INT);
    $stmt->execute();
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        echo json_encode(['success' => false, 'message' => 'Purchase not found']);
        exit;
    }

    $productId = $purchase['product_id'];
    $quantity = $purchase['sold'];

    // Delete purchase row
    $deleteStmt = $db->prepare("DELETE FROM purchase_history WHERE id = :id");
    $deleteStmt->bindValue(':id', $purchaseId, PDO::PARAM_INT);
    $deleteStmt->execute();

    // Update product quantity
    $updateStmt = $db->prepare("UPDATE product SET product_stock = product_stock + :qty, sold = sold - :qty WHERE product_id = :productId");
    $updateStmt->bindValue(':qty', $quantity, PDO::PARAM_INT);
    $updateStmt->bindValue(':productId', $productId, PDO::PARAM_INT);
    $updateStmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
