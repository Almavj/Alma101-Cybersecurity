<?php
require_once __DIR__ . '/../middleware/security.php';
// Optional: auth helper provides isUserAdminFromUser() for robust role checks
require_once __DIR__ . '/../middleware/auth.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Your existing login code continues here...
$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to login. Data is incomplete."]);
    exit();
}

try {
    // Read configuration from environment
    $supabaseUrl = getenv('SUPABASE_URL') ?: ($_ENV['SUPABASE_URL'] ?? 'https://vmwuglqrafyzrriygzyn.supabase.co');
    $anonKey = getenv('SUPABASE_ANON_KEY') ?: ($_ENV['SUPABASE_ANON_KEY'] ?? null);

    if (empty($anonKey)) {
        http_response_code(500);
        echo json_encode(["message" => "Server misconfiguration: SUPABASE_ANON_KEY is not set. Please configure backend/.env or environment variables."]);
        exit();
    }
    
    // Use the working format: grant_type in URL, JSON body
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $supabaseUrl . '/auth/v1/token?grant_type=password',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $data->email,
            'password' => $data->password,
        ]),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $anonKey,
            'Content-Type: application/json',
        ],
    ]);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $body = json_decode($response, true);
    
    if ($statusCode >= 200 && $statusCode < 300 && isset($body['access_token'])) {
        // Login successful
        http_response_code(200);
        // Determine admin flag. Prefer checking explicit role/metadata via
        // isUserAdminFromUser() (if available), otherwise fall back to email compare.
        $userData = $body['user'] ?? [];
        $isAdmin = false;
        if (function_exists('isUserAdminFromUser')) {
            $isAdmin = isUserAdminFromUser($userData);
        } else {
            $userEmail = $userData['email'] ?? null;
            $adminEmail = getenv('SUPABASE_ADMIN_EMAIL') ?: ($_ENV['SUPABASE_ADMIN_EMAIL'] ?? '');
            if ($userEmail && $adminEmail && strcasecmp($userEmail, $adminEmail) === 0) {
                $isAdmin = true;
            }
        }

        echo json_encode([
            'message' => 'Login successful.',
            'user' => [
                'id' => $body['user']['id'] ?? null,
                'email' => $userEmail,
                'access_token' => $body['access_token'] ?? null,
                'refresh_token' => $body['refresh_token'] ?? null,
                'is_admin' => $isAdmin,
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            "message" => "Login failed.", 
            'error' => $body,
            'status_code' => $statusCode
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Login failed.", 
        'error' => $e->getMessage()
    ]);
}
?>