<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../middleware/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EmailService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

function getJsonBody() {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

$data = getJsonBody();
$userId = trim($data['user_id'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($userId) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters long']);
    exit();
}

try {
    // Ensure the requester is an admin via Supabase token
    require_once __DIR__ . '/../middleware/auth.php';
    authenticateAdmin(); // will exit() with 401/403 if not admin

    $db = new Database();
    $client = $db->getClient();

    // Update user password using Supabase admin API
    $resp = $client->put('/auth/v1/admin/users/' . $userId, [
        'json' => ['password' => $password],
        'headers' => ['Content-Type' => 'application/json']
    ]);

    $body = json_decode((string)$resp->getBody(), true);

    if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
        // Optionally notify the user
        if (!empty($body['email'])) {
            $mailer = new \Services\EmailService();
            $mailer->sendPasswordChangedNotification($body['email']);
        }
        echo json_encode(['message' => 'Password has been reset successfully']);
        exit();
    } else {
        error_log('Admin reset error: ' . print_r($body, true));
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred while processing your request']);
        exit();
    }

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your request']);
    exit();
}

?>
