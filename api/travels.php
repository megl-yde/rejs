<?php
/**
 * API endpoint to return all travels as JSON
 * Used for map visualization
 */

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();
    $sql = "SELECT id, city, country, year, description, latitude, longitude 
            FROM travels 
            ORDER BY year DESC, city ASC";
    $stmt = $pdo->query($sql);
    $travels = $stmt->fetchAll();
    
    // Filter out travels without coordinates for map display
    $travelsWithCoordinates = array_filter($travels, function($travel) {
        return !is_null($travel['latitude']) && !is_null($travel['longitude']);
    });
    
    echo json_encode(array_values($travelsWithCoordinates), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    error_log("Error fetching travels: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}

