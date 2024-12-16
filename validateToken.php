<?php
require 'vendor/autoload.php'; // Include JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Validates a JWT token and returns the user ID if valid.
 * 
 * @param string $token The JWT token to validate.
 * @return int|null Returns the user ID if the token is valid, or null if invalid.
 */
function validateToken($token) {
    // Replace with your secret key
    $key = "Yt1T6)|0wA3T";

    try {
        // Decode the token
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        return $decoded->user_id; // Return the user ID
    } catch (Exception $e) {
        return null; // Return null if the token is invalid or expired
    }
}
?>
