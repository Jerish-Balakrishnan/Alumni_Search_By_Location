<?php

function handleApiRequest()
{
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

    return $userId;
}
