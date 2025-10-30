<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

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