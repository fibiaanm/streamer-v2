<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Docker injects env vars from env_file before the process starts, which
        // causes Dotenv's ImmutableRepository to block .env.testing from writing
        // its JWT_SECRET. phpunit.xml force="true" sets getenv() correctly, so
        // we sync config here before the JWT manager is first resolved (lazy).
        if ($secret = getenv('JWT_SECRET')) {
            config(['jwt.secret' => $secret]);
        }
    }
}
