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

arch('support classes are plain PHP with no Laravel dependency')
    ->expect('EastpointLabs\LaravelAu\Support')
    ->not->toUse(['Illuminate', 'Filament']);

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
