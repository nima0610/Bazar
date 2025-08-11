<?php
class Category
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // Fetch all categories
    public function getAllCategories()
    {
        return $this->db->query("SELECT category_id, category_name FROM categories")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get category ID by name
    public function getCategoryIdByName(string $name)
    {
        $stmt = $this->db->query("SELECT category_id FROM categories WHERE category_name = ?", [$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['category_id'] : null;
    }
}
