<?php


namespace App\Services;

interface LoggingServiceInterface
{
    public function log(string $message): void;
}