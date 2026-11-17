<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Abr\Data\NameMatch;
use EastpointLabs\LaravelAu\Abr\Testing\AbrFake;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use PHPUnit\Framework\ExpectationFailedException;

mutates(AbrFake::class);

describe('configured ABNs', function (): void {
    it('builds details from a payload merged over an active record for the ABN', function (string $key, Abn|string $lookUp): void {
        $fake = new AbrFake([$key => ['EntityName' => 'Example Holdings Pty Ltd']]);

        $details = $fake->abn($lookUp);

        expect($details)->toBeInstanceOf(AbnDetails::class)
            ->and($details?->abn->value)->toBe('51824753556')
            ->and($details?->entityName)->toBe('Example Holdings Pty Ltd')
            ->and($details?->status)->toBe('Active')
            ->and($details?->isActive())->toBeTrue()
            ->and($details?->toArray())
            ->toBe(AbnDetails::fromPayload(minimalAbnPayload(['EntityName' => 'Example Holdings Pty Ltd']))->toArray());
    })->with([
        // PHP stores a numeric string key such as '51824753556' as an int.
        'keyed by digits, looked up formatted' => ['51824753556', '51 824 753 556'],
        'keyed formatted, looked up as an Abn' => ['51 824 753 556', Abn::from('51824753556')],
    ]);

    it('lets payload fields replace the defaults', function (): void {
        $details = (new AbrFake([
            '51824753556' => ['Abn' => '51 824 753 556', 'AbnStatus' => 'Cancelled', 'EntityName' => 'Example Holdings Pty Ltd'],
        ]))->abn('51824753556');

        expect($details?->abn->value)->toBe('51824753556')
            ->and($details?->status)->toBe('Cancelled')
            ->and($details?->isActive())->toBeFalse();
    });

    it('returns AbnDetails values as given', function (): void {
        $details = AbnDetails::fromPayload(abrPayload('abn-active.jsonp'));

        expect((new AbrFake(['51824753556' => $details]))->abn('51 824 753 556'))->toBe($details);
    });

    it('rejects details keyed by another ABN', function (AbnDetails|array $details): void {
        expect(fn () => new AbrFake(['51824753556' => $details]))
            ->toThrow(new InvalidArgumentException('Faked ABN details must be keyed by their own ABN.'));
    })->with([
        'AbnDetails' => fn (): AbnDetails => AbnDetails::fromPayload(abrPayload('acn-active.jsonp')),
        'a payload with another ABN' => [['Abn' => '53004085616', 'EntityName' => 'Example Company Pty Ltd']],
    ]);

    it('rejects details without a valid ABN or a status', function (Closure $setUp): void {
        expect($setUp)->toThrow(new InvalidArgumentException('Faked ABN details need a valid ABN and a status.'));
    })->with([
        'an empty status' => fn (): AbrFake => new AbrFake(['51824753556' => ['AbnStatus' => '']]),
        'an invalid ABN field' => fn (): AbrFake => new AbrFake(['51824753556' => ['Abn' => '51824753557']]),
        'suppressed with a blank status' => fn (): AbrFake => (new AbrFake)->suppressed('83914571673', ' '),
    ]);

    it('returns nothing for numbers and names it has not been given', function (): void {
        $fake = new AbrFake(['53004085616' => ['EntityName' => 'Example Company Pty Ltd']]);

        expect($fake->abn('83914571673'))->toBeNull()
            // The ABN ends in this ACN, but its details don't carry it.
            ->and($fake->acn('004085616'))->toBeNull()
            ->and($fake->searchByName('Example Company'))->toBe([]);
    });
});

describe('acn()', function (): void {
    it('finds the configured details that carry the ACN', function (): void {
        $fake = new AbrFake([
            '51824753556' => ['EntityName' => 'Example Holdings Pty Ltd'],
            '53004085616' => ['Acn' => '004085616', 'EntityName' => 'Example Company Pty Ltd'],
        ]);

        $details = $fake->acn('004 085 616');

        expect($details?->abn->value)->toBe('53004085616')
            ->and($details?->acn?->value)->toBe('004085616')
            ->and($details?->entityName)->toBe('Example Company Pty Ltd')
            ->and($fake->acn(Acn::from('004085616')))->toEqual($details);
    });
});

describe('input checks', function (): void {
    it('rejects invalid input before anything else and records no lookup', function (string $method, string $input, Throwable $expected): void {
        $fake = (new AbrFake)->failing()->preventStrayLookups();

        expect(fn () => $fake->{$method}($input))->toThrow($expected);

        $fake->assertNothingLookedUp();
    })->with([
        'an ABN with a bad checksum' => ['abn', '51 824 753 557', InvalidValueException::abn()],
        'an ACN with a bad checksum' => ['acn', '000 000 018', InvalidValueException::acn()],
        'an empty name' => ['searchByName', '', new InvalidArgumentException('The name to search ABN Lookup for must not be empty.')],
        'a whitespace-only name' => ['searchByName', " \t\n ", new InvalidArgumentException('The name to search ABN Lookup for must not be empty.')],
    ]);

    it('rejects an invalid ABN in the setup without giving it away', function (Closure $setUp): void {
        expect($setUp)->toThrow(function (InvalidValueException $exception): void {
            expect($exception->getMessage())->toBe('The given value is not a valid ABN.')
                ->not->toContain('51824753557')
                ->not->toContain('51 824 753 557');
        });
    })->with([
        'a key' => fn (): AbrFake => new AbrFake(['51 824 753 557' => ['EntityName' => 'Example Holdings Pty Ltd']]),
        'notFound()' => fn (): AbrFake => (new AbrFake)->notFound('51 824 753 557'),
        'a name match with a bad checksum' => fn (): AbrFake => (new AbrFake)->withNameMatches([nameSearchEntry(['Abn' => '51824753557'])]),
        'a name match without an ABN' => fn (): AbrFake => (new AbrFake)->withNameMatches([array_diff_key(nameSearchEntry(), ['Abn' => true])]),
    ]);
});

describe('preventStrayLookups()', function (): void {
    it('throws for a number it has not been given, without giving the number away', function (string $method, string $number): void {
        $fake = new AbrFake(['51824753556' => ['EntityName' => 'Example Holdings Pty Ltd']]);

        expect($fake->preventStrayLookups())->toBe($fake)
            ->and(fn () => $fake->{$method}($number))->toThrow(function (RuntimeException $exception) use ($number): void {
                // A plain RuntimeException, so application code that catches AbrException can't swallow it.
                expect($exception::class)->toBe(RuntimeException::class)
                    ->and($exception->getMessage())
                    ->toBe('The ABN Lookup fake has no details for the number looked up, and stray lookups are prevented.')
                    ->not->toContain($number)
                    ->not->toContain(str_replace(' ', '', $number));
            });
    })->with([
        'an ABN' => ['abn', '83 914 571 673'],
        'an ACN' => ['acn', '004 085 616'],
    ]);

    it('still answers for configured numbers and name searches', function (): void {
        $fake = (new AbrFake([
            '53004085616' => ['Acn' => '004085616', 'EntityName' => 'Example Company Pty Ltd'],
        ]))->preventStrayLookups();

        expect($fake->abn('53004085616')?->entityName)->toBe('Example Company Pty Ltd')
            ->and($fake->acn('004085616')?->entityName)->toBe('Example Company Pty Ltd')
            ->and($fake->searchByName('Example Company'))->toBe([]);
    });
});

describe('searchByName()', function (): void {
    it('returns the configured matches for any name', function (): void {
        $match = NameMatch::listFromPayload(['Names' => [nameSearchEntry()]])[0];
        $entry = nameSearchEntry(['Abn' => '83914571673', 'Name' => 'Example Services']);
        $fake = new AbrFake;

        expect($fake->withNameMatches([$match, $entry]))->toBe($fake);

        $matches = $fake->searchByName('Example Holdings');

        expect($matches)->toHaveCount(2)
            ->and($matches[0])->toBe($match)
            ->and($matches[1])->toEqual(NameMatch::listFromPayload(['Names' => [$entry]])[0])
            ->and($fake->searchByName('Something Else Entirely'))->toEqual($matches);
    });

    it('returns at most maxResults matches, clamped like the real client', function (int $configured, ?int $maxResults, int $expected): void {
        $matches = NameMatch::listFromPayload(['Names' => array_map(
            fn (int $position): array => nameSearchEntry(['Name' => "Example Holdings {$position} Pty Ltd"]),
            range(1, $configured),
        )]);
        $fake = (new AbrFake)->withNameMatches($matches);

        $found = $maxResults === null
            ? $fake->searchByName('Example Holdings')
            : $fake->searchByName('Example Holdings', $maxResults);

        expect($found)->toBe(array_slice($matches, 0, $expected));
    })->with([
        // Matches configured, maxResults (null for the default), matches returned.
        'the default of 10' => [11, null, 10],
        'fewer than are configured' => [3, 2, 2],
        'zero, raised to 1' => [3, 0, 1],
        'more than 200, lowered to 200' => [201, 500, 200],
    ]);
});

describe('notFound()', function (): void {
    it('returns null for the ABNs given, even when configured or stray lookups are prevented', function (): void {
        $fake = (new AbrFake(['51824753556' => ['EntityName' => 'Example Holdings Pty Ltd']]))->preventStrayLookups();

        expect($fake->notFound('51 824 753 556', Abn::from('83914571673')))->toBe($fake)
            ->and($fake->abn('51824753556'))->toBeNull()
            ->and($fake->abn('83 914 571 673'))->toBeNull();

        $fake->assertLookupCount(2);
    });

    it('skips the ABNs given when finding an ACN', function (): void {
        $fake = (new AbrFake)->notFound('51824753556');

        expect($fake->acn('004085616'))->toBeNull();
    });
});

describe('suppressed()', function (): void {
    it('returns suppressed details with the given status and GST registration', function (array $arguments, string $status, bool $gst): void {
        $fake = new AbrFake;

        expect($fake->suppressed(...$arguments))->toBe($fake);

        $details = $fake->abn('83914571673');

        expect($details)->toBeInstanceOf(AbnDetails::class)
            ->and($details?->abn->value)->toBe('83914571673')
            ->and($details?->isSuppressed())->toBeTrue()
            ->and($details?->displayName())->toBeNull()
            ->and($details?->status)->toBe($status)
            ->and($details?->isGstRegistered())->toBe($gst);
    })->with([
        // Arguments to suppressed(), expected status, expected GST registration.
        'the defaults' => [['83 914 571 673'], 'Active', false],
        'a cancelled ABN registered for GST' => [[Abn::from('83914571673'), 'Cancelled', true], 'Cancelled', true],
    ]);

    it('lets later setup for an ABN replace earlier setup', function (): void {
        $fake = (new AbrFake(['53004085616' => ['EntityName' => 'Example Company Pty Ltd']]))
            ->suppressed('53004085616')
            ->suppressed('83914571673')
            ->notFound('83914571673')
            ->notFound('51824753556')
            ->suppressed('51824753556');

        expect($fake->abn('53004085616')?->isSuppressed())->toBeTrue()
            ->and($fake->abn('83914571673'))->toBeNull()
            ->and($fake->abn('51824753556')?->isSuppressed())->toBeTrue();
    });
});

describe('failing()', function (): void {
    it('throws a connection failure for every lookup and still records it', function (string $method, string $input): void {
        $fake = (new AbrFake(['53004085616' => ['Acn' => '004085616', 'EntityName' => 'Example Company Pty Ltd']]))
            ->withNameMatches([nameSearchEntry()]);

        expect($fake->failing())->toBe($fake)
            ->and(fn () => $fake->{$method}($input))->toThrow(AbrException::connectionFailed());

        $fake->assertLookupCount(1);
    })->with([
        'abn()' => ['abn', '53004085616'],
        'acn()' => ['acn', '004085616'],
        'searchByName()' => ['searchByName', 'Example Company'],
    ]);
});

describe('assertions', function (): void {
    it('records an abn() call under the ABN in any form', function (Abn|string $lookedUp, Abn|string $asserted): void {
        $fake = new AbrFake;
        $fake->abn($lookedUp);

        $fake->assertLookedUp($asserted);

        expect(fn () => $fake->assertNotLookedUp($asserted))->toThrow(function (ExpectationFailedException $exception): void {
            expect($exception->getMessage())
                ->not->toContain('51824753556')
                ->not->toContain('51 824 753 556');
        });
    })->with([
        'formatted, asserted as digits' => ['51 824 753 556', '51824753556'],
        'digits, asserted as an Abn' => ['51824753556', Abn::from('51824753556')],
    ]);

    it('does not count other calls as looking up the ABN', function (Closure $lookUp): void {
        $fake = new AbrFake(['53004085616' => ['Acn' => '004085616', 'EntityName' => 'Example Company Pty Ltd']]);
        $lookUp($fake);

        $fake->assertNotLookedUp('53004085616');

        expect(fn () => $fake->assertLookedUp(Abn::from('53004085616')))->toThrow(function (ExpectationFailedException $exception): void {
            expect($exception->getMessage())
                ->not->toContain('53004085616')
                ->not->toContain('53 004 085 616');
        });
    })->with([
        'no lookups' => fn (AbrFake $fake): null => null,
        'another ABN' => fn (AbrFake $fake): ?AbnDetails => $fake->abn('83914571673'),
        'the ACN its details carry' => fn (AbrFake $fake): ?AbnDetails => $fake->acn('004085616'),
    ]);

    it('counts each kind of lookup', function (Closure $lookUp): void {
        $fake = new AbrFake;

        $fake->assertNothingLookedUp();
        $fake->assertLookupCount(0);

        $lookUp($fake);

        $fake->assertLookupCount(1);

        expect(fn () => $fake->assertNothingLookedUp())->toThrow(ExpectationFailedException::class)
            ->and(fn () => $fake->assertLookupCount(0))->toThrow(ExpectationFailedException::class);
    })->with([
        'abn()' => fn (AbrFake $fake): ?AbnDetails => $fake->abn('51824753556'),
        'acn()' => fn (AbrFake $fake): ?AbnDetails => $fake->acn('004085616'),
        'searchByName()' => fn (AbrFake $fake): array => $fake->searchByName('Example Holdings'),
    ]);

    it('counts every lookup, including repeats and those that found nothing', function (): void {
        $fake = new AbrFake(['51824753556' => ['EntityName' => 'Example Holdings Pty Ltd']]);

        $fake->abn('51824753556');
        $fake->abn('51 824 753 556');
        $fake->abn('83914571673');
        $fake->acn('004085616');
        $fake->searchByName('Example Holdings');

        $fake->assertLookupCount(5);

        expect(fn () => $fake->assertLookupCount(4))->toThrow(ExpectationFailedException::class)
            ->and(fn () => $fake->assertLookupCount(6))->toThrow(ExpectationFailedException::class);
    });
});
