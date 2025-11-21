<?php
session_start();
require_once 'database.php';

$host = "localhost";
$dbname = "bazar";
$user = "root";
$pass = "";

$db = new Database($host, $dbname, $user, $pass);

$data = json_decode(file_get_contents("php://input"), true);

$purchase_id = intval($data['purchase_id']);
$newQty = intval($data['new_qty']);

try {

    // fetch current purchase
    $stmt = $db->prepare("SELECT product_id, sold FROM purchase_history WHERE id = :id");
    $stmt->bindValue(":id", $purchase_id);
    $stmt->execute();
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        echo json_encode(['success' => false, 'message' => 'Purchase not found']);
        exit;
    }

    $oldQty = intval($purchase['sold']);
    $productId = intval($purchase['product_id']);

    // calculate difference
    $diff = $newQty - $oldQty;

    // If quantity is increased, verify stock
    if ($diff > 0) {

        // fetch remaining stock
        $stmt = $db->prepare("SELECT product_stock FROM product WHERE product_id = :pid");
        $stmt->bindValue(":pid", $productId);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        $currentStock = intval($product['product_stock']);

        // Not enough stock
        if ($diff > $currentStock) {
            echo json_encode([
                'success' => false,
                'message' => 'Not enough stock available!'
            ]);
            exit;
        }
    }

    // update purchase history
    $stmt = $db->prepare("UPDATE purchase_history SET sold = :qty WHERE id = :id");
    $stmt->bindValue(":qty", $newQty);
    $stmt->bindValue(":id", $purchase_id);
    $stmt->execute();

    // update product stock
    $stmt = $db->prepare("UPDATE product SET product_stock = product_stock - :diff WHERE product_id = :pid");
    $stmt->bindValue(":diff", $diff);
    $stmt->bindValue(":pid", $productId);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}