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
        $limit = (int) $limit; // ensure it’s an integer
        $sql = "SELECT * FROM product ORDER BY sold DESC LIMIT $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>