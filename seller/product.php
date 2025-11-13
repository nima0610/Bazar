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


    public function getSellerDetails($product_id)
    {
        $sql = "SELECT * FROM seller WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$product_id]); // ✅ execute with params here
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Save product with image uploads
    public function addProduct($sellerId, $categoryId, $productName, $amount, $quantity, $description, $imagesCommaSeparated)
    {
        $results = [];

        // Insert a single row with all images
        $this->db->query(
            "INSERT INTO product (seller_id, category_id, product_name, product_amount, product_stock, description, product_image) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$sellerId, $categoryId, $productName, $amount, $quantity, $description, $imagesCommaSeparated]
        );

        $results[] = "Product added successfully with images.";

        return $results;
    }

}
