<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\LaravelAuServiceProvider;
use Illuminate\Support\ServiceProvider;

mutates(LaravelAuServiceProvider::class);

dataset('validation messages', [
    'abn' => ['abn', 'The :attribute must be a valid ABN.'],
    'acn' => ['acn', 'The :attribute must be a valid ACN.'],
    'abn_or_acn' => ['abn_or_acn', 'The :attribute must be a valid ABN or ACN.'],
    'abn_active' => ['abn_active', 'The :attribute must be an active ABN.'],
    'abn_gst' => ['abn_gst', 'The :attribute must be registered for GST.'],
    'abn_unverifiable' => ['abn_unverifiable', 'The :attribute could not be checked right now. Please try again.'],
    'bsb' => ['bsb', 'The :attribute must be a valid BSB.'],
    'au_phone' => ['au_phone', 'The :attribute must be a valid Australian phone number.'],
    'au_postcode' => ['au_postcode', 'The :attribute must be a valid Australian postcode.'],
    'au_postcode_state' => ['au_postcode_state', 'The :attribute must be in :state.'],
]);

it('merges the package config', function (): void {
    expect(config('au.abr.base_url'))->toBe('https://abr.business.gov.au/json')
        ->and(config('au.abr.cache.ttl'))->toBe(86400)
        ->and(config('au.phone.store_as'))->toBe('e164');
});

it('publishes the config under the au-config tag', function (): void {
    expect(ServiceProvider::pathsToPublish(LaravelAuServiceProvider::class, 'au-config'))->not->toBeEmpty();
});

it('publishes the translations under the au-translations tag', function (): void {
    expect(ServiceProvider::pathsToPublish(LaravelAuServiceProvider::class, 'au-translations'))->not->toBeEmpty();
});

it('loads the validation messages under the au namespace', function (string $key, string $message): void {
    expect(__("au::validation.{$key}"))->toBe($message);
})->with('validation messages');
