<?php

namespace Jamesdencorrea\ScandiwebBackend\Controller;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Definition\ObjectType;
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
                    'type' => ['type' => Type::string()],
                ],
            ]);

            $productType = new ObjectType([
                'name' => 'Product',
                'fields' => [
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
                    'gallery' => ['type' => Type::listOf(Type::string())], // ✅ Added
                    'attributes' => ['type' => Type::listOf($attributeType)],
                ],
            ]);

            $queryType = new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'products' => [
                        'type' => Type::listOf($productType),
                        'resolve' => function () use ($db) {
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
                                        a.type AS type
                                    FROM product_attributes pa
                                    JOIN attributes a ON pa.attribute_id = a.id
                                    LEFT JOIN attribute_items ai ON pa.attribute_id = ai.attribute_id
                                    WHERE pa.product_id = ?
                                ");
                                $attrStmt->execute([$product['id']]);
                                $attributes = $attrStmt->fetchAll(PDO::FETCH_ASSOC);

                                $galleryStmt = $db->prepare("
                                    SELECT image_url FROM product_gallery WHERE product_id = ?
                                ");
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
                                    'in_stock' => (int)$product['in_stock'],
                                    'description' => $product['description'] ?? '',
                                    'gallery' => $galleryImages ?: [],
                                    'attributes' => $attributes,
                                ];
                            }

                            return $finalProducts;
                        },
                    ],
                    'categories' => [
                        'type' => Type::listOf(Type::string()),
                        'resolve' => function () use ($db) {
                            $stmt = $db->query("SELECT name FROM categories");
                            return $stmt->fetchAll(PDO::FETCH_COLUMN);
                        }
                    ]
                ],
            ]);

            $schema = new Schema(['query' => $queryType]);

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
}
