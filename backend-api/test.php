<?php
// CORS (za React)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Ako je OPTIONS request (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// JSON odgovor
header("Content-Type: application/json");

echo json_encode([
    "status" => "ok",
    "message" => "RADI BACKEND 🎯",
    "time" => date("Y-m-d H:i:s")
]);
