<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$temp_location = trim($data['location'] ?? '');

if (!$temp_location || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = new Database('localhost', 'bazar', 'root', '');

// Save temporary location in session (for UI)
$_SESSION['temp_location'] = $temp_location;

// Optional: save in database for this order
// If you have a `temporary_addresses` table OR store in `purchase_history` later
// Example: UPDATE purchase_history SET delivery_address = ? WHERE user_id = ? AND status='pending'
/*
$sql = "UPDATE purchase_history SET delivery_address=?, type='temporary' 
        WHERE user_id=? AND status='pending'";
$db->query($sql, [$temp_location, $user_id]);
*/

echo json_encode(['success' => true]);
