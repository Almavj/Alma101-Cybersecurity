<?php
require_once '../config/database.php';

class Writeup {
    private $client;
    private $baseUrl;
    private $lastResponse;

    public function __construct(Database $db) {
        $this->client = $db->connect();
        $this->baseUrl = $db->getBaseUrl();
    }

    public function create(array $data) {
        try {
            $resp = $this->client->post('/rest/v1/writeups', [
                'json' => $data,
                'headers' => [ 'Prefer' => 'return=representation' ]
            ]);

            $status = $resp->getStatusCode();
            $body = (string)$resp->getBody();
            $this->lastResponse = ['status' => $status, 'body' => $body];

            if ($status >= 200 && $status < 300) {
                return json_decode($body, true);
            }
            return false;
        } catch (Exception $e) {
            $this->lastResponse = ['status' => 500, 'body' => $e->getMessage()];
            return false;
        }
    }

    public function getLastResponse() {
        return $this->lastResponse ?? null;
    }

    public function getAll(int $limit = 10, int $page = 1) {
        $offset = ($page - 1) * $limit;
        $query = http_build_query(['select' => '*', 'limit' => $limit, 'offset' => $offset, 'order' => 'created_at.desc']);
        try {
            $resp = $this->client->get('/rest/v1/writeups?' . $query, [
                'headers' => ['Accept' => 'application/json']
            ]);
            return json_decode((string)$resp->getBody(), true);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getById(string $id) {
        if (!$this->isValidId($id)) return null;
        try {
            $resp = $this->client->get('/rest/v1/writeups?id=eq.' . urlencode($id), [
                'headers' => ['Accept' => 'application/json']
            ]);
            $data = json_decode((string)$resp->getBody(), true);
            return $data[0] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function update(string $id, array $data) {
        if (!$this->isValidId($id)) return false;
        try {
            $resp = $this->client->patch('/rest/v1/writeups?id=eq.' . urlencode($id), [
                'json' => $data,
                'headers' => ['Prefer' => 'return=representation']
            ]);
            $status = $resp->getStatusCode();
            $body = (string)$resp->getBody();
            $this->lastResponse = ['status' => $status, 'body' => $body];

            if ($status >= 200 && $status < 300) {
                // Supabase returns the updated rows as a JSON array when return=representation
                $decoded = json_decode($body, true);
                return $decoded;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete(string $id) {
        if (!$this->isValidId($id)) return false;
        try {
            $resp = $this->client->delete('/rest/v1/writeups?id=eq.' . urlencode($id));
            return $resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300;
        } catch (Exception $e) {
            return false;
        }
    }

    private function isValidId(string $id): bool {
        if (ctype_digit($id)) return true;
        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $id)) return true;
        return false;
    }
}
