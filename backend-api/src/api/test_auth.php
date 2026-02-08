<?php
header("Content-Type: application/json");
require_once 'auth.php';  // uključi auth.php

// Ako dođeš do ovde, token je validan
echo json_encode([
    "success" => true,
    "message" => "Token je validan!",
    "user" => $user_data  // prikazuje podatke korisnika iz tokena
]);
