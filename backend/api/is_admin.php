<?php
require_once __DIR__ . '/../middleware/security.php';
require_once __DIR__ . '/../middleware/auth.php';

// Allow preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Lightweight check: read Authorization header and verify the authenticated user's
// role/metadata contains admin using the centralized helper. Returns JSON { admin: true|false }.

$authHeader = getAuthHeader();
if (!$authHeader) {
    echo json_encode(['admin' => false]);
    exit();
}

$accessToken = str_replace('Bearer ', '', $authHeader);

$db = new Database();
$client = $db->getClient();

try {
    $resp = $client->get('/auth/v1/user', [
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'apikey' => $_ENV['SUPABASE_ANON_KEY'] ?? ''
        ]
    ]);

    if ($resp->getStatusCode() !== 200) {
        echo json_encode(['admin' => false]);
        exit();
    }

    $body = json_decode((string)$resp->getBody(), true);
    $isAdmin = isUserAdminFromUser($body);

    echo json_encode(['admin' => $isAdmin]);
    exit();
} catch (Exception $e) {
    // On errors, be conservative and return not-admin
    echo json_encode(['admin' => false]);
    exit();
}

?>
