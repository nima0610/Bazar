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
        $sql = "SELECT * FROM customers WHERE user_id=?";
        $stmt = $this->db->query($sql, [$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}


?>