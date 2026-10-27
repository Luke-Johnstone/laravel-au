<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Tests;

use EastpointLabs\LaravelAu\LaravelAuServiceProvider;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // No test may reach the real ABN Lookup service (or any other host).
        // Fake every request with fixtures from tests/Fixtures instead.
        Http::preventStrayRequests();
    }

    /**
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelAuServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('au.abr.guid', 'testing-guid');
        $app['config']->set('cache.default', 'array');
    }
}
