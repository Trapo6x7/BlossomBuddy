<?php

namespace App\Exceptions;

use Exception;

class ApiRateLimitException extends Exception
{
    protected $rateLimitInfo;

    public function __construct($response)
    {
        $data = $response->json();
        
        $this->rateLimitInfo = [
            'limit' => $data['X-RateLimit-Limit'] ?? 0,
            'remaining' => $data['X-RateLimit-Remaining'] ?? 0,
            'retry_after_seconds' => $data['Retry-After'] ?? 0,
            'reset_timestamp' => $data['X-RateLimit-Reset'] ?? 0,
            'message' => $data['X-RateLimit-Exceeded'] ?? 'Rate limit exceeded'
        ];

        // Calcul du temps d'attente lisible
        $retryAfter = $this->rateLimitInfo['retry_after_seconds'];
        $hours = floor($retryAfter / 3600);
        $minutes = floor(($retryAfter % 3600) / 60);
        
        $waitTime = '';
        if ($hours > 0) {
            $waitTime .= $hours . 'h ';
        }
        if ($minutes > 0) {
            $waitTime .= $minutes . 'min';
        }
        if (empty($waitTime)) {
            $waitTime = $retryAfter . ' secondes';
        }

        $message = "Quota API dépassé. Limite: {$this->rateLimitInfo['limit']} requêtes. " .
                  "Temps d'attente avant reset: {$waitTime}";

        parent::__construct($message);
    }

    public function getRateLimitInfo()
    {
        return $this->rateLimitInfo;
    }
}