<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // S'assurer que les contraintes de clés étrangères sont activées
        if (config('database.default') === 'sqlite') {
            $this->app['db']->connection()->statement('PRAGMA foreign_keys=1');
        }
    }
}
