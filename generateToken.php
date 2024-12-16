<?php
header('Content-Type: application/json');

require 'vendor/autoload.php';
// Include JWT library
use Firebase\JWT\JWT;

// Replace with a strong secret key
$key = "Yt1T6)|0wA3T";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $userId = $data['user_id'] ?? null;

    if (!$userId) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
        exit;
    }

    // Generate the token
    $payload = [
        "iss" => "alumni_locator", // Issuer
        "iat" => time(),          // Issued At
        "exp" => time() + 3600,   // Expiry (1 hour)
        "user_id" => $userId      // User ID
    ];

    $jwt = JWT::encode($payload, $key, 'HS256');

    // Send the response
    http_response_code(200); // OK
    echo json_encode(['status' => 'success', 'token' => $jwt]);
}
?>
