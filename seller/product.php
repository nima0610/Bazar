<?php
class Product
{
    private $db;
    private $uploadDir = 'uploads/products/';

    public function __construct(Database $db)
    {
        $this->db = $db;
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    // Save product with image uploads
    public function addProduct($sellerId, $categoryId, $productName, $amount, $quantity, $description, $images)
    {
        $results = [];

        if (!$images || !isset($images['tmp_name'])) {
            throw new Exception("No images uploaded.");
        }

        foreach ($images['tmp_name'] as $index => $tmpName) {
            $originalName = basename($images['name'][$index]);
            $newFileName = time() . '_' . $originalName;
            $targetFilePath = $this->uploadDir . $newFileName;

            if (move_uploaded_file($tmpName, $targetFilePath)) {
                $this->db->query(
                    "INSERT INTO product (seller_id, category_id, product_name, product_amount, product_stock, description, product_image) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$sellerId, $categoryId, $productName, $amount, $quantity, $description, $targetFilePath]
                );
                // $results[] = "Uploaded and saved: " . htmlspecialchars($newFileName);
            } else {
                $results[] = "Failed to upload: " . htmlspecialchars($originalName);
            }
        }

        return $results;
    }
}
