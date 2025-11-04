<?php


namespace App\Services;

use Illuminate\Support\Facades\Log;

class LaravelLoggingService implements LoggingServiceInterface
{
    public function log(string $message): void
    {
        Log::info($message);
    }
}