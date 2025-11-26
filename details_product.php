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
    public function getAverageRating($product_id)
    {
        $stmt = $this->db->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['avg_rating'] ?? 0; // return 0 if no reviews
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

    public function getProductReview($product_id)
    {
        $sql = "SELECT * FROM reviews WHERE product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserData($user_id)
    {
        $sql = "SELECT * FROM customers WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // <-- return single row
    }

    public function getStockFromVariants($product_id, $size, $color)
    {
        $sql = "SELECT * FROM product_variants 
            WHERE product_id = ? AND size = ? AND color = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$product_id, $size, $color]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
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