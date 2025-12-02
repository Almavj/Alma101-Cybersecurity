<?php
require_once '../config/database.php';

/**
 * Authenticate incoming request by validating the provided Supabase access token.
 * This implementation calls Supabase Auth `/auth/v1/user` endpoint with the
 * provided access token. If valid, returns the user's id (sub) string.
 */
function getAuthHeader() {
    // Try multiple methods to retrieve Authorization header in different SAPI setups
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') return $value;
        }
    }

    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') return $value;
        }
    }

    // Fallback to common $_SERVER entries
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) return $_SERVER['HTTP_AUTHORIZATION'];
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    return null;
}

function authenticate() {
    $authHeader = getAuthHeader();

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(['message' => 'No token provided']);
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
    $authHeader = getAuthHeader();

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(['message' => 'No token provided']);
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
    if (!isUserAdminFromUser($user)) {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden: admin only']);
        exit();
    }
    return $user['id'] ?? null;
}

/**
 * Determine whether a user object (as returned from /auth/v1/user) represents an admin.
 * This is flexible: it checks several common locations for a role claim (top-level 'role',
 * 'app_metadata' and 'user_metadata') and falls back to comparing email against
 * SUPABASE_ADMIN_EMAIL for backward compatibility.
 */
function isUserAdminFromUser(array $user): bool {
    $adminEmail = $_ENV['SUPABASE_ADMIN_EMAIL'] ?? '';

    // 1) top-level role claim (some JWTs place role here)
    if (isset($user['role']) && strcasecmp($user['role'], 'admin') === 0) {
        return true;
    }

    // 2) app_metadata.role or app_metadata.roles
    if (isset($user['app_metadata'])) {
        if (isset($user['app_metadata']['role']) && strcasecmp($user['app_metadata']['role'], 'admin') === 0) {
            return true;
        }
        if (isset($user['app_metadata']['roles'])) {
            $roles = $user['app_metadata']['roles'];
            if (is_array($roles) && in_array('admin', array_map('strtolower', $roles))) return true;
            if (is_string($roles) && strcasecmp($roles, 'admin') === 0) return true;
        }
    }

    // 3) user_metadata.role or user_metadata.roles
    if (isset($user['user_metadata'])) {
        if (isset($user['user_metadata']['role']) && strcasecmp($user['user_metadata']['role'], 'admin') === 0) {
            return true;
        }
        if (isset($user['user_metadata']['roles'])) {
            $roles = $user['user_metadata']['roles'];
            if (is_array($roles) && in_array('admin', array_map('strtolower', $roles))) return true;
            if (is_string($roles) && strcasecmp($roles, 'admin') === 0) return true;
        }
    }

    // 4) legacy fallback: compare email
    if (!empty($adminEmail) && isset($user['email']) && strcasecmp($user['email'], $adminEmail) === 0) {
        return true;
    }

    return false;
}