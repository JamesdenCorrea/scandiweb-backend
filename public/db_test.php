<?php
// db_test.php

$host = 'turntable.proxy.rlwy.net';
$port = 20562;
$db = 'railway';
$user = 'root';
$pass = 'vAOGplewvNdAAAazCEZnIufRidogCBsR'; // Replace with Railway password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Connected to Railway MySQL database.<br><br>";

    $stmt = $pdo->query("SELECT id, name FROM products LIMIT 5");
    echo "<strong>Sample Products:</strong><br>";
    foreach ($stmt as $row) {
        echo "• {$row['id']}: {$row['name']}<br>";
    }
} catch (PDOException $e) {
    echo "❌ DB connection failed: " . $e->getMessage();
}
