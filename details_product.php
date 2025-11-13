<?php
class Details
{
    private $db;

    // Constructor - takes Database object
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // Fetch product details by ID
    public function getProductDetails($product_id)
    {
        $sql = "SELECT * FROM product WHERE product_id = ?";
        $stmt = $this->db->query($sql, [$product_id]); // ✅ execute with params here
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSellerName($product_seller)
    {
        $sql = "SELECT * FROM seller WHERE seller_id = ?";
        $stmt = $this->db->query($sql, [$product_seller]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getProductByCategory($category_id)
    {
        $sql = "SELECT * FROM product WHERE category_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch similar products by category
    /*public function getSimilarProducts($category_id, $exclude_product_id)
    {
        $sql = "SELECT * FROM product WHERE category_id = ? AND product_id != ? LIMIT 4";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$category_id, $exclude_product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
    */
}
?>