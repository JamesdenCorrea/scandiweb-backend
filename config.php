<?php
// config.php

// Get Railway environment variables with fallbacks
$host = getenv('MYSQLHOST') ?: 'turntable.proxy.rlwy.net';
$port = getenv('MYSQLPORT') ?: '20562';
$db   = getenv('MYSQLDATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: 'vAOGplewvNdAAAazCEZnIufRidogCBsR';
$charset = 'utf8mb4';

// Construct DSN with proper formatting
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // More detailed error logging for debugging
    error_log("Database connection failed: " . $e->getMessage());
    error_log("DSN: $dsn");
    error_log("User: $user");
    
    http_response_code(500);
    echo json_encode([
        'error' => [
            'message' => 'Database connection error',
            'details' => $e->getMessage()
        ]
    ]);
    exit;
}