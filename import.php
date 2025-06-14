<?php
// import.php - Import JSON data into DB

// DB config - update these to your environment
$host = 'turntable.proxy.rlwy.net';
$port = 20562;
$db = 'railway';
$user = 'root';
$pass = 'vAOGplewvNdAAAazCEZnIufRidogCBsR';
$charset = 'utf8mb4';


// PDO DSN and options
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// Load JSON data
$jsonFile = __DIR__ . '/data.json';
if (!file_exists($jsonFile)) {
    die("JSON file not found.");
}

$data = json_decode(file_get_contents($jsonFile), true);
if (!$data) {
    die("Failed to decode JSON.");
}

// Your data is inside $data['data'] per your JSON structure
$categories = $data['data']['categories'] ?? [];
$products = $data['data']['products'] ?? [];

// Insert categories with AUTO_INCREMENT id
$categoryStmt = $pdo->prepare("
    INSERT INTO categories (name) VALUES (:name)
    ON DUPLICATE KEY UPDATE name = VALUES(name)
");
foreach ($categories as $category) {
    $categoryStmt->execute(['name' => $category['name']]);
}

// Insert brands (extract unique brand names from products)
$brands = [];
foreach ($products as $product) {
    if (!empty($product['brand'])) {
        $brands[$product['brand']] = true;
    }
}

$brandStmt = $pdo->prepare("
    INSERT INTO brands (name) VALUES (:name)
    ON DUPLICATE KEY UPDATE name = VALUES(name)
");
foreach (array_keys($brands) as $brandName) {
    $brandStmt->execute(['name' => $brandName]);
}

// Helper function to get ID by name from categories or brands
function getIdByName(PDO $pdo, string $table, string $name): int {
    $stmt = $pdo->prepare("SELECT id FROM `$table` WHERE name = :name LIMIT 1");
    $stmt->execute(['name' => $name]);
    $result = $stmt->fetch();
    return $result ? (int)$result['id'] : 0;
}

// Insert products
$productStmt = $pdo->prepare("
    INSERT INTO products (id, sku, name, description, category_id, brand_id, in_stock)
    VALUES (:id, :sku, :name, :description, :category_id, :brand_id, :in_stock)
    ON DUPLICATE KEY UPDATE
        sku = VALUES(sku),
        name = VALUES(name),
        description = VALUES(description),
        category_id = VALUES(category_id),
        brand_id = VALUES(brand_id),
        in_stock = VALUES(in_stock)
");


foreach ($products as $product) {
    $categoryId = getIdByName($pdo, 'categories', $product['category']);
    if ($categoryId === 0) {
        die("Category '{$product['category']}' not found for product ID {$product['id']}");
    }

    $brandId = getIdByName($pdo, 'brands', $product['brand']);
    if ($brandId === 0) {
        die("Brand '{$product['brand']}' not found for product ID {$product['id']}");
    }

    $inStock = isset($product['inStock']) && $product['inStock'] ? 1 : 0;


$productStmt->execute([
    'id' => $product['id'],
    'sku' => $product['sku'] ?? 'SKU-' . $product['id'], // ✅ fallback value
    'name' => $product['name'],
    'description' => $product['description'] ?? null,
    'category_id' => $categoryId,
    'brand_id' => $brandId,
    'in_stock' => $inStock,
]);


}

// Insert product galleries
$galleryStmt = $pdo->prepare("
    INSERT INTO product_gallery (product_id, image_url)
    VALUES (:product_id, :image_url)
");

foreach ($products as $product) {
    // ✅ Clear old gallery images for this product
    $deleteGalleryStmt = $pdo->prepare("DELETE FROM product_gallery WHERE product_id = :product_id");
    $deleteGalleryStmt->execute(['product_id' => $product['id']]);

    // ✅ Insert new gallery images
    if (!empty($product['gallery']) && is_array($product['gallery'])) {
        foreach ($product['gallery'] as $imageUrl) {
            $galleryStmt->execute([
                'product_id' => $product['id'],
                'image_url' => $imageUrl,
            ]);
        }
    }
}


// Insert attributes and attribute items
$attributeStmt = $pdo->prepare("
    INSERT INTO attributes (id, name, type)
    VALUES (:id, :name, :type)
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        type = VALUES(type)
");

$attributeItemStmt = $pdo->prepare("
    INSERT INTO attribute_items (id, attribute_id, display_value, value)
    VALUES (:id, :attribute_id, :display_value, :value)
    ON DUPLICATE KEY UPDATE
        display_value = VALUES(display_value),
        value = VALUES(value)
");

$seenAttributes = [];
$seenAttributeItems = [];

foreach ($products as $product) {
    if (!empty($product['attributes']) && is_array($product['attributes'])) {
        foreach ($product['attributes'] as $attribute) {
            $attrId = $attribute['id'];
            if (!isset($seenAttributes[$attrId])) {
                $attributeStmt->execute([
                    'id' => $attrId,
                    'name' => $attribute['name'],
                    'type' => $attribute['type'],
                ]);
                $seenAttributes[$attrId] = true;
            }
            if (!empty($attribute['items']) && is_array($attribute['items'])) {
                foreach ($attribute['items'] as $item) {
                    $itemId = $item['id'];
                    if (!isset($seenAttributeItems[$itemId])) {
                        $attributeItemStmt->execute([
                            'id' => $itemId,
                            'attribute_id' => $attrId,
                            'display_value' => $item['displayValue'],
                            'value' => $item['value'],
                        ]);
                        $seenAttributeItems[$itemId] = true;
                    }
                }
            }
        }
    }
}

// Link products with attributes and attribute items
$productAttrStmt = $pdo->prepare("
    INSERT IGNORE INTO product_attributes (product_id, attribute_id)
    VALUES (:product_id, :attribute_id)
");

$productAttrItemStmt = $pdo->prepare("
    INSERT IGNORE INTO product_attribute_items (product_id, attribute_id, attribute_item_id)
    VALUES (:product_id, :attribute_id, :attribute_item_id)
");

foreach ($products as $product) {
    if (!empty($product['attributes']) && is_array($product['attributes'])) {
        foreach ($product['attributes'] as $attribute) {
            $attrId = $attribute['id'];
            $productAttrStmt->execute([
                'product_id' => $product['id'],
                'attribute_id' => $attrId,
            ]);
            if (!empty($attribute['items']) && is_array($attribute['items'])) {
                foreach ($attribute['items'] as $item) {
                    $productAttrItemStmt->execute([
                        'product_id' => $product['id'],
                        'attribute_id' => $attrId,
                        'attribute_item_id' => $item['id'],
                    ]);
                }
            }
        }
    }
}

// Insert prices
$priceStmt = $pdo->prepare("
    INSERT INTO prices (product_id, amount, currency_label, currency_symbol)
    VALUES (:product_id, :amount, :currency_label, :currency_symbol)
    ON DUPLICATE KEY UPDATE
        amount = VALUES(amount),
        currency_label = VALUES(currency_label),
        currency_symbol = VALUES(currency_symbol)
");

foreach ($products as $product) {
    if (!empty($product['prices']) && is_array($product['prices'])) {
        foreach ($product['prices'] as $price) {
            $priceStmt->execute([
                'product_id' => $product['id'],
                'amount' => $price['amount'],
                'currency_label' => $price['currency']['label'],
                'currency_symbol' => $price['currency']['symbol'],
            ]);
        }
    }
}

echo "Import completed successfully.\n";
