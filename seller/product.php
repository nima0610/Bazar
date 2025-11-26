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
        $stmt = $this->db->query($sql, [$product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addProduct($sellerId, $categoryId, $productName, $amount, $quantity, $description, $imagesCommaSeparated)
    {
        $results = [];

        // Insert product
        $stmt = $this->db->query(
            "INSERT INTO product (seller_id, category_id, product_name, product_amount, product_stock, description, product_image) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$sellerId, $categoryId, $productName, $amount, $quantity, $description, $imagesCommaSeparated]
        );

        // Get newly inserted product ID safely
        $productId = 0;
        if (method_exists($this->db, 'lastInsertId')) {
            $productId = $this->db->lastInsertId();
        } else {
            // Fallback: fetch max product ID for this seller (less ideal, but works)
            $stmt2 = $this->db->query("SELECT MAX(product_id) AS last_id FROM product WHERE seller_id = ?", [$sellerId]);
            $row = $stmt2->fetch(PDO::FETCH_ASSOC);
            $productId = $row['last_id'] ?? 0;
        }

        $results[] = "Product added successfully with images.";
        $results['product_id'] = $productId;

        return $results;
    }

    public function getAvailableSizes($productId)
    {
        $stmt = $this->db->prepare("SELECT DISTINCT size FROM product_variants WHERE product_id = ? AND size IS NOT NULL");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableColors($productId)
    {
        $stmt = $this->db->prepare("SELECT DISTINCT color FROM product_variants WHERE product_id = ? AND color IS NOT NULL");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

