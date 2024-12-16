<?php
require 'vendor/autoload.php'; // Include Redis client

use Predis\Client;

function rateLimit($key, $limit = 100, $window = 60) {
    $redis = new Client(); // Connect to Redis
    $currentCount = $redis->get($key);

    if ($currentCount === null) {
        $redis->setex($key, $window, 1); // Start a new window
        return true;
    }

    if ($currentCount >= $limit) {
        return false; // Limit exceeded
    }

    $redis->incr($key); // Increment the count
    return true;
}
?>
