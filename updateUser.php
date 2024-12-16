<?php
// Set the Content-Type to application/json
header('Content-Type: application/json');

// Update user details: /updateUser.php
require 'db.php';
require 'rateLimiter.php';
require 'validateToken.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
	$clientIp = $_SERVER['REMOTE_ADDR']; // Identify the client by IP
	$rateLimitKey = "rate_limit:" . $clientIp;

	if (!rateLimit($rateLimitKey, 60, 60)) { // Allow 60 requests per minute
		http_response_code(429); // Too Many Requests
		echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded. Try again later.']);
		exit;
	}

	// Get Authorization Header
	$headers = getallheaders();
	$authHeader = $headers['Authorization'] ?? null;

	if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
		http_response_code(401); // Unauthorized
		echo json_encode(['status' => 'error', 'message' => 'Authorization token is missing']);
		exit;
	}

	$token = $matches[1]; // Extract token

	// Validate the token
	$userId = validateToken($token);
	if (!$userId) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
        exit;
    }

	// Get the raw PUT data
	$data = json_decode(file_get_contents("php://input"), true);

	$id = $data['id'] ?? null;
	$name = $data['name'] ?? null;
	$email = $data['email'] ?? null;
	$latitude = $data['latitude'] ?? null;
	$longitude = $data['longitude'] ?? null;

	// Validate input
	if (!$id || !$name || !$email || !$latitude || !$longitude) {
		http_response_code(400);
		echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
		exit;
	}

	// Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }

    // Validate latitude and longitude
    if (!is_numeric($latitude) || $latitude < -90 || $latitude > 90) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Invalid latitude. Must be a number between -90 and 90.']);
        exit;
    }

    if (!is_numeric($longitude) || $longitude < -180 || $longitude > 180) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Invalid longitude. Must be a number between -180 and 180.']);
        exit;
    }

	// Ensure the user can only update their own details
	if ($id != $userId) {
		http_response_code(403); // Forbidden
		echo json_encode(['status' => 'error', 'message' => 'You are not authorized to update this user']);
		exit;
	}

	// Prepare data for update
	$db->where('id', $id);
	$data = [
		'name' => $name,
		'email' => $email,
		// Use the MySQL POINT() function to store latitude and longitude
		'location' => $db->func('POINT(?, ?)', [$latitude, $longitude])
	];

	// Update user in the database
	if ($db->update('users', $data)) {
		http_response_code(200);  // OK
		echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
	} else {
		http_response_code(500);  // Internal Server Error
		echo json_encode(['status' => 'error', 'message' => 'Failed to update user']);
	}
}
