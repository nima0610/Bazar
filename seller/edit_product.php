<?php
session_start();
require 'db.php';
require 'category.php';
require 'product.php';

// DB connection
$db = new Database("localhost", "bazar", "root", "");
$categoryObj = new Category($db);
$productObj = new Product($db);

// Get product_id from URL
$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    die("No product selected.");
}

// Fetch product data
$product = $productObj->getProductById($product_id);
if (!$product) {
    die("Product not found.");
}

// Fetch categories
$categories = $categoryObj->getAllCategories();

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category'];
    $product_name = $_POST['product_name'];
    $product_amount = $_POST['product_amount'];
    $product_stock = $_POST['product_stock'];
    $description = $_POST['description'];

    // Handle product image
    $product_image = $product['product_image']; // default (old one)

    if (!empty($_FILES['product_image']['name'][0])) {
        $uploadDir = "uploads/products/";

        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        $uploadedImages = [];

        foreach ($_FILES['product_image']['tmp_name'] as $key => $tmpName) {
            $originalName = basename($_FILES['product_image']['name'][$key]);
            $imgName = time() . "_" . $originalName;
            $uploadPath = $uploadDir . $imgName;

            if (move_uploaded_file($tmpName, $uploadPath)) {
                $uploadedImages[] = $uploadDir . $imgName;
            }
        }

        // Delete old images if any
        if (!empty($product['product_image'])) {
            $oldImages = explode(',', $product['product_image']);
            foreach ($oldImages as $oldImg) {
                if (file_exists(trim($oldImg))) {
                    unlink(trim($oldImg));
                }
            }
        }

        // Store new images as comma-separated
        if (!empty($uploadedImages)) {
            $product_image = implode(',', $uploadedImages);
        }
    }


    $seller_id = $product['seller_id']; // keep same seller

    $updated = $productObj->updateProduct(
        $product_id,
        $seller_id,
        $category_id,
        $product_name,
        $product_amount,
        $product_stock,
        $description,
        $product_image
    );

    if ($updated) {
        header("Location: dashboard.php#products");
        exit();
    } else {
        $error = "Failed to update product.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Product</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 60%;
            margin: 40px auto;
        }

        .card {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h2 {
            text-align: center;
            color: #222;
            margin-bottom: 25px;
        }

        label {
            font-weight: bold;
            margin-bottom: 6px;
            display: block;
            color: #444;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            margin-bottom: 18px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            background: #fafafa;
        }

        textarea {
            resize: vertical;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #f57224;
            /* Daraz Orange */
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 17px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-submit:hover {
            background: #d85f18;
        }

        .back-btn {
            display: inline-block;
            margin-top: 15px;
            color: #555;
            text-decoration: none;
            font-size: 15px;
        }

        .image-box {
            margin-bottom: 20px;
            text-align: center;
        }

        .image-box img {
            width: 150px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .error {
            color: red;
            background: #ffe6e6;
            border-left: 4px solid red;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="card">

            <h2>Edit Product</h2>

            <?php if (isset($error))
                echo "<p class='error'>$error</p>"; ?>

            <form method="POST" enctype="multipart/form-data">

                <label>Product Name</label>
                <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>"
                    required>

                <label>Category</label>
                <select name="category" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $cat['category_id'] == $product['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Amount</label>
                <input type="number" name="product_amount" value="<?= htmlspecialchars($product['product_amount']) ?>"
                    required>

                <label>Stock</label>
                <input type="number" name="product_stock" value="<?= htmlspecialchars($product['product_stock']) ?>"
                    required>

                <label>Description</label>
                <textarea name="description" rows="4"
                    required><?= htmlspecialchars($product['description']) ?></textarea>

                <label>Current Image</label>

                <?php
                $images = explode(',', $product['product_image']); // split by comma
                ?>
                <div class="image-box">
                    <?php foreach ($images as $img):
                        $img = trim($img); // remove extra spaces
                        if (!file_exists($img)) {
                            $img = "uploads/products/no_image.png";
                        }
                        ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="Product Image">
                    <?php endforeach; ?>
                </div>


                <label>Upload New Image (optional)</label>
                <input type="file" name="product_image[]" multiple>

                <button type="submit" class="btn-submit">Update Product</button>

            </form>

            <a href="dashboard.php#products" class="back-btn">← Back to Product Section</a>

        </div>
    </div>

</body>

</html>