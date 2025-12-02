<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$health = [
    "status" => "healthy",
    "service" => "Alma101 Security API",
    "timestamp" => date("Y-m-d H:i:s"),
    "environment" => getenv('APP_ENV') ?: 'development',
    "version" => "1.0.0"
];

// Try database connection if configured
try {
    if (file_exists(__DIR__ . '/../config/database.php')) {
        require_once __DIR__ . '/../config/database.php';
        $health['database'] = ['connected' => true];
    }
} catch (Exception $e) {
    $health['database'] = ['connected' => false, 'error' => $e->getMessage()];
}

http_response_code(200);
echo json_encode($health, JSON_PRETTY_PRINT);
