<?php
class Product
{
    private $db;  // Database instance

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // Get all products ordered by sold descending
    public function getTopProductsBySold($limit = 6)
    {
        $limit = 6; // ensure it’s an integer
        $sql = "SELECT * FROM product ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopProductByDiscount($limit = 18)
    {
        $limit = 18;
        $sql = "SELECT * FROM product ORDER BY discount_percent DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopHomeDecor($limit = 4)
    {
        $sql = "SELECT * FROM product WHERE category_id = 2  ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopBeauty($limit = 4)
    {
        $sql = "SELECT * FROM product WHERE category_id = 1  ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopHome($limit = 4)
    {
        $sql = "SELECT * FROM product WHERE category_id = 3  ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopUtensils($limit = 4)
    {
        $sql = "SELECT * FROM product WHERE category_id = 4  ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopBeautyNB($limit = 4)
    {
        $sql = "SELECT * FROM product WHERE category_id = 5  ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopWatch($limit = 4)
    {
        $sql = "SELECT * FROM product WHERE category_id = 6  ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopElectronics($limit = 4)
    {
        $sql = "SELECT * FROM product WHERE category_id = 7  ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopToys($limit = 4)
    {
        $sql = "SELECT * FROM product WHERE category_id = 8  ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductByCategory($category_id, $limit = 6)
    {
        $limit = (int) $limit; // sanitize limit
        $sql = "SELECT * FROM product WHERE category_id = ? ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>