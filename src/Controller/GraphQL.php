<?php

namespace Jamesdencorrea\ScandiwebBackend\Controller;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use PDO;
use RuntimeException;
use Throwable;

class GraphQL
{
    static public function handle()
    {
        try {
            $host = 'turntable.proxy.rlwy.net';
            $port = '20562';
            $dbname = 'railway';
            $user = 'root';
            $pass = 'vAOGplewvNdAAAazCEZnIufRidogCBsR';

            $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
            $db = new PDO($dsn, $user, $pass);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $attributeType = new ObjectType([
                'name' => 'Attribute',
                'fields' => [
                    'name' => ['type' => Type::string()],
                    'value' => ['type' => Type::string()],
                    'displayValue' => ['type' => Type::string()],
                    'type' => ['type' => Type::string()],
                ],
            ]);

            $productType = null;
            $productType = new ObjectType([
                'name' => 'Product',
                'fields' => function () use (&$productType, $attributeType) {
                    return [
                        'id' => ['type' => Type::string()],
                        'sku' => ['type' => Type::string()],
                        'name' => ['type' => Type::string()],
                        'price' => ['type' => Type::float()],
                        'type' => ['type' => Type::string()],
                        'category' => ['type' => Type::string()],
                        'brand' => ['type' => Type::string()],
                        'image_url' => ['type' => Type::string()],
                        'in_stock' => ['type' => Type::int()],
                        'description' => ['type' => Type::string()],
                        'gallery' => ['type' => Type::listOf(Type::string())],
                        'attributes' => ['type' => Type::listOf($attributeType)],
                        'image' => ['type' => Type::string()],
                    ];
                },
            ]);

            $queryType = new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'products' => [
                        'type' => Type::listOf($productType),
                        'resolve' => function () use ($db) {
                            return self::fetchAllProducts($db);
                        },
                    ],
                    'product' => [
                        'type' => $productType,
                        'args' => [
                            'id' => Type::nonNull(Type::string()),
                        ],
                        'resolve' => function ($root, $args) use ($db) {
                            return self::fetchProductById($db, $args['id']);
                        },
                    ],
                    'categories' => [
                        'type' => Type::listOf(Type::string()),
                        'resolve' => function () use ($db) {
                            $stmt = $db->query("SELECT name FROM categories");
                            return $stmt->fetchAll(PDO::FETCH_COLUMN);
                        },
                    ],
                ],
            ]);

            $orderItemInputType = new InputObjectType([
                'name' => 'OrderItemInput',
                'fields' => [
                    'productId' => ['type' => Type::nonNull(Type::string())],
                    'quantity' => ['type' => Type::nonNull(Type::int())],
                ],
            ]);

            $orderInputType = new InputObjectType([
                'name' => 'OrderInput',
                'fields' => [
                    'items' => ['type' => Type::nonNull(Type::listOf($orderItemInputType))],
                    'total' => ['type' => Type::nonNull(Type::float())],
                ],
            ]);

            $orderType = new ObjectType([
                'name' => 'Order',
                'fields' => [
                    'id' => ['type' => Type::string()],
                    'total' => ['type' => Type::float()],
                    'items' => [
                        'type' => Type::listOf(new ObjectType([
                            'name' => 'OrderItem',
                            'fields' => [
                                'productId' => ['type' => Type::string()],
                                'quantity' => ['type' => Type::int()],
                            ],
                        ])),
                    ],
                ],
            ]);

           $mutationType = new ObjectType([
    'name' => 'Mutation',
    'fields' => [
        'addProduct' => [
            'type' => $productType,
            'args' => [
                'input' => [
                    'type' => new InputObjectType([
                        'name' => 'ProductInput',
                        'fields' => [
                            'id' => ['type' => Type::string()],
                            'sku' => ['type' => Type::nonNull(Type::string())],
                            'name' => ['type' => Type::nonNull(Type::string())],
                            'price' => ['type' => Type::nonNull(Type::float())],
                            'productType' => ['type' => Type::nonNull(Type::string())],
                            'category' => ['type' => Type::nonNull(Type::string())],
                            'description' => ['type' => Type::string()],
                            'size' => ['type' => Type::string()],
                            'weight' => ['type' => Type::string()],
                            'height' => ['type' => Type::string()],
                            'width' => ['type' => Type::string()],
                            'length' => ['type' => Type::string()],
                            'attributes' => ['type' => Type::listOf(new InputObjectType([
                                'name' => 'AttributeInput',
                                'fields' => [
                                    'name' => ['type' => Type::string()],
                                    'value' => ['type' => Type::string()],
                                    'type' => ['type' => Type::string()],
                                ],
                            ]))],
                        ],
                    ]),
                ],
            ],
            'resolve' => function ($root, $args) use ($db) {
                $input = $args['input'];
                
                // Insert basic product info
                $stmt = $db->prepare("
                    INSERT INTO products 
                    (id, sku, name, product_type, category_id, description, in_stock) 
                    VALUES (?, ?, ?, ?, (SELECT id FROM categories WHERE name = ?), ?, 1)
                ");
                $stmt->execute([
                    $input['id'],
                    $input['sku'],
                    $input['name'],
                    $input['productType'],
                    $input['category'],
                    $input['description'] ?? ''
                ]);
                
                // Insert price
                $priceStmt = $db->prepare("INSERT INTO prices (product_id, amount) VALUES (?, ?)");
                $priceStmt->execute([$input['id'], $input['price']]);
                
                // Insert type-specific attributes
                if ($input['productType'] === 'DVD' && isset($input['size'])) {
                    $sizeStmt = $db->prepare("INSERT INTO product_attributes (product_id, attribute_id, value) 
                                           VALUES (?, (SELECT id FROM attributes WHERE name = 'size'), ?)");
                    $sizeStmt->execute([$input['id'], $input['size']]);
                } 
                elseif ($input['productType'] === 'Book' && isset($input['weight'])) {
                    $weightStmt = $db->prepare("INSERT INTO product_attributes (product_id, attribute_id, value) 
                                             VALUES (?, (SELECT id FROM attributes WHERE name = 'weight'), ?)");
                    $weightStmt->execute([$input['id'], $input['weight']]);
                } 
                elseif ($input['productType'] === 'Furniture') {
                    if (isset($input['height'])) {
                        $heightStmt = $db->prepare("INSERT INTO product_attributes (product_id, attribute_id, value) 
                                                   VALUES (?, (SELECT id FROM attributes WHERE name = 'height'), ?)");
                        $heightStmt->execute([$input['id'], $input['height']]);
                    }
                    if (isset($input['width'])) {
                        $widthStmt = $db->prepare("INSERT INTO product_attributes (product_id, attribute_id, value) 
                                                VALUES (?, (SELECT id FROM attributes WHERE name = 'width'), ?)");
                        $widthStmt->execute([$input['id'], $input['width']]);
                    }
                    if (isset($input['length'])) {
                        $lengthStmt = $db->prepare("INSERT INTO product_attributes (product_id, attribute_id, value) 
                                                 VALUES (?, (SELECT id FROM attributes WHERE name = 'length'), ?)");
                        $lengthStmt->execute([$input['id'], $input['length']]);
                    }
                }
                
                // Insert custom attributes
                if (!empty($input['attributes'])) {
                    foreach ($input['attributes'] as $attribute) {
                        // First check if attribute exists
                        $attrCheck = $db->prepare("SELECT id FROM attributes WHERE name = ?");
                        $attrCheck->execute([$attribute['name']]);
                        $attrId = $attrCheck->fetchColumn();
                        
                        if (!$attrId) {
                            // Create new attribute if it doesn't exist
                            $attrInsert = $db->prepare("INSERT INTO attributes (name, type) VALUES (?, ?)");
                            $attrInsert->execute([$attribute['name'], $attribute['type'] ?? 'text']);
                            $attrId = $db->lastInsertId();
                        }
                        
                        // Insert product attribute
                        $attrStmt = $db->prepare("INSERT INTO product_attributes (product_id, attribute_id, value) 
                                               VALUES (?, ?, ?)");
                        $attrStmt->execute([$input['id'], $attrId, $attribute['value']]);
                    }
                }
                
                return self::fetchProductById($db, $input['id']);
            },
        ],
        'deleteProducts' => [
            'type' => Type::listOf(Type::string()),
            'args' => [
                'ids' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
            ],
            'resolve' => function ($root, $args) use ($db) {
                $placeholders = implode(',', array_fill(0, count($args['ids']), '?'));
                $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                $stmt->execute($args['ids']);
                return $args['ids'];
            },
        ],
        'createOrder' => [
            'type' => $orderType,
            'args' => [
                'input' => ['type' => Type::nonNull($orderInputType)],
            ],
            'resolve' => function ($root, $args) {
                $input = $args['input'];
                return [
                    'id' => uniqid('order_'),
                    'total' => $input['total'],
                    'items' => $input['items'],
                ];
            },
        ],
    ],
]);

            $schema = new Schema([
                'query' => $queryType,
                'mutation' => $mutationType,
            ]);

            $rawInput = file_get_contents('php://input');
            if ($rawInput === false) throw new RuntimeException('Failed to get php://input');

            $input = json_decode($rawInput, true);
            if ($input === null) {
                echo json_encode(['error' => ['message' => 'Invalid JSON input']]);
                return;
            }

            if (!isset($input['query']) || !is_string($input['query'])) {
                echo json_encode(['error' => ['message' => 'Missing or invalid GraphQL query.']]);
                return;
            }

            $query = $input['query'];
            $variableValues = $input['variables'] ?? null;

            $result = GraphQLBase::executeQuery($schema, $query, null, null, $variableValues);
            $output = $result->toArray();

            header('Content-Type: application/json');
            echo json_encode($output);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => ['message' => $e->getMessage()]]);
        }
    }

    static private function fetchAllProducts(PDO $db): array
    {
        $stmt = $db->query("
            SELECT 
                p.id, 
                p.sku, 
                p.name, 
                pr.amount AS price,
                p.product_type AS type,
                c.name AS category,
                b.name AS brand,
                p.in_stock,
                p.description,
                (
                    SELECT image_url 
                    FROM product_gallery 
                    WHERE product_id = p.id 
                    LIMIT 1
                ) AS image_url
            FROM products p
            JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN (
                SELECT product_id, MAX(amount) AS amount 
                FROM prices 
                GROUP BY product_id
            ) pr ON p.id = pr.product_id
            GROUP BY p.id
        ");

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $finalProducts = [];

        foreach ($products as $product) {
            $attrStmt = $db->prepare("
                SELECT 
                    a.name AS name,
                    ai.value AS value,
                    ai.id AS displayValue,
                    a.type AS type
                FROM product_attributes pa
                JOIN attributes a ON pa.attribute_id = a.id
                LEFT JOIN attribute_items ai ON pa.attribute_id = ai.attribute_id
                WHERE pa.product_id = ?
            ");
            $attrStmt->execute([$product['id']]);
            $attributes = $attrStmt->fetchAll(PDO::FETCH_ASSOC);

            $galleryStmt = $db->prepare("SELECT image_url FROM product_gallery WHERE product_id = ?");
            $galleryStmt->execute([$product['id']]);
            $galleryImages = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);

            $finalProducts[] = [
                'id' => $product['id'],
                'sku' => $product['sku'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'type' => $product['type'],
                'category' => $product['category'],
                'brand' => $product['brand'] ?? '',
                'image_url' => $product['image_url'] ?? '',
                'image' => $galleryImages[0] ?? ($product['image_url'] ?? ''),
                'in_stock' => (int)$product['in_stock'],
                'description' => $product['description'] ?? '',
                'gallery' => $galleryImages ?: [],
                'attributes' => $attributes,
            ];
        }

        return $finalProducts;
    }

    static private function fetchProductById(PDO $db, string $id): ?array
    {
        $stmt = $db->prepare("
            SELECT 
                p.id, 
                p.sku, 
                p.name, 
                pr.amount AS price,
                p.product_type AS type,
                c.name AS category,
                b.name AS brand,
                p.in_stock,
                p.description,
                (
                    SELECT image_url 
                    FROM product_gallery 
                    WHERE product_id = p.id 
                    LIMIT 1
                ) AS image_url
            FROM products p
            JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN (
                SELECT product_id, MAX(amount) AS amount 
                FROM prices 
                GROUP BY product_id
            ) pr ON p.id = pr.product_id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) return null;

        $attrStmt = $db->prepare("
            SELECT 
                a.name AS name,
                ai.value AS value,
                ai.id AS displayValue,
                a.type AS type
            FROM product_attributes pa
            JOIN attributes a ON pa.attribute_id = a.id
            LEFT JOIN attribute_items ai ON pa.attribute_id = ai.attribute_id
            WHERE pa.product_id = ?
        ");
        $attrStmt->execute([$product['id']]);
        $attributes = $attrStmt->fetchAll(PDO::FETCH_ASSOC);

        $galleryStmt = $db->prepare("SELECT image_url FROM product_gallery WHERE product_id = ?");
        $galleryStmt->execute([$product['id']]);
        $galleryImages = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);

        return [
            'id' => $product['id'],
            'sku' => $product['sku'],
            'name' => $product['name'],
            'price' => (float)$product['price'],
            'type' => $product['type'],
            'category' => $product['category'],
            'brand' => $product['brand'] ?? '',
            'image_url' => $product['image_url'] ?? '',
            'image' => $galleryImages[0] ?? ($product['image_url'] ?? ''),
            'in_stock' => (int)$product['in_stock'],
            'description' => $product['description'] ?? '',
            'gallery' => $galleryImages ?: [],
            'attributes' => $attributes,
        ];
    }
}
