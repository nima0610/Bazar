<?php
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 0;
    echo "<script>
console.log('Product ID: $product_id');
console.log('Quantity: $quantity');
</script>";

    if ($product_id && $quantity > 0) {
        $db = new Database('localhost', 'bazar', 'root', ''); // adjust credentials
        $sql = "UPDATE product SET product_stock = product_stock - ? WHERE product_id = ?";
        $db->query($sql, [$quantity, $product_id]);
    }

    // Return a JSON response (no page load)
    echo json_encode(['success' => true]);
    echo "<script>console.log('Successfully updated database');</script>";
}
?>