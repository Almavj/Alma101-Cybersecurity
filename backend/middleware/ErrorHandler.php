<?php

namespace App\Middleware;

class ErrorHandler {
    public function handle($request, $next) {
        try {
            return $next($request);
        } catch (\Exception $e) {
            $statusCode = $this->getStatusCode($e);
            
            $response = [
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $statusCode,
                    'type' => get_class($e)
                ]
            ];
            
            if ($_ENV['APP_DEBUG'] === 'true') {
                $response['error']['trace'] = $e->getTrace();
            }
            
            http_response_code($statusCode);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
    
    private function getStatusCode(\Exception $e): int {
        if ($e instanceof \App\Exceptions\ValidationException) {
            return 422;
        }
        
        if ($e instanceof \App\Exceptions\AuthenticationException) {
            return 401;
        }
        
        if ($e instanceof \App\Exceptions\AuthorizationException) {
            return 403;
        }
        
        if ($e instanceof \App\Exceptions\NotFoundException) {
            return 404;
        }
        
        return 500;
    }
}