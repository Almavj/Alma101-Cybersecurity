<?php
// Minimal index for API root so web server returns a 200 JSON response
header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Alma101 API is running'
]);
