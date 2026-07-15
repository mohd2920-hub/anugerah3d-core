<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $database = $_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE');

        if (! is_string($database) || ! str_ends_with($database, '_test')) {
            throw new RuntimeException(
                'Refusing to run tests: DB_DATABASE must end with "_test". Never run tests against application data.',
            );
        }

        parent::setUp();
    }
}
