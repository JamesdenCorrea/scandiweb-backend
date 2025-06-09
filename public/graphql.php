<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jamesdencorrea\ScandiwebBackend\Controller\GraphQL;

// CORS headers for frontend requests from localhost:5173 or anywhere
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

echo GraphQL::handle();
