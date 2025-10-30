<?php
require_once '../config/database.php';

/**
 * Authenticate incoming request by validating the provided Supabase access token.
 * This implementation calls Supabase Auth `/auth/v1/user` endpoint with the
 * provided access token. If valid, returns the user's id (sub) string.
 */
function authenticate() {
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['message' => 'No token provided']);
        exit();
    }

    $accessToken = str_replace('Bearer ', '', $headers['Authorization']);

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
            http_response_code(401);
            echo json_encode(['message' => 'Invalid token']);
            exit();
        }

        $body = json_decode((string)$resp->getBody(), true);
        // return user id for backward compatibility
        return $body['id'] ?? null;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid token']);
        exit();
    }
}

/**
 * Return the authenticated user's full data (array) or exit with 401.
 */
function getAuthenticatedUser() {
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['message' => 'No token provided']);
        exit();
    }

    $accessToken = str_replace('Bearer ', '', $headers['Authorization']);

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
            http_response_code(401);
            echo json_encode(['message' => 'Invalid token']);
            exit();
        }

        $body = json_decode((string)$resp->getBody(), true);
        return $body;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid token']);
        exit();
    }
}

/**
 * Authenticate and ensure user is admin. Returns user id if admin.
 */
function authenticateAdmin() {
    $user = getAuthenticatedUser();
    $adminEmail = $_ENV['SUPABASE_ADMIN_EMAIL'] ?? 'machariaallan881@gmail.com';
    if (!isset($user['email']) || strtolower($user['email']) !== strtolower($adminEmail)) {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden: admin only']);
        exit();
    }
    return $user['id'] ?? null;
}