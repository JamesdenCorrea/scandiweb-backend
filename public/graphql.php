<?php

use GraphQL\Error\DebugFlag;
use Jamesdencorrea\ScandiwebBackend\Controller\GraphQL;

// ✅ Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit();
}

// ✅ Set headers for POST GraphQL requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once __DIR__ . '/../vendor/autoload.php';

try {
    GraphQL::handle(); // ✅ This internally already uses DebugFlag in your updated GraphQL.php
} catch (Throwable $e) {
    // ✅ This is a fallback in case GraphQL::handle() itself throws
    http_response_code(500);
    echo json_encode([
        'error' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
