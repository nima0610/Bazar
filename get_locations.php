<?php
require 'db.php';

$districtId = $_GET['district_id'] ?? 0;

$stmt = $pdo->prepare("SELECT id, name FROM locations WHERE district_id = :district_id ORDER BY name ASC");
$stmt->execute([':district_id' => $districtId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
