<?php

declare(strict_types=1);

arch('source files declare strict types')
    ->expect('EastpointLabs\LaravelAu')
    ->toUseStrictTypes();

arch('no debugging calls are left in the source')
    ->expect(['dd', 'ddd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('the core does not depend on Filament')
    ->expect('EastpointLabs\LaravelAu')
    ->not->toUse('Filament')
    ->ignoring('EastpointLabs\LaravelAu\Filament');

// Allowlists rather than ->not->toUse('Illuminate'): they're stricter, and checking against a
// blocklist means parsing all of Laravel, which takes most of PHP's default memory limit.
arch('support classes use only plain PHP and the package\'s exceptions')
    ->expect('EastpointLabs\LaravelAu\Support')
    ->toOnlyUse(['EastpointLabs\LaravelAu\Support', 'EastpointLabs\LaravelAu\Exceptions']);

// Laravel is narrowed to what a cast needs, including the config() helper, which counts as a
// use. Allowing all of 'Illuminate' would mean parsing all of Laravel, as a blocklist would.
arch('casts use only Laravel, the value objects and the package\'s exceptions')
    ->expect('EastpointLabs\LaravelAu\Casts')
    ->toOnlyUse([
        'Illuminate\Contracts',
        'Illuminate\Database\Eloquent\Model',
        'config',
        'EastpointLabs\LaravelAu\Support',
        'EastpointLabs\LaravelAu\Exceptions',
    ]);

// Each rule needs its own classes() filter: it skips enums, and chaining a
// second expectation onto the same arch() call would drop the filter.
arch('support classes are final')
    ->expect('EastpointLabs\LaravelAu\Support')
    ->classes()
    ->toBeFinal();

arch('support classes are readonly')
    ->expect('EastpointLabs\LaravelAu\Support')
    ->classes()
    ->toBeReadonly();

arch('ABN Lookup data objects use only plain PHP and the package\'s value objects')
    ->expect('EastpointLabs\LaravelAu\Abr\Data')
    ->toOnlyUse([
        'EastpointLabs\LaravelAu\Abr\Data',
        'EastpointLabs\LaravelAu\Support',
        'EastpointLabs\LaravelAu\Exceptions',
    ]);

arch('ABN Lookup data objects are final')
    ->expect('EastpointLabs\LaravelAu\Abr\Data')
    ->classes()
    ->toBeFinal();

arch('ABN Lookup data objects are readonly')
    ->expect('EastpointLabs\LaravelAu\Abr\Data')
    ->classes()
    ->toBeReadonly();

// Faker's entry points rather than the whole 'Faker' namespace. Checking against all of Faker
// means parsing its hundreds of locale providers, about 20 MB more at peak, and loading its
// Doctrine shim, which fails without Doctrine installed.
arch('only the Faker provider and the service provider use Faker')
    ->expect('EastpointLabs\LaravelAu')
    ->not->toUse(['Faker\Factory', 'Faker\Generator', 'Faker\Provider\Base', 'Faker\Provider\en_AU'])
    ->ignoring(['EastpointLabs\LaravelAu\Faker', 'EastpointLabs\LaravelAu\LaravelAuServiceProvider']);
