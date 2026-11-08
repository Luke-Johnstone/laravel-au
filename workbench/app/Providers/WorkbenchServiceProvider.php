<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use EastpointLabs\LaravelAu\Facades\Abr;
use Filament\PanelProvider;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Providers\Filament\AdminPanelProvider;

/**
 * Sets up the demo app. Without an ABR_GUID, ABN Lookup answers from a fake, so the demo works
 * offline.
 */
final class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Filament is optional, and Composer boots the demo's providers after every install,
        // including one without Filament.
        if (class_exists(PanelProvider::class)) {
            $this->app->register(AdminPanelProvider::class);
        }
    }

    public function boot(): void
    {
        if (blank(config('au.abr.guid'))) {
            Abr::fake([
                '51824753556' => ['EntityName' => 'Example Holdings Pty Ltd'],
                '53004085616' => ['EntityName' => 'Example Trading Pty Ltd', 'Acn' => '004085616'],
                '83914571673' => ['EntityName' => 'Example Cancelled Pty Ltd', 'AbnStatus' => 'Cancelled'],
            ]);
        }
    }
}
