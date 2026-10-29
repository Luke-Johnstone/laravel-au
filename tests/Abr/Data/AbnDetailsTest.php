<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;

mutates(AbnDetails::class);

dataset('optional text fields', [
    'entity name' => ['EntityName', 'entityName', 'Example Holdings Pty Ltd'],
    'entity type code' => ['EntityTypeCode', 'entityTypeCode', 'PRV'],
    'entity type name' => ['EntityTypeName', 'entityTypeName', 'Australian Private Company'],
]);

dataset('date fields', [
    'ABN status effective from' => ['AbnStatusEffectiveFrom', 'statusEffectiveFrom'],
    'GST registered from' => ['Gst', 'gstRegisteredFrom'],
]);

dataset('parseable dates', [
    'Y-m-d' => '2000-07-01',
    'ISO 8601 without an offset' => '2000-07-01T00:00:00',
    'ISO 8601 with an offset' => '2000-07-01T00:00:00+10:00',
    'ISO 8601 with fractional seconds' => '2000-07-01T00:00:00.000',
    'ISO 8601 with fractional seconds and an offset' => '2000-07-01T00:00:00.1234567+10:00',
    'Y-m-d with surrounding spaces' => ' 2000-07-01 ',
]);

dataset('unusable dates', [
    'an empty string' => '',
    'null' => null,
    'text' => 'not a date',
    'an impossible day' => '2020-02-31',
    'an impossible month' => '2020-13-01',
]);

dataset('statuses', [
    'Active' => ['Active', true],
    'lower case' => ['active', true],
    'upper case' => ['ACTIVE', true],
    'Cancelled' => ['Cancelled', false],
]);

dataset('payloads without a usable ABN', [
    'no Abn field' => [['AbnStatus' => 'Active']],
    'an empty Abn' => [['Abn' => '', 'AbnStatus' => 'Active']],
    'an Abn that fails the checksum' => [['Abn' => '51824753557', 'AbnStatus' => 'Active']],
    'an Abn that is too short' => [['Abn' => '5182475355', 'AbnStatus' => 'Active']],
    'an Abn with a letter' => [['Abn' => '5182475355a', 'AbnStatus' => 'Active']],
]);

describe('fromPayload()', function (): void {
    it('maps every field of an active ABN', function (): void {
        $details = AbnDetails::fromPayload(abrPayload('abn-active.jsonp'));

        expect($details->abn)->toBeInstanceOf(Abn::class)
            ->and($details->abn->value)->toBe('51824753556')
            ->and($details->acn)->toBeNull()
            ->and($details->status)->toBe('Active')
            ->and($details->statusEffectiveFrom)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($details->statusEffectiveFrom?->format('Y-m-d'))->toBe('2000-07-01')
            ->and($details->entityName)->toBe('Example Holdings Pty Ltd')
            ->and($details->entityTypeCode)->toBe('PRV')
            ->and($details->entityTypeName)->toBe('Australian Private Company')
            ->and($details->businessNames)->toBe(['Example Trading Co', 'Example Services'])
            ->and($details->gstRegisteredFrom)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($details->gstRegisteredFrom?->format('Y-m-d'))->toBe('2000-07-01')
            ->and($details->postcode)->toBeInstanceOf(Postcode::class)
            ->and($details->postcode?->value)->toBe('2600')
            ->and($details->state)->toBe(State::ACT);
    });

    it('maps a cancelled ABN', function (): void {
        $details = AbnDetails::fromPayload(abrPayload('abn-cancelled.jsonp'));

        expect($details->abn->value)->toBe('83914571673')
            ->and($details->status)->toBe('Cancelled')
            ->and($details->statusEffectiveFrom?->format('Y-m-d'))->toBe('2015-06-30')
            ->and($details->entityName)->toBe('Example Cancelled Pty Ltd')
            ->and($details->businessNames)->toBe([])
            ->and($details->gstRegisteredFrom)->toBeNull()
            ->and($details->postcode?->value)->toBe('3000')
            ->and($details->state)->toBe(State::VIC);
    });

    it('maps the ACN of a company', function (): void {
        $details = AbnDetails::fromPayload(abrPayload('acn-active.jsonp'));

        expect($details->abn->value)->toBe('53004085616')
            ->and($details->acn)->toBeInstanceOf(Acn::class)
            ->and($details->acn?->value)->toBe('004085616')
            ->and($details->entityName)->toBe('Example Company Pty Ltd')
            ->and($details->gstRegisteredFrom?->format('Y-m-d'))->toBe('2001-01-01')
            ->and($details->postcode?->value)->toBe('2000')
            ->and($details->state)->toBe(State::NSW);
    });

    it('maps a suppressed ABN without error', function (): void {
        $details = AbnDetails::fromPayload(abrPayload('abn-suppressed.jsonp'));

        expect($details->abn->value)->toBe('83914571673')
            ->and($details->status)->toBe('Active')
            ->and($details->statusEffectiveFrom?->format('Y-m-d'))->toBe('2012-01-01')
            ->and($details->gstRegisteredFrom?->format('Y-m-d'))->toBe('2012-01-01')
            ->and($details->acn)->toBeNull()
            ->and($details->entityName)->toBeNull()
            ->and($details->entityTypeCode)->toBeNull()
            ->and($details->entityTypeName)->toBeNull()
            ->and($details->businessNames)->toBe([])
            ->and($details->postcode)->toBeNull()
            ->and($details->state)->toBeNull();
    });

    it('builds from a payload holding only the ABN and its status', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload());

        expect($details->abn->value)->toBe('51824753556')
            ->and($details->status)->toBe('Active')
            ->and($details->acn)->toBeNull()
            ->and($details->statusEffectiveFrom)->toBeNull()
            ->and($details->entityName)->toBeNull()
            ->and($details->entityTypeCode)->toBeNull()
            ->and($details->entityTypeName)->toBeNull()
            ->and($details->businessNames)->toBe([])
            ->and($details->gstRegisteredFrom)->toBeNull()
            ->and($details->postcode)->toBeNull()
            ->and($details->state)->toBeNull();
    });
});

describe('empty values', function (): void {
    it('maps an empty text field to null', function (string $field, string $property): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload([$field => '']))->{$property})->toBeNull();
    })->with('optional text fields');

    it('maps a null text field to null', function (string $field, string $property): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload([$field => null]))->{$property})->toBeNull();
    })->with('optional text fields');

    it('keeps a non-empty text field', function (string $field, string $property, string $value): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload([$field => $value]))->{$property})->toBe($value);
    })->with('optional text fields');

    it('maps an empty or null ACN to null', function (?string $acn): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload(['Acn' => $acn]))->acn)->toBeNull();
    })->with([
        'an empty string' => '',
        'null' => null,
    ]);
});

describe('dates', function (): void {
    it('parses Y-m-d and ISO 8601 dates', function (string $field, string $property, string $date): void {
        $parsed = AbnDetails::fromPayload(minimalAbnPayload([$field => $date]))->{$property};

        expect($parsed)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($parsed?->format('Y-m-d'))->toBe('2000-07-01');
    })->with('date fields')->with('parseable dates');

    it('parses a date without a time as midnight', function (string $field, string $property): void {
        $parsed = AbnDetails::fromPayload(minimalAbnPayload([$field => '2000-07-01']))->{$property};

        expect($parsed?->format('Y-m-d H:i:s'))->toBe('2000-07-01 00:00:00');
    })->with('date fields');

    it('maps an empty, unparseable or impossible date to null', function (string $field, string $property, ?string $date): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload([$field => $date]))->{$property})->toBeNull();
    })->with('date fields')->with('unusable dates');
});

describe('business names', function (): void {
    it('keeps the names in order', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload([
            'BusinessName' => ['Example Services', 'Example Trading Co'],
        ]));

        expect($details->businessNames)->toBe(['Example Services', 'Example Trading Co']);
    });

    it('skips blank and non-string entries and returns a list', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload([
            'BusinessName' => ['', 'Example Trading Co', '   ', 42, null, ['Example Nested'], 'Example Services'],
        ]));

        expect($details->businessNames)->toBe(['Example Trading Co', 'Example Services']);
    });

    it('maps a missing or null BusinessName to an empty list', function (array $payload): void {
        expect(AbnDetails::fromPayload($payload)->businessNames)->toBe([]);
    })->with([
        'missing' => fn (): array => minimalAbnPayload(),
        'null' => fn (): array => minimalAbnPayload(['BusinessName' => null]),
        'empty' => fn (): array => minimalAbnPayload(['BusinessName' => []]),
    ]);
});

describe('address', function (): void {
    it('maps the state code', function (string $code, State $state): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload(['AddressState' => $code]))->state)->toBe($state);
    })->with([
        'NSW' => ['NSW', State::NSW],
        'VIC' => ['VIC', State::VIC],
        'QLD' => ['QLD', State::QLD],
        'SA' => ['SA', State::SA],
        'WA' => ['WA', State::WA],
        'TAS' => ['TAS', State::TAS],
        'NT' => ['NT', State::NT],
        'ACT' => ['ACT', State::ACT],
    ]);

    it('maps an empty or unknown state to null', function (?string $state): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload(['AddressState' => $state]))->state)->toBeNull();
    })->with([
        'an empty string' => '',
        'null' => null,
        'an unknown code' => 'XYZ',
        'another country' => 'NZ',
    ]);

    it('maps a valid postcode', function (): void {
        $postcode = AbnDetails::fromPayload(minimalAbnPayload(['AddressPostcode' => '0800']))->postcode;

        expect($postcode)->toBeInstanceOf(Postcode::class)
            ->and($postcode?->value)->toBe('0800');
    });

    it('maps an empty or invalid postcode to null', function (?string $postcode): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload(['AddressPostcode' => $postcode]))->postcode)->toBeNull();
    })->with([
        'an empty string' => '',
        'null' => null,
        'too short' => '260',
        'too long' => '26000',
        'letters' => 'ABCD',
        'in no state' => '0000',
    ]);
});

describe('invalid payloads', function (): void {
    it('throws an ABR exception when there is no valid ABN', function (array $payload): void {
        expect(fn () => AbnDetails::fromPayload($payload))->toThrow(AbrException::class);
    })->with('payloads without a usable ABN');

    it('throws an ABR exception when the ABN status is missing', function (): void {
        expect(fn () => AbnDetails::fromPayload(['Abn' => '51824753556']))->toThrow(AbrException::class);
    });

    it('throws an ABR exception for a not-found or error response', function (string $fixture): void {
        expect(fn () => AbnDetails::fromPayload(abrPayload($fixture)))->toThrow(AbrException::class);
    })->with([
        'ABN not found' => 'abn-not-found.jsonp',
        'service error' => 'error.jsonp',
    ]);

    it('does not reveal the ABN in the exception message', function (): void {
        expect(fn () => AbnDetails::fromPayload(['Abn' => '51824753557', 'AbnStatus' => 'Active']))
            ->toThrow(function (AbrException $exception): void {
                expect($exception->getMessage())->not->toContain('51824753557');
            });
    });
});

describe('isActive()', function (): void {
    it('is active for an active ABN', function (): void {
        expect(AbnDetails::fromPayload(abrPayload('abn-active.jsonp'))->isActive())->toBeTrue();
    });

    it('is not active for a cancelled ABN', function (): void {
        expect(AbnDetails::fromPayload(abrPayload('abn-cancelled.jsonp'))->isActive())->toBeFalse();
    });

    it('is active for a suppressed ABN with an active status', function (): void {
        expect(AbnDetails::fromPayload(abrPayload('abn-suppressed.jsonp'))->isActive())->toBeTrue();
    });

    it('compares the status case-insensitively', function (string $status, bool $active): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload(['AbnStatus' => $status]))->isActive())->toBe($active);
    })->with('statuses');
});

describe('isGstRegistered()', function (): void {
    it('reports GST registration from the fixtures', function (string $fixture, bool $registered): void {
        expect(AbnDetails::fromPayload(abrPayload($fixture))->isGstRegistered())->toBe($registered);
    })->with([
        'active ABN' => ['abn-active.jsonp', true],
        'cancelled ABN with no GST date' => ['abn-cancelled.jsonp', false],
        'suppressed ABN' => ['abn-suppressed.jsonp', true],
        'active ACN' => ['acn-active.jsonp', true],
    ]);

    it('is not registered when the GST date is empty or unparseable', function (?string $gst): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload(['Gst' => $gst]))->isGstRegistered())->toBeFalse();
    })->with([
        'an empty string' => '',
        'null' => null,
        'text' => 'not a date',
    ]);
});

describe('displayName()', function (): void {
    it('prefers the first business name', function (): void {
        expect(AbnDetails::fromPayload(abrPayload('abn-active.jsonp'))->displayName())->toBe('Example Trading Co');
    });

    it('falls back to the entity name when there are no business names', function (): void {
        expect(AbnDetails::fromPayload(abrPayload('abn-cancelled.jsonp'))->displayName())->toBe('Example Cancelled Pty Ltd');
    });

    it('falls back to the entity name when every business name is blank', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload([
            'EntityName' => 'Example Holdings Pty Ltd',
            'BusinessName' => ['', '   '],
        ]));

        expect($details->displayName())->toBe('Example Holdings Pty Ltd');
    });

    it('is null for a suppressed ABN', function (): void {
        expect(AbnDetails::fromPayload(abrPayload('abn-suppressed.jsonp'))->displayName())->toBeNull();
    });
});

describe('isSuppressed()', function (): void {
    it('is suppressed when there is no entity name and no business name', function (): void {
        expect(AbnDetails::fromPayload(abrPayload('abn-suppressed.jsonp'))->isSuppressed())->toBeTrue();
    });

    it('is not suppressed when the details are public', function (string $fixture): void {
        expect(AbnDetails::fromPayload(abrPayload($fixture))->isSuppressed())->toBeFalse();
    })->with([
        'active ABN' => 'abn-active.jsonp',
        'cancelled ABN' => 'abn-cancelled.jsonp',
        'active ACN' => 'acn-active.jsonp',
    ]);

    it('is not suppressed when only a business name is present', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload(['BusinessName' => ['Example Trading Co']]));

        expect($details->isSuppressed())->toBeFalse()
            ->and($details->displayName())->toBe('Example Trading Co');
    });
});

describe('toArray()', function (): void {
    it('converts an active ABN to plain values', function (): void {
        expect(AbnDetails::fromPayload(abrPayload('abn-active.jsonp'))->toArray())->toBe([
            'abn' => '51824753556',
            'acn' => null,
            'status' => 'Active',
            'statusEffectiveFrom' => '2000-07-01',
            'entityName' => 'Example Holdings Pty Ltd',
            'entityTypeCode' => 'PRV',
            'entityTypeName' => 'Australian Private Company',
            'businessNames' => ['Example Trading Co', 'Example Services'],
            'gstRegisteredFrom' => '2000-07-01',
            'postcode' => '2600',
            'state' => 'ACT',
        ]);
    });

    it('converts the ACN to its digits', function (): void {
        $array = AbnDetails::fromPayload(abrPayload('acn-active.jsonp'))->toArray();

        expect($array['abn'])->toBe('53004085616')
            ->and($array['acn'])->toBe('004085616')
            ->and($array['state'])->toBe('NSW')
            ->and($array['postcode'])->toBe('2000');
    });

    it('converts a suppressed ABN to plain values', function (): void {
        expect(AbnDetails::fromPayload(abrPayload('abn-suppressed.jsonp'))->toArray())->toBe([
            'abn' => '83914571673',
            'acn' => null,
            'status' => 'Active',
            'statusEffectiveFrom' => '2012-01-01',
            'entityName' => null,
            'entityTypeCode' => null,
            'entityTypeName' => null,
            'businessNames' => [],
            'gstRegisteredFrom' => '2012-01-01',
            'postcode' => null,
            'state' => null,
        ]);
    });

    it('writes ISO 8601 dates as Y-m-d', function (): void {
        $array = AbnDetails::fromPayload(minimalAbnPayload([
            'AbnStatusEffectiveFrom' => '2000-07-01T00:00:00+10:00',
            'Gst' => '2000-07-01T00:00:00',
        ]))->toArray();

        expect($array['statusEffectiveFrom'])->toBe('2000-07-01')
            ->and($array['gstRegisteredFrom'])->toBe('2000-07-01');
    });
});

describe('surrounding whitespace', function (): void {
    it('trims text fields', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload([
            'AbnStatus' => ' Active ',
            'EntityName' => '  Example Holdings Pty Ltd ',
        ]));

        expect($details->status)->toBe('Active')
            ->and($details->isActive())->toBeTrue()
            ->and($details->entityName)->toBe('Example Holdings Pty Ltd');
    });
});
