<?php
session_start();
require_once "database.php";

$userId = $_SESSION['user_id'] ?? null;
if (!$userId)
    exit(json_encode(['status' => 'error']));

$orderIds = $_POST['order_ids'] ?? '';
if (!$orderIds)
    exit(json_encode(['status' => 'error']));

$orderIdsArr = explode(',', $orderIds);

// Prepare placeholders for PDO
$placeholders = implode(',', array_fill(0, count($orderIdsArr), '?'));

$db = new Database('localhost', 'bazar', 'root', '');
$stmt = $db->prepare("
    UPDATE purchase_history
    SET review_status = 'skipped'
    WHERE id IN ($placeholders) AND user_id = ?
");

// Bind all IDs
foreach ($orderIdsArr as $k => $id) {
    $stmt->bindValue($k + 1, $id);
}
$stmt->bindValue(count($orderIdsArr) + 1, $userId);
$stmt->execute();

echo json_encode(['status' => 'success']);
?>