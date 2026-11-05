<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use EastpointLabs\LaravelAu\LaravelAuServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // No test may reach the real ABN Lookup service (or any other host).
        // Fake every request with fixtures from tests/Fixtures instead.
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Each test's application is a web of objects that refer to each other, which PHP only
        // frees when it collects cycles. Collecting them now keeps the suite well inside PHP's
        // default memory limit.
        gc_collect_cycles();
    }

    /**
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...self::filamentProviders(),
            LaravelAuServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Livewire signs component snapshots with the app key.
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('au.abr.guid', 'testing-guid');
        $app['config']->set('cache.default', 'array');
    }

    /**
     * The providers a Filament form needs, when Filament is installed. The suite also runs
     * without Filament, so these names are only strings until it's known to be there.
     *
     * @return list<class-string<ServiceProvider>>
     */
    private static function filamentProviders(): array
    {
        if (! class_exists(FilamentServiceProvider::class)) {
            return [];
        }

        return [
            LivewireServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
        ];
    }
}
