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

// Helper to read JSON body
function getJsonBody() {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

$data = getJsonBody();
$email = trim($data['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit();
}

try {
    $db = new Database();
    $client = $db->getClient();

    // Use Supabase's recover endpoint to send a password reset email.
    $redirectTo = $data['redirect_to'] ?? 'https://alma101.vercel.app/auth';

    $resp = $client->post('/auth/v1/recover', [
        'json' => [
            'email' => $email,
            'redirect_to' => $redirectTo
        ],
        // Use anon key for public auth actions
        'headers' => [
            'apikey' => $_ENV['SUPABASE_ANON_KEY'] ?? '',
            'Content-Type' => 'application/json'
        ]
    ]);

    $body = json_decode((string)$resp->getBody(), true);

    if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
        echo json_encode(['message' => 'If an account exists with this email, you will receive password reset instructions.']);
        exit();
    } else {
        error_log('Reset error: ' . print_r($body, true));
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred while processing your request.']);
        exit();
    }

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your request.']);
    exit();
}

?>