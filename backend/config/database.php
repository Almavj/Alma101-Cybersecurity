<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
// Use safeLoad so we can provide a clearer error message if .env is missing
$dotenv->safeLoad();

// Validate required env vars and provide a helpful message if missing
$required = ['SUPABASE_URL', 'SUPABASE_SERVICE_ROLE_KEY', 'SUPABASE_ANON_KEY'];
$missing = [];
foreach ($required as $key) {
    $val = getenv($key) ?: ($_ENV[$key] ?? null);
    // Treat obvious placeholder/example values as "missing" so a real secret must be provided
    $isPlaceholder = false;
    if ($key === 'SUPABASE_SERVICE_ROLE_KEY' && is_string($val)) {
        $lower = strtolower($val);
        if (strpos($lower, 'replace_with') !== false || strpos($lower, 'your_service_role') !== false || strpos($lower, 'your-service-role') !== false) {
            $isPlaceholder = true;
        }
    }
    if (empty($val) || $isPlaceholder) $missing[] = $key;
}
if (!empty($missing)) {
    $msg = "Missing required environment variables: " . implode(', ', $missing) . ".\nPlease create a .env file at the project root (see .env.example) and restart the server.";
    // Log and throw a readable error to help local developers
    error_log('[config] ' . $msg);
    throw new \RuntimeException($msg);
}

class Database {
    private $client;
    private $baseUrl;
    private $serviceRoleKey;

    public function __construct() {
        $this->baseUrl = rtrim($_ENV['SUPABASE_URL'], '/');
        $this->serviceRoleKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'];

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer ' . $this->serviceRoleKey,
                'Content-Type' => 'application/json'
            ],
            'http_errors' => false,
            'timeout' => 10
        ]);
    }

    /**
     * Return configured Guzzle client
     * Server-side code should use this client to call Supabase REST and Auth endpoints.
     */
    public function connect(): Client {
        return $this->client;
    }

    /**
     * Backwards-compatible accessor used elsewhere in the codebase.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->connect();
    }

    public function getBaseUrl(): string {
        return $this->baseUrl;
    }
}