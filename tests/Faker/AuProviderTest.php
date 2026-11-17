<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Faker\AuProvider;
use EastpointLabs\LaravelAu\LaravelAuServiceProvider;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Bsb;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;
use Faker\Generator;

mutates(AuProvider::class, LaravelAuServiceProvider::class);

dataset('states with their area codes', [
    'NSW' => [State::NSW, '02'],
    'VIC' => [State::VIC, '03'],
    'QLD' => [State::QLD, '07'],
    'SA' => [State::SA, '08'],
    'WA' => [State::WA, '08'],
    'TAS' => [State::TAS, '03'],
    'NT' => [State::NT, '08'],
    'ACT' => [State::ACT, '02'],
]);

describe('registration', function (): void {
    it('adds the provider once to fake() and to the generator model factories use', function (): void {
        $auProviders = fn (Generator $faker): array => array_filter(
            $faker->getProviders(),
            fn (object $provider): bool => $provider instanceof AuProvider,
        );

        fake();
        fake();
        app(Generator::class);

        // Model factories keep one generator for the whole process, shared by every application
        // (each test has its own), and resolving it with a locale builds it again. Either way the
        // generator that comes back already has the provider, which mustn't be added twice.
        app()->make(Generator::class, ['locale' => 'en_US']);

        expect($auProviders(fake()))->toHaveCount(1)
            ->and($auProviders(app(Generator::class)))->toHaveCount(1);
    });

    it('repeats its values when Faker is seeded again with the same seed', function (): void {
        $generate = fn (): array => array_map(fn (): array => [
            fake()->abn(),
            fake()->invalidAbn(),
            fake()->acn(),
            fake()->bsb(),
            fake()->auMobileNumber(),
            fake()->auLandlineNumber(),
            fake()->auPostcode(),
            fake()->auState(),
        ], range(1, 10));

        fake()->seed(1234);
        $first = $generate();

        fake()->seed(1234);
        $second = $generate();

        // Back to an unpredictable seed, so later tests don't all see the same values.
        fake()->seed();

        expect($second)->toBe($first);
    });
});

describe('identifiers', function (): void {
    it('generates ABNs that pass the checksum', function (): void {
        $abns = array_map(fn (): string => fake()->abn(), range(1, 200));

        expect($abns)->each->toMatch('/\A[1-9]\d{10}\z/')
            ->and(array_filter($abns, Abn::isValid(...)))->toBe($abns)
            ->and(count(array_unique($abns)))->toBeGreaterThan(1);
    });

    it('starts some ABNs with 99, which passes the checksum whenever 10 does', function (): void {
        // Each lead starts about 1 ABN in 175, so 5,000 ABNs all but guarantee both appear.
        $leads = array_map(fn (): string => substr(fake()->abn(), 0, 2), range(1, 5000));

        expect($leads)->toContain('10', '99');
    });

    it('generates invalid ABNs that are a valid ABN with a different last digit', function (): void {
        $abns = array_map(fn (): string => fake()->invalidAbn(), range(1, 200));

        $lastDigitFixes = fn (string $abn): bool => array_filter(
            range(0, 9),
            fn (int $digit): bool => Abn::isValid(substr($abn, 0, 10).$digit),
        ) !== [];

        expect($abns)->each->toMatch('/\A\d{11}\z/')
            ->and(array_filter($abns, Abn::isValid(...)))->toBeEmpty()
            ->and(array_filter($abns, $lastDigitFixes))->toBe($abns)
            ->and(count(array_unique($abns)))->toBeGreaterThan(1);
    });

    it('generates ACNs with a valid check digit', function (): void {
        $acns = array_map(fn (): string => fake()->acn(), range(1, 200));

        expect($acns)->each->toMatch('/\A\d{9}\z/')
            ->and(array_filter($acns, Acn::isValid(...)))->toBe($acns)
            ->and(count(array_unique($acns)))->toBeGreaterThan(1);
    });

    it('generates six-digit BSBs', function (): void {
        $bsbs = array_map(fn (): string => fake()->bsb(), range(1, 200));

        expect($bsbs)->each->toMatch('/\A\d{6}\z/')
            ->and(array_filter($bsbs, Bsb::isValid(...)))->toBe($bsbs)
            ->and(count(array_unique($bsbs)))->toBeGreaterThan(1);
    });
});

describe('phone numbers and places', function (): void {
    it('generates only the mobile numbers ACMA reserves for fiction', function (): void {
        $mobiles = array_map(fn (): string => fake()->auMobileNumber(), range(1, 200));

        expect(array_values(array_unique($mobiles)))->toEqualCanonicalizing([
            '0491570006',
            '0491570156',
            '0491570157',
            '0491570158',
            '0491570159',
            '0491570110',
        ]);
    });

    it('generates landlines with the state\'s area code and postcodes in its ranges', function (State $state, string $areaCode): void {
        $landlines = array_map(fn (): string => fake()->auLandlineNumber($state), range(1, 200));
        $postcodes = array_map(fn (): string => fake()->auPostcode($state), range(1, 200));

        expect($landlines)->each->toMatch('/\A'.$areaCode.'5550\d{4}\z/')
            ->and(array_filter($landlines, fn (string $landline): bool => PhoneNumber::tryFrom($landline)?->areaCode() === $areaCode))->toBe($landlines)
            ->and(count(array_unique($landlines)))->toBeGreaterThan(1)
            ->and($postcodes)->each->toMatch('/\A\d{4}\z/')
            ->and(array_filter($postcodes, fn (string $postcode): bool => State::fromPostcode($postcode) === $state))->toBe($postcodes)
            ->and(count(array_unique($postcodes)))->toBeGreaterThan(1);
    })->with('states with their area codes');

    it('generates landlines and postcodes for any state when none is given', function (): void {
        $landlines = array_map(fn (): string => fake()->auLandlineNumber(), range(1, 200));
        $postcodes = array_map(fn (): string => fake()->auPostcode(), range(1, 200));

        $areaCodes = array_map(fn (string $landline): ?string => PhoneNumber::tryFrom($landline)?->areaCode(), $landlines);
        $states = array_map(fn (string $postcode): ?string => State::fromPostcode($postcode)?->value, $postcodes);

        expect($landlines)->each->toMatch('/\A0[2378]5550\d{4}\z/')
            ->and(array_values(array_unique($areaCodes)))->toEqualCanonicalizing(['02', '03', '07', '08'])
            ->and($postcodes)->each->toMatch('/\A\d{4}\z/')
            ->and(array_filter($postcodes, Postcode::isValid(...)))->toBe($postcodes)
            ->and($states)->not->toContain(null)
            ->and(count(array_unique($states)))->toBeGreaterThan(1);
    });

    it('generates every state, and only states', function (): void {
        $states = array_map(fn (): State => fake()->auState(), range(1, 200));

        expect($states)->each->toBeInstanceOf(State::class)
            ->and(array_values(array_unique(array_column($states, 'value'))))
            ->toEqualCanonicalizing(array_column(State::cases(), 'value'));
    });
});
