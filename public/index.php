<?php

// 👇 Load Composer packages
require_once __DIR__ . '/../vendor/autoload.php';

// 👇 Load your controller
require_once __DIR__ . '/../src/Controller/GraphQL.php';

// 👇 Run the handler
Jamesdencorrea\ScandiwebBackend\Controller\GraphQL::handle();
