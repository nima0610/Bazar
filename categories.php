<?php

class Categories
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getCategories()
    {
        $sql = "SELECT * FROM categories";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>