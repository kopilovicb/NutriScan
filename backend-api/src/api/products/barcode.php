<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Authorization");

// Uključi auth i konekciju
require_once __DIR__ . '/../connection.php';

// Provera GET parametra
if(!isset($_GET['barcode'])) {
    http_response_code(400);
    echo json_encode(["error" => "Nedostaje barcode"]);
    exit;
}

$barcode = $_GET['barcode'];

// OpenFoodFacts API URL
$url = "https://world.openfoodfacts.org/api/v0/product/$barcode.json";

// cURL zahtev
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if(!$response || $http_code != 200){
    http_response_code(500);
    echo json_encode(["error" => "Ne mogu da pristupim OpenFoodFacts API-ju"]);
    exit;
}

// Dekodiraj JSON odgovor
$data = json_decode($response, true);

// Provera da li proizvod postoji
// Provera da li je proizvod pronađen u OpenFoodFacts
if (!isset($data['status']) || $data['status'] != 1) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "error" => "Proizvod nije pronađen u OpenFoodFacts bazi"
    ]);
    exit;
}

// Izvuci relevantne podatke
$product = $data['product'];
$result = [
    "naziv" => $product['product_name'] ?? "",
    "brend" => $product['brands'] ?? "",
    "kalorije" => $product['nutriments']['energy-kcal_100g'] ?? 0,
    "proteini" => $product['nutriments']['proteins_100g'] ?? 0,
    "masti" => $product['nutriments']['fat_100g'] ?? 0,
    "ugljeni_hidrati" => $product['nutriments']['carbohydrates_100g'] ?? 0,
    "barkod" => $barcode
];
$kalorije = $product['nutriments']['energy-kcal_100g']
    ?? ($product['nutriments']['energy_100g'] / 4.184 ?? 0);

http_response_code(200);

echo json_encode([
    "success" => true,
    "product" => $result
]);
