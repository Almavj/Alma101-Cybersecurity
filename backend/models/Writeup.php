<?php
require_once '../config/database.php';

class Writeup {
    private $client;
    private $baseUrl;

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

            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                return json_decode((string)$resp->getBody(), true);
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
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
        try {
            $resp = $this->client->get('/rest/v1/writeups?id=eq.' . $id, [
                'headers' => ['Accept' => 'application/json']
            ]);
            $data = json_decode((string)$resp->getBody(), true);
            return $data[0] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function update(string $id, array $data) {
        try {
            $resp = $this->client->patch('/rest/v1/writeups?id=eq.' . $id, [
                'json' => $data,
                'headers' => ['Prefer' => 'return=representation']
            ]);
            return $resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete(string $id) {
        try {
            $resp = $this->client->delete('/rest/v1/writeups?id=eq.' . $id);
            return $resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300;
        } catch (Exception $e) {
            return false;
        }
    }
}
