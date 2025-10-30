<?php
require_once '../config/database.php';

class User {
    private $client;
    private $baseUrl;

    public function __construct(Database $db) {
        $this->client = $db->connect();
        $this->baseUrl = $db->getBaseUrl();
    }

    /**
     * Register a new user using Supabase Auth signup endpoint
     * Returns the response array on success, false on failure
     */
    public function create(array $data) {
        try {
            $resp = $this->client->post('/auth/v1/signup', [
                'json' => [
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'data' => [
                        'username' => $data['username'] ?? null
                    ]
                ]
            ]);

            $body = json_decode((string)$resp->getBody(), true);
            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                return $body;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Log in a user via Supabase Auth token endpoint (password grant)
     */
    public function login(string $email, string $password) {
        try {
            $resp = $this->client->post('/auth/v1/token', [
                'json' => [
                    'grant_type' => 'password',
                    'email' => $email,
                    'password' => $password
                ]
            ]);

            $body = json_decode((string)$resp->getBody(), true);
            if ($resp->getStatusCode() === 200) {
                return $body; // contains access_token, refresh_token, user, etc.
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}