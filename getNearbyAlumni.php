<?php
// Set the Content-Type to application/json
header('Content-Type: application/json');

// Retrieve nearby alumni: /getNearbyAlumni.php
require 'db.php';
require 'rateLimiter.php';
require 'validateToken.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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

	$user_id = $_GET['user_id'] ?? null;
	$radius = $_GET['radius'] ?? 10; // Default radius is 10 km
	$page = $_GET['page'] ?? 1; // Default page is 1
	$limit = $_GET['limit'] ?? 10; // Default limit is 10 records

	if (!$user_id) {
		http_response_code(400); // Bad Request
		echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
		exit;
	}

	// Ensure the user can only use their own details
	if ($user_id != $userId) {
		http_response_code(403); // Forbidden
		echo json_encode(['status' => 'error', 'message' => 'You are not authorized']);
		exit;
	}

	// Get user's location
	$db->where('id', $user_id);
	$user = $db->getOne('users', ['location']);
	if (!$user) {
		http_response_code(200); // Changed to 200 based on feedback
		echo json_encode(['status' => 'error', 'message' => 'User not found']);
		exit;
	}

	// Extract latitude and longitude from the POINT data type
	$latLng = $user['location'];
	$lat = $db->rawQueryValue("SELECT ST_Y(?) AS lat", [$latLng]);
	$lng = $db->rawQueryValue("SELECT ST_X(?) AS lng", [$latLng]);

	if ($lat === null || $lng === null) {
		http_response_code(200); // Changed to 200 based on feedback
		echo json_encode(['status' => 'error', 'message' => 'Invalid user location']);
		exit;
	}

	$lat = $lat[0];
	$lng = $lng[0];

	// Calculate OFFSET for pagination
	$offset = ($page - 1) * $limit;

	// Haversine formula to find nearby alumni using ST_Distance_Sphere()
	$query = "
		SELECT 
			id, name, email, ST_AsText(location) AS location,
			(6378 * acos(
				cos(radians(?)) * cos(radians(ST_Y(location))) * 
				cos(radians(ST_X(location)) - radians(?)) + 
				sin(radians(?)) * sin(radians(ST_Y(location)))
			)) AS distance
		FROM users
		WHERE id != ?
		HAVING distance <= ?
		ORDER BY distance ASC
		LIMIT ? OFFSET ?
	";

	$bindings = [$lat, $lng, $lat, $user_id, $radius, $limit, $offset];

	// Execute query to find nearby alumni
	$nearbyAlumni = $db->rawQuery($query, $bindings);

	if (empty($nearbyAlumni)) {
		http_response_code(200); // Changed to 200 based on feedback
		echo json_encode(['status' => 'error', 'message' => 'No nearby alumni found']);
		exit;
	}

	// Count total records within the radius for pagination metadata
	$totalQuery = "
		SELECT COUNT(*) AS total
		FROM (
			SELECT 
				(6378 * acos(
					cos(radians(?)) * cos(radians(ST_Y(location))) * 
					cos(radians(ST_X(location)) - radians(?)) + 
					sin(radians(?)) * sin(radians(ST_Y(location)))
				)) AS distance
			FROM users
			WHERE id != ?
			HAVING distance <= ?
		) AS subquery
	";

	$totalQueryBindings = [$lat, $lng, $lat, $user_id, $radius];

	$totalRecords = $db->rawQuery($totalQuery, $totalQueryBindings)[0]['total'];

	// Calculate total pages
	$totalPages = ceil($totalRecords / $limit);

	// Set HTTP response code to 200 (OK) for success
	http_response_code(200);
	// Send the response
	echo json_encode([
		'status' => 'success',
		'data' => $nearbyAlumni,
		'pagination' => [
			'total_records' => $totalRecords,
			'total_pages' => $totalPages,
			'current_page' => (int) $page,
			'limit' => (int) $limit
		]
	]);
}
