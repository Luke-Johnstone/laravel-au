<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Wires the package into a Laravel application.
 */
class LaravelAuServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-au')
            ->hasConfigFile('au');
    }
}
