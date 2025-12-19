<?php
session_start();
require 'db.php';
require 'category.php';
require 'product.php';

// DB connection
$db = new Database("localhost", "bazar", "root", "");
$categoryObj = new Category($db);
$productObj = new Product($db);

// Get product_id
$product_id = $_GET['product_id'] ?? null;
if (!$product_id)
    die("No product selected.");

$pdo = $db->getConnection();

// Fetch variants
$variant_query = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$variant_query->execute([$product_id]);
$variants = $variant_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch product
$product = $productObj->getProductById($product_id);
if (!$product)
    die("Product not found.");

// Fetch categories
$categories = $categoryObj->getAllCategories();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $category_id = $_POST['category'];
    $product_name = $_POST['product_name'];
    $product_amount = $_POST['product_amount'];
    $product_stock = $_POST['product_stock'];
    $description = $_POST['description'];
    $variant_id = $_POST['variant_id'] ?? null;  // <-- important

    // If variant selected → update ONLY variant stock
    if (!empty($variant_id)) {
        $updateVariant = $pdo->prepare("
            UPDATE product_variants 
            SET stock = ?
            WHERE variant_id = ?
        ");
        $updateVariant->execute([$product_stock, $variant_id]);
    }

    // Handle images
    $product_image = $product['product_image'];

    if (!empty($_FILES['product_image']['name'][0])) {
        $uploadDir = "uploads/products/";
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        $uploadedImages = [];

        foreach ($_FILES['product_image']['tmp_name'] as $key => $tmpName) {
            $name = time() . "_" . basename($_FILES['product_image']['name'][$key]);
            $path = $uploadDir . $name;

            if (move_uploaded_file($tmpName, $path))
                $uploadedImages[] = $path;
        }

        // Delete old images
        if (!empty($product['product_image'])) {
            foreach (explode(',', $product['product_image']) as $oldImg) {
                if (file_exists(trim($oldImg)))
                    unlink(trim($oldImg));
            }
        }

        // Save new images
        if (!empty($uploadedImages)) {
            $product_image = implode(',', $uploadedImages);
        }
    }

    // Update NON-VARIANT product info
    $seller_id = $product['seller_id'];

    $updated = $productObj->updateProduct(
        $product_id,
        $seller_id,
        $category_id,
        $product_name,
        $product_amount,
        $product_stock,  // safe, ignored if variant exists
        $description,
        $product_image
    );

    $discountType = $_POST['discount_type'] ?? null;
    $percent = $_POST['discount_percent'] ?? 0;

    if ($discountType) {

        // Discount on this product only
        if ($discountType === "product") {
            $stmt = $pdo->prepare("UPDATE product SET discount_percent = ? WHERE product_id = ?");
            $stmt->execute([$percent, $product_id]);
        }

        // Discount on all products of this seller
        elseif ($discountType === "all") {
            $stmt = $pdo->prepare("UPDATE product SET discount_percent = ? WHERE seller_id = ?");
            $stmt->execute([$percent, $product['seller_id']]);
        }

        // Discount on selected category
        elseif ($discountType === "category") {
            $categoryId = $_POST['discount_category_id'];
            $stmt = $pdo->prepare("UPDATE product SET discount_percent = ? WHERE seller_id = ? AND category_id = ?");
            $stmt->execute([$percent, $product['seller_id'], $categoryId]);
        }
    }


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
            font-size: 28px;
            font-weight: 600;
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
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 17px;
            cursor: pointer;
            transition: 0.2s ease-in-out;
        }

        .btn-submit:hover {
            background: #d85f18;
        }

        .image-box img {
            width: 120px;
            border-radius: 6px;
            margin-right: 10px;
            border: 1px solid #ddd;
        }

        .variant-box {
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            width: 150px;
            text-align: center;
            background: #fafafa;
            transition: 0.2s;
        }

        .variant-box:hover {
            background: #f0f0f0;
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
                <input type="number" name="product_amount" value="<?= $product['product_amount'] ?>" required>

                <?php if (!empty($variants)): ?>

                    <label>Choose Variant</label>
                    <div id="variantBoxes" style="display:flex;flex-wrap:wrap;gap:15px;margin-bottom:20px;">

                        <?php foreach ($variants as $v): ?>
                            <div class="variant-box" data-variant-id="<?= $v['variant_id'] ?>" data-size="<?= $v['size'] ?>"
                                data-color="<?= $v['color'] ?>" data-stock="<?= $v['stock'] ?>" style="
                        border:1px solid #ccc;
                        padding:15px;
                        border-radius:8px;
                        cursor:pointer;
                        width:150px;
                        text-align:center;
                        background:#fafafa;
                    ">
                                <div><strong>Size:</strong> <?= $v['size'] ?></div>
                                <div><strong>Color:</strong> <?= $v['color'] ?></div>
                            </div>
                        <?php endforeach; ?>

                    </div>

                    <label>Stock</label>
                    <input type="number" id="variantStock" name="product_stock" value="" placeholder="Select a variant">

                    <input type="hidden" id="variantId" name="variant_id">

                <?php else: ?>

                    <label>Stock</label>
                    <input type="number" name="product_stock" value="<?= $product['product_stock'] ?>">

                <?php endif; ?>

                <label>Description</label>
                <textarea name="description" rows="4" required><?= $product['description'] ?></textarea>

                <label>Current Image</label>
                <div class="image-box">
                    <?php foreach (explode(',', $product['product_image']) as $img): ?>
                        <img src="<?= $img ?>" width="120" style="border-radius:6px;margin-right:10px;">
                    <?php endforeach; ?>
                </div>

                <label>Upload New Image</label>
                <input type="file" name="product_image[]" multiple>


                <h3 style="margin-top: 30px;">Apply Discount</h3>

                <label>
                    <input type="radio" name="discount_type" value="product" checked>
                    Discount on This Product Only
                </label><br>

                <label>
                    <input type="radio" name="discount_type" value="all">
                    Discount on All Products
                </label><br>

                <label>
                    <input type="radio" name="discount_type" value="category">
                    Discount on Category
                </label><br><br>

                <label>Discount Percentage (%)</label>
                <input type="number" name="discount_percent" min="0" max="100" placeholder="Enter %">

                <div id="category-select-field" style="display:none;">
                    <label>Select Category</label>
                    <select name="discount_category_id">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>">
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <script>
                    document.querySelectorAll("input[name='discount_type']").forEach(radio => {
                        radio.addEventListener("change", function () {
                            document.getElementById("category-select-field").style.display =
                                this.value === "category" ? "block" : "none";
                        });
                    });
                </script>

                <button type="submit" class="btn-submit">Update Product</button>

            </form>

        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const boxes = document.querySelectorAll(".variant-box");
            const stockInput = document.getElementById("variantStock");
            const variantIdInput = document.getElementById("variantId");

            boxes.forEach(box => {
                box.addEventListener("click", () => {

                    boxes.forEach(b => b.style.border = "1px solid #ccc");

                    box.style.border = "2px solid #f57224";

                    stockInput.value = box.dataset.stock;
                    variantIdInput.value = box.dataset.variantId;
                });
            });
        });
    </script>

</body>

</html>