<?php

namespace App\Middleware;

use Predis\Client;

class RateLimit {
    private $redis;
    
    public function __construct() {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ]);
    }

    public function handle($request, $next) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit:$ip";
        
        $requests = $this->redis->get($key) ?: 0;
        
        if ($requests >= $_ENV['RATE_LIMIT_REQUESTS']) {
            http_response_code(429);
            echo json_encode(['error' => 'Too Many Requests']);
            exit;
        }
        
        $this->redis->incr($key);
        if ($requests === 0) {
            $this->redis->expire($key, $_ENV['RATE_LIMIT_PER_MINUTES'] * 60);
        }
        
        return $next($request);
    }
}