<?php

// ===================
// CORS
// ===================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===================
// NORMALIZACIJA URI-JA
// ===================

// primeri koji MORAJU da rade:
// /nutriscan-backend/Public/index.php
// /nutriscan-backend/Public/products/barcode
// /nutriscan-backend/Public/index.php/products/barcode

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ukloni base folder
$base = '/nutriscan-backend/Public';
if (strpos($path, $base) === 0) {
    $path = substr($path, strlen($base));
}

// ukloni index.php ako postoji
$path = str_replace('/index.php', '', $path);

// očisti /
$uri = trim($path, '/');

// ===================
// ROOT
// ===================
if ($uri === '') {
    echo json_encode([
        "status" => "OK",
        "message" => "NutriScan backend is running",
        "routes" => [
            "/login",
            "/register",
            "/dashboard",
            "/profile",
            "/products/barcode"
        ]
    ]);
    exit;
}

// ===================
// ROUTES
// ===================
$routes = [
    'login' => __DIR__ . '/../src/api/login.php',
    'register' => __DIR__ . '/../src/api/register.php',
    'dashboard' => __DIR__ . '/../src/api/dashboard.php',
    'profile' => __DIR__ . '/../src/api/profile.php',
    'logout' => __DIR__ . '/../src/api/logout.php',
    'meals/add' => __DIR__ . '/../src/api/meals/add.php',
    'products/barcode' => __DIR__ . '/../src/api/products/barcode.php',
    'meals/today' => __DIR__ . '/../src/api/meals/today.php',
    'meals/add_manual' => __DIR__ . '/../src/api/meals/add_manual.php',
    'products/comment' => __DIR__ . '/../src/api/products/comment_add.php',
    'products/comments' => __DIR__ . '/../src/api/products/comments.php',
    'products/rate' => __DIR__ . '/../src/api/products/rate.php',
    'products/rating' => __DIR__ . '/../src/api/products/rating_avg.php',
    'products/rating_avg' => '../src/api/products/rating_avg.php',

];

// ===================
// DISPATCH
// ===================
if (isset($routes[$uri])) {
    require $routes[$uri];
    exit;
}

// ===================
// 404
// ===================
http_response_code(404);
echo json_encode([
    "success" => false,
    "error" => "Route not found",
    "uri" => $uri,
    "debug_request_uri" => $_SERVER['REQUEST_URI']
]);
