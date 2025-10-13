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
        return $stmt->fetch(PDO::FETCH_ASSOC); // 
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
}
?>