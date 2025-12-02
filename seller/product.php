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

    public function getProductsBySeller($sellerId)
    {
        $sql = "SELECT p.*, 
               (SELECT category_name FROM categories WHERE category_id = p.category_id) AS categories
            FROM product p
            WHERE p.seller_id = ? AND p.is_active = '1'
            ORDER BY p.product_id DESC";

        $stmt = $this->db->getConnection()->prepare($sql);  // Use prepare
        $stmt->execute([$sellerId]);                        // Bind param
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    // 1️⃣ Get single product by ID
    public function getProductById($product_id)
    {
        $sql = "SELECT * FROM product WHERE product_id = ?";
        $stmt = $this->db->query($sql, [$product_id]); // ✅ FIXED
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }



    // 2️⃣ Update product
    public function updateProduct($product_id, $seller_id, $category_id, $product_name, $product_amount, $product_stock, $description, $product_image)
    {

        // Step 1: Get old image
        $stmt = $this->db->getPDO()->prepare("SELECT product_image FROM product WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $old = $stmt->fetch();

        $oldImage = $old['product_image'];

        // Step 2: Upload new image if selected
        if (!empty($photo['name'])) {

            // Delete old image  
            if ($oldImage && file_exists("uploads/products/" . $oldImage)) {
                unlink("uploads/" . $oldImage);
            }

            // Make new image name
            $newFileName = time() . "_" . basename($product_image["name"]);
            $targetPath = "uploads/" . $newFileName;

            move_uploaded_file($product_image["tmp_name"], $targetPath);

        } else {
            // No new image → keep old image
            $newFileName = $oldImage;
        }
        $stmt = $this->db->getConnection()->prepare("
            UPDATE product 
            SET seller_id = :seller_id,
                category_id = :category_id,
                product_name = :product_name,
                product_amount = :product_amount,
                product_stock = :product_stock,
                description = :description,
                product_image = :product_image
            WHERE product_id = :product_id
        ");
        return $stmt->execute([
            'seller_id' => $seller_id,
            'category_id' => $category_id,
            'product_name' => $product_name,
            'product_amount' => $product_amount,
            'product_stock' => $product_stock,
            'description' => $description,
            'product_image' => $product_image,
            'product_id' => $product_id
        ]);
    }


    //Product Delete Logic
    public function softDeleteProduct($product_id)
    {
        $sql = "UPDATE product SET is_active = 0 WHERE product_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([$product_id]);
    }

    public function hasOrders($product_id)
    {
        $sql = "SELECT COUNT(*) FROM orders WHERE product_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$product_id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getSellerMu($user_id)
    {
        $sql = "SELECT * FROM seller WHERE user_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // return row as array
    }

}

?>