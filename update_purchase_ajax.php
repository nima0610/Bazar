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
    $stmt = $db->prepare("SELECT product_id, sold, variant_size, variant_color FROM purchase_history WHERE id = :id");
    $stmt->bindValue(":id", $purchase_id);
    $stmt->execute();
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        echo json_encode(['success' => false, 'message' => 'Purchase not found']);
        exit;
    }

    $oldQty = intval($purchase['sold']);
    $productId = intval($purchase['product_id']);
    $variantSize = $purchase['variant_size'];
    $variantColor = $purchase['variant_color'];


    $diff = $newQty - $oldQty; // positive if increasing quantity

    if ($diff > 0) {
        if ($variantSize || $variantColor) {
            // Product with size/color variant
            $stmt = $db->prepare("SELECT stock FROM product_variants WHERE product_id = :pid AND size = :size AND color = :color LIMIT 1");
            $stmt->bindValue(":pid", $productId);
            $stmt->bindValue(":size", $variantSize);
            $stmt->bindValue(":color", $variantColor);
            $stmt->execute();
            $variant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$variant) {
                echo json_encode(['success' => false, 'message' => 'Variant not found']);
                exit;
            }

            $availableStock = intval($variant['stock']);

            if ($diff > $availableStock) {
                echo json_encode(['success' => false, 'message' => 'Not enough stock for this variant!']);
                exit;
            }

            // update variant stock
            $stmt = $db->prepare("UPDATE product_variants SET stock = stock - :diff WHERE product_id = :pid AND size = :size AND color = :color");
            $stmt->bindValue(":diff", $diff);
            $stmt->bindValue(":pid", $productId);
            $stmt->bindValue(":size", $variantSize);
            $stmt->bindValue(":color", $variantColor);
            $stmt->execute();

        } else {
            // Existing logic for normal product
            $stmt = $db->prepare("SELECT product_stock FROM product WHERE product_id = :pid");
            $stmt->bindValue(":pid", $productId);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }

            $currentStock = intval($product['product_stock']);

            if ($diff > $currentStock) {
                echo json_encode(['success' => false, 'message' => 'Not enough stock available!']);
                exit;
            }

            // update main product stock
            $stmt = $db->prepare("UPDATE product SET product_stock = product_stock - :diff WHERE product_id = :pid");
            $stmt->bindValue(":diff", $diff);
            $stmt->bindValue(":pid", $productId);
            $stmt->execute();
        }
    } elseif ($diff < 0) {
        // If quantity is reduced, return stock back
        if ($variantSize || $variantColor) {
            $stmt = $db->prepare("UPDATE product_variants SET stock = stock + :diff_abs WHERE product_id = :pid AND size = :size AND color = :color");
            $stmt->bindValue(":diff_abs", abs($diff));
            $stmt->bindValue(":pid", $productId);
            $stmt->bindValue(":size", $variantSize);
            $stmt->bindValue(":color", $variantColor);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("UPDATE product SET product_stock = product_stock + :diff_abs WHERE product_id = :pid");
            $stmt->bindValue(":diff_abs", abs($diff));
            $stmt->bindValue(":pid", $productId);
            $stmt->execute();
        }
    }

    // update purchase history
    $stmt = $db->prepare("UPDATE purchase_history SET sold = :qty WHERE id = :id");
    $stmt->bindValue(":qty", $newQty);
    $stmt->bindValue(":id", $purchase_id);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>