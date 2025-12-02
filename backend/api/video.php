<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';


require_once __DIR__ . '/../middleware/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$supabase = $database->getClient();

// Configuration
$config = [
    'max_file_size' => 500 * 1024 * 1024, // 500MB
    'allowed_mime_types' => [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
        'video/x-msvideo',
        'image/jpeg',
        'image/png',
        'image/gif'
    ],
    'bucket_id' => 'videos'
];

function validateUpload($file) {
    global $config;
    
    // Check if file was uploaded
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed. Error code: ' . ($file['error'] ?? 'unknown'));
    }
    
    // Check file size
    if ($file['size'] > $config['max_file_size']) {
        throw new Exception('File too large. Maximum size: ' . 
            round($config['max_file_size'] / (1024 * 1024)) . 'MB');
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $config['allowed_mime_types'])) {
        throw new Exception('Invalid file type. Allowed types: ' . 
            implode(', ', $config['allowed_mime_types']));
    }
    
    // Validate filename
    $filename = $file['name'];
    if (preg_match('/\.{2}|\/|\\\\/', $filename)) {
        throw new Exception('Invalid filename');
    }
    
    // Sanitize filename
    $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $safe_filename = time() . '_' . $safe_filename;
    
    return [
        'tmp_path' => $file['tmp_name'],
        'mime_type' => $mime_type,
        'original_name' => $filename,
        'safe_name' => $safe_filename,
        'size' => $file['size']
    ];
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // List all videos with pagination
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            // Try to get videos from storage
            $data = [];

            $response = $supabase->request('GET', '/storage/v1/object/list/' . $config['bucket_id'], [
                'query' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'sortBy' => 'created_at',
                    'order' => 'desc'
                ]
            ]);

            $result = json_decode((string)$response->getBody(), true);

            // Initialize $data as empty array
            $data = [];

            // Only process if result is an array and not an error
            if (is_array($result) && !isset($result['error'])) {
                $data = $result;

                // Add public URLs to each item
                foreach ($data as &$item) {
                    if (is_array($item) && isset($item['name'])) {
                        $item['public_url'] = getenv('SUPABASE_URL') . '/storage/v1/object/public/' .
                            $config['bucket_id'] . '/' . urlencode($item['name']);
                    }
                }
            } else {
                // Log error but return empty array
                error_log('Storage API returned error or non-array: ' . print_r($result, true));
            }
            
            // Return list with pagination (no unrelated success/message keys)
            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'has_more' => is_array($data) && count($data) === $limit
                ]
            ]);
            break;
            
        case 'POST':
            // Handle file upload
            if (!isset($_FILES['video'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No video file provided']);
                break;
            }
            
            $validated = validateUpload($_FILES['video']);
            $fileContent = file_get_contents($validated['tmp_path']);
            
            if ($fileContent === false) {
                throw new Exception('Could not read uploaded file');
            }
            
            // Get admin user for ownership (optional)
            $adminId = null;
            $adminEmail = getenv('SUPABASE_ADMIN_EMAIL');
            if ($adminEmail) {
                try {
                    $adminResponse = $supabase->request('GET', '/auth/v1/admin/users', [
                        'query' => ['email' => $adminEmail]
                    ]);
                    
                    if ($adminResponse->getStatusCode() === 200) {
                        $adminUsers = json_decode($adminResponse->getBody(), true);
                        $adminId = $adminUsers[0]['id'] ?? null;
                    }
                } catch (Exception $e) {
                    // Continue without admin ID
                    error_log('Could not fetch admin user: ' . $e->getMessage());
                }
            }
            
            // Prepare upload parameters
            $queryParams = [];
            if ($adminId) {
                $queryParams['owner'] = $adminId;
            }
            
            // Upload to Supabase Storage
            $uploadResponse = $supabase->request('POST', 
                "/storage/v1/object/{$config['bucket_id']}/{$validated['safe_name']}", 
                [
                    'body' => $fileContent,
                    'headers' => [
                        'Content-Type' => $validated['mime_type'],
                        'x-upsert' => 'true',
                        'Cache-Control' => 'public, max-age=3600'
                    ],
                    'query' => $queryParams
                ]
            );
            
            if ($uploadResponse->getStatusCode() === 200) {
                echo json_encode([
                    'success' => true,
                    'filename' => $validated['safe_name'],
                    'original_name' => $validated['original_name'],
                    'owner' => $adminId,
                    'size' => $validated['size'],
                    'mime_type' => $validated['mime_type'],
                    'public_url' => getenv('SUPABASE_URL') . '/storage/v1/object/public/' . 
                        $config['bucket_id'] . '/' . urlencode($validated['safe_name'])
                ]);
            } else {
                throw new Exception('Storage upload failed: ' . $uploadResponse->getBody());
            }
            break;
            
        case 'DELETE':
            // Admin-only deletion
            $auth = authenticateAdmin();
            if (!$auth) {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                break;
            }
            
            $filename = $_GET['filename'] ?? '';
            if (empty($filename)) {
                http_response_code(400);
                echo json_encode(['error' => 'Filename required']);
                break;
            }
            
            // Validate filename
            if (preg_match('/\.{2}|\/|\\\\/', $filename)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid filename']);
                break;
            }
            
            $deleteResponse = $supabase->request('DELETE', 
                "/storage/v1/object/{$config['bucket_id']}/{$filename}"
            );
            
            $success = $deleteResponse->getStatusCode() === 200;
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'File deleted successfully' : 'Failed to delete file'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('Video API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>
