<?php
require_once __DIR__ . '/../middleware/security.php';
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create user. Data is incomplete."]);
    exit();
}

$username = $data->username ?? null;

try {
    $db = new Database();
    $client = $db->getClient();

    $payload = [
        'email' => $data->email,
        'password' => $data->password,
    ];
    if ($username) {
        $payload['user_metadata'] = ['username' => $username];
    }

    // Create user via Supabase admin API
    $resp = $client->post('/auth/v1/admin/users', [
        'json' => $payload,
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ]);

    $body = json_decode((string)$resp->getBody(), true);

    if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
        http_response_code(201);
        echo json_encode(["message" => "User was created.", 'user' => $body]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to create user.", 'error' => $body]);
    }
} catch (Exception $e) {
    error_log('Register error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "An error occurred while creating user.", 'error' => $e->getMessage()]);
}