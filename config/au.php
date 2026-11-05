<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | ABN Lookup (Australian Business Register)
    |--------------------------------------------------------------------------
    |
    | ABN Lookup web services need a free authentication GUID, which you can
    | request at https://abr.business.gov.au/Tools/WebServices. Keep it in
    | your .env file. It is only needed when you call the ABR client; the
    | validation rules and formatters work without it.
    |
    */

    'abr' => [
        'guid' => env('ABR_GUID'),

        'base_url' => env('ABR_BASE_URL', 'https://abr.business.gov.au/json'),

        // Request timeout in seconds.
        'timeout' => (int) env('ABR_TIMEOUT', 10),

        'cache' => [
            'enabled' => (bool) env('ABR_CACHE_ENABLED', true),

            // Cache store to use. Null uses your application's default store.
            'store' => env('ABR_CACHE_STORE'),

            // How long to cache successful lookups, in seconds.
            'ttl' => (int) env('ABR_CACHE_TTL', 60 * 60 * 24),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone numbers
    |--------------------------------------------------------------------------
    |
    | How the Filament phone field and the AsPhoneNumber cast store numbers:
    | "e164" (+61491570006) or "national" (0491570006). Service numbers such
    | as 1300 975 707 have no E.164 form, so they're always stored as
    | national digits.
    |
    */

    'phone' => [
        'store_as' => 'e164',
    ],

];
