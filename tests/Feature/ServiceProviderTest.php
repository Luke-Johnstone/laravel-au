<?php

declare(strict_types=1);

it('merges the package config', function (): void {
    expect(config('au.abr.base_url'))->toBe('https://abr.business.gov.au/json')
        ->and(config('au.abr.cache.ttl'))->toBe(86400)
        ->and(config('au.phone.store_as'))->toBe('e164');
});
