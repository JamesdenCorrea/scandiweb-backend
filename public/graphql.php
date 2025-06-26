<?php
// ✅ Handle CORS preflight request FIRST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit();
}

// ✅ Set headers for actual GET/POST GraphQL requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// ✅ Autoload and handle GraphQL
require_once __DIR__ . '/../vendor/autoload.php';

use Jamesdencorrea\ScandiwebBackend\Controller\GraphQL;

// ✅ OUTPUT the GraphQL result
echo GraphQL::handle();
