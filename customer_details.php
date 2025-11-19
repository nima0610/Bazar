<?php
class CustomerDetails
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getCustomerDetails($user_id)
    {
        $sql = "SELECT * FROM customers WHERE user_id = ?"; // singular table name
        $stmt = $this->db->query($sql, [$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // will be false 
    }
    public function getDistrictName($district_id)
    {
        $sql = "SELECT name FROM districts WHERE id = ?";
        $stmt = $this->db->query($sql, [$district_id]);
        $row = $stmt->fetch();
        return $row['name'] ?? null; // return null if not found
    }

    // Get location name by location_id
    public function getLocationName($location_id)
    {
        $sql = "SELECT name FROM locations WHERE district_id = ?";
        $stmt = $this->db->query($sql, [$location_id]);
        $row = $stmt->fetch();
        return $row['name'] ?? null; // return null if not found
    }

    public function getPurchasedDetails($user_id)
    {
        $sql = "SELECT * FROM purchase_history WHERE user_id = ?"; // singular table name
        $stmt = $this->db->query($sql, [$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // will be false 
    }

    public function getPurchasedProduct($product_id)
    {
        $sql = "SELECT * FROM product WHERE product_id = ?";
        $stmt = $this->db->query($sql, [$product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // fetch once and return
    }

    public function getCustomerPurchases($userkoid)
    {
        $sql = "SELECT * FROM purchase_history WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$userkoid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPurchasedPic($picture)
    {
        $sql = "SELECT * FROM product WHERE product_id = ?";
        $stmt = $this->db->query($sql, [$picture]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>