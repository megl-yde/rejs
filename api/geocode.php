<?php
/**
 * Geocoding API endpoint
 * Proxies requests to Nominatim API to avoid CORS issues
 */

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$country = isset($_GET['country']) ? trim($_GET['country']) : '';

if (empty($city) || empty($country)) {
    http_response_code(400);
    echo json_encode(['error' => 'City and country are required']);
    exit;
}

// Use the shared geocodeLocation function
$coordinates = geocodeLocation($city, $country);

if ($coordinates) {
    echo json_encode([
        'lat' => $coordinates['lat'],
        'lon' => $coordinates['lon']
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Location not found']);
}


