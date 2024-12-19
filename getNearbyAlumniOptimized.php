<?php
// Set the Content-Type to application/json
header('Content-Type: application/json');

// Retrieve nearby alumni: /getNearbyAlumni.php
require 'db.php';
require 'rateLimiter.php';
require 'validateToken.php';
require 'apiHelper.php';
require 'redis.php';

// $redis->flushAll();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$userId = handleApiRequest();

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

	// Check Redis cache first for nearby alumni
	$cacheKey = "nearby_alumni:{$user_id}:{$radius}:{$page}:{$limit}";
	$cachedResult = $redis->get($cacheKey);

	if ($cachedResult) {
		// If cached result exists, return it
		http_response_code(200);
		echo $cachedResult;
		exit;
	}

	// Get user's latitude and longitude directly
	$db->where('id', $user_id);
	$user = $db->getOne('users', ['latitude', 'longitude']);

	if (!$user) {
		http_response_code(200); // Changed to 200 based on feedback
		echo json_encode(['status' => 'error', 'message' => 'User not found']);
		exit;
	}

	$lat = $user['latitude'];
	$lng = $user['longitude'];

	// Calculate min/max latitude and longitude for the bounding box
	$earthRadius = 6378;  // Earth's radius in kilometers
	$deltaLat = $radius / $earthRadius;
	$deltaLng = $radius / ($earthRadius * cos(deg2rad($lat)));

	$minLat = $lat - rad2deg($deltaLat);
	$maxLat = $lat + rad2deg($deltaLat);
	$minLon = $lng - rad2deg($deltaLng);
	$maxLon = $lng + rad2deg($deltaLng);

	// Calculate OFFSET for pagination
	$offset = ($page - 1) * $limit;
	$radiusInMeters = $radius * 1000;

	// echo "Min Lat: ". $minLat;
	// echo "Min Long: ". $minLon;
	// echo "Max Lat: ". $maxLat;
	// echo "Max Long: ". $maxLon;

	// Use the ORM's pagination and ST_Distance_Sphere for optimized query
	$db->where("longitude BETWEEN ? AND ?", [$minLon, $maxLon]);
	$db->where("latitude BETWEEN ? AND ?", [$minLat, $maxLat]);

	// Fetch the nearby users (excluding the current user)
	$nearbyAlumni = $db->arrayBuilder()->withTotalCount()
		->rawQuery("
            SELECT id, name, email, latitude, longitude, 
                   ST_Distance_Sphere(location, POINT(?, ?)) AS distance
            FROM users
            WHERE id != ?
            HAVING distance <= ?
			ORDER BY distance ASC
            LIMIT ? OFFSET ?
        ", [
			$lng,
			$lat,
			$user_id,
			$radiusInMeters,
			$limit,
			$offset
		]);

	if (empty($nearbyAlumni)) {
		http_response_code(200); // Changed to 200 based on feedback
		echo json_encode(['status' => 'error', 'message' => 'No nearby alumni found']);
		exit;
	}

	// Manually count total records within the radius (for pagination metadata)
	$totalQuery = "
        SELECT COUNT(*) AS total
        FROM users
        WHERE id != ?
          AND longitude BETWEEN ? AND ?
          AND latitude BETWEEN ? AND ?
          AND ST_Distance_Sphere(location, POINT(?, ?)) <= ?
    ";

	$totalQueryBindings = [$user_id, $minLon, $maxLon, $minLat, $maxLat, $lng, $lat, $radiusInMeters];

	// Execute the total count query
	$totalRecordsResult = $db->rawQuery($totalQuery, $totalQueryBindings);
	$totalRecords = $totalRecordsResult[0]['total'] ?? 0;

	// Calculate total pages
	$totalPages = ceil($totalRecords / $limit);

	$response = json_encode([
		'status' => 'success',
		'data' => $nearbyAlumni,
		'pagination' => [
			'total_records' => $totalRecords,
			'total_pages' => $totalPages,
			'current_page' => (int) $page,
			'limit' => (int) $limit
		]
	]);

	// Store the result in Redis with a 1-hour TTL
	$redis->setex($cacheKey, 3600, $response);

	// Set HTTP response code to 200 (OK) for success
	http_response_code(200);
	// Send the response
	echo $response;
}
