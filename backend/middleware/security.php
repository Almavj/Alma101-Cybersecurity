<?php
// Centralized security headers and CORS handling for backend API endpoints.
// Include and call set_security_headers() at the top of API files.

function set_security_headers() {
    // Content type default for API responses
    header('Content-Type: application/json; charset=UTF-8');

    // CORS: read allowed origins from env (comma-separated)
    $allowed = getenv('CORS_ALLOWED_ORIGINS') ?: ($_ENV['CORS_ALLOWED_ORIGINS'] ?? '');
    $allowedOrigins = array_filter(array_map('trim', explode(',', $allowed)));
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin && in_array($origin, $allowedOrigins)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
    } else {
        // Do not use wildcard when credentials are in use
        if (empty($allowedOrigins) && $origin) {
            // If no configured allowed origins, fall back to same-origin only
            header('Access-Control-Allow-Origin: ' . $origin);
        }
    }

    // Common security headers
    header_remove('X-Powered-By');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: interest-cohort=()');

    // HSTS only when running on HTTPS
    if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
    }

    // Basic Content-Security-Policy for API responses is not strictly necessary,
    // but set a conservative default for any HTML responses that might be served.
    header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; connect-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'");

    // Handle preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
}

// Auto-run when included
set_security_headers();
