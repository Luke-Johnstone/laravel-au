<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;

mutates(AbnDetails::class);

describe('fromPayload()', function (): void {
    it('maps an active ABN', function (): void {
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
            ->and($details->state)->toBe(State::ACT)
            ->and($details->isActive())->toBeTrue()
            ->and($details->isGstRegistered())->toBeTrue()
            ->and($details->isSuppressed())->toBeFalse()
            ->and($details->displayName())->toBe('Example Trading Co')
            ->and($details->toArray())->toBe([
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

    it('maps a cancelled ABN', function (): void {
        $details = AbnDetails::fromPayload(abrPayload('abn-cancelled.jsonp'));

        expect($details->abn->value)->toBe('83914571673')
            ->and($details->status)->toBe('Cancelled')
            ->and($details->statusEffectiveFrom?->format('Y-m-d'))->toBe('2015-06-30')
            ->and($details->entityName)->toBe('Example Cancelled Pty Ltd')
            ->and($details->businessNames)->toBe([])
            ->and($details->gstRegisteredFrom)->toBeNull()
            ->and($details->postcode?->value)->toBe('3000')
            ->and($details->state)->toBe(State::VIC)
            ->and($details->isActive())->toBeFalse()
            ->and($details->isGstRegistered())->toBeFalse()
            ->and($details->isSuppressed())->toBeFalse()
            ->and($details->displayName())->toBe('Example Cancelled Pty Ltd');
    });

    it('maps the ACN of a company', function (): void {
        $details = AbnDetails::fromPayload(abrPayload('acn-active.jsonp'));

        expect($details->abn->value)->toBe('53004085616')
            ->and($details->acn)->toBeInstanceOf(Acn::class)
            ->and($details->acn?->value)->toBe('004085616')
            ->and($details->entityName)->toBe('Example Company Pty Ltd')
            ->and($details->gstRegisteredFrom?->format('Y-m-d'))->toBe('2001-01-01')
            ->and($details->postcode?->value)->toBe('2000')
            ->and($details->state)->toBe(State::NSW)
            ->and($details->isGstRegistered())->toBeTrue()
            ->and($details->isSuppressed())->toBeFalse()
            ->and($details->toArray())->toMatchArray([
                'abn' => '53004085616',
                'acn' => '004085616',
                'postcode' => '2000',
                'state' => 'NSW',
            ]);
    });

    it('maps a suppressed ABN, which has no names or address', function (): void {
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
            ->and($details->state)->toBeNull()
            ->and($details->isActive())->toBeTrue()
            ->and($details->isGstRegistered())->toBeTrue()
            ->and($details->isSuppressed())->toBeTrue()
            ->and($details->displayName())->toBeNull()
            ->and($details->toArray())->toBe([
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

    it('throws a malformed response exception, without the ABN, when there is no valid ABN or status', function (array $payload): void {
        expect(fn () => AbnDetails::fromPayload($payload))->toThrow(function (AbrException $exception): void {
            expect($exception->getMessage())
                ->toBe('ABN Lookup returned a response that could not be read.')
                ->not->toMatch('/\d/');
        });
    })->with([
        'no Abn field' => [['AbnStatus' => 'Active']],
        'an empty Abn' => [['Abn' => '', 'AbnStatus' => 'Active']],
        'an Abn that fails the checksum' => [['Abn' => '51824753557', 'AbnStatus' => 'Active']],
        'no AbnStatus field' => [['Abn' => '51824753556']],
        'a not-found response' => fn (): array => abrPayload('abn-not-found.jsonp'),
        'a service error response' => fn (): array => abrPayload('error.jsonp'),
    ]);
});

describe('status and text fields', function (): void {
    it('keeps a text field, and maps an empty or null one to null', function (string $field, string $property, string $value): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload([$field => $value]))->{$property})->toBe($value)
            ->and(AbnDetails::fromPayload(minimalAbnPayload([$field => '']))->{$property})->toBeNull()
            ->and(AbnDetails::fromPayload(minimalAbnPayload([$field => null]))->{$property})->toBeNull();
    })->with([
        'entity name' => ['EntityName', 'entityName', 'Example Holdings Pty Ltd'],
        'entity type code' => ['EntityTypeCode', 'entityTypeCode', 'PRV'],
        'entity type name' => ['EntityTypeName', 'entityTypeName', 'Australian Private Company'],
    ]);

    it('trims text fields', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload([
            'AbnStatus' => ' Active ',
            'EntityName' => '  Example Holdings Pty Ltd ',
        ]));

        expect($details->status)->toBe('Active')
            ->and($details->isActive())->toBeTrue()
            ->and($details->entityName)->toBe('Example Holdings Pty Ltd');
    });

    it('compares the status case-insensitively', function (): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload(['AbnStatus' => 'active']))->isActive())->toBeTrue()
            ->and(AbnDetails::fromPayload(minimalAbnPayload(['AbnStatus' => 'ACTIVE']))->isActive())->toBeTrue();
    });
});

describe('dates', function (): void {
    it('parses Y-m-d and ISO 8601 dates, reading a date without a time as midnight', function (string $date): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload(['AbnStatusEffectiveFrom' => $date, 'Gst' => $date]));

        expect($details->statusEffectiveFrom?->format('Y-m-d H:i:s'))->toBe('2000-07-01 00:00:00')
            ->and($details->gstRegisteredFrom?->format('Y-m-d H:i:s'))->toBe('2000-07-01 00:00:00')
            ->and($details->toArray())->toMatchArray([
                'statusEffectiveFrom' => '2000-07-01',
                'gstRegisteredFrom' => '2000-07-01',
            ]);
    })->with([
        'Y-m-d' => '2000-07-01',
        'ISO 8601 without an offset' => '2000-07-01T00:00:00',
        'ISO 8601 with an offset' => '2000-07-01T00:00:00+10:00',
        'ISO 8601 with fractional seconds' => '2000-07-01T00:00:00.000',
        'ISO 8601 with seven fractional digits and an offset' => '2000-07-01T00:00:00.1234567+10:00',
        'Y-m-d with surrounding spaces' => ' 2000-07-01 ',
    ]);

    it('maps an empty, unparseable or impossible date to null', function (?string $date): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload(['AbnStatusEffectiveFrom' => $date, 'Gst' => $date]));

        expect($details->statusEffectiveFrom)->toBeNull()
            ->and($details->gstRegisteredFrom)->toBeNull()
            ->and($details->isGstRegistered())->toBeFalse();
    })->with([
        'an empty string' => '',
        'null' => null,
        'text' => 'not a date',
        'an impossible day' => '2020-02-31',
        'an impossible month' => '2020-13-01',
    ]);
});

describe('business names', function (): void {
    it('keeps the names in order, skipping blank and non-string entries', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload([
            'BusinessName' => ['', 'Example Services', '   ', 42, null, ['Example Nested'], 'Example Trading Co'],
        ]));

        expect($details->businessNames)->toBe(['Example Services', 'Example Trading Co']);
    });

    it('maps a null or empty BusinessName to an empty list', function (): void {
        expect(AbnDetails::fromPayload(minimalAbnPayload(['BusinessName' => null]))->businessNames)->toBe([])
            ->and(AbnDetails::fromPayload(minimalAbnPayload(['BusinessName' => []]))->businessNames)->toBe([]);
    });

    it('falls back to the entity name when every business name is blank', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload([
            'EntityName' => 'Example Holdings Pty Ltd',
            'BusinessName' => ['', '   '],
        ]));

        expect($details->displayName())->toBe('Example Holdings Pty Ltd');
    });

    it('is not suppressed when only a business name is present', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload(['BusinessName' => ['Example Trading Co']]));

        expect($details->isSuppressed())->toBeFalse()
            ->and($details->displayName())->toBe('Example Trading Co');
    });
});

describe('address', function (): void {
    it('maps a postcode with a leading zero, and its state', function (): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload(['AddressPostcode' => '0800', 'AddressState' => 'NT']));

        expect($details->postcode?->value)->toBe('0800')
            ->and($details->state)->toBe(State::NT);
    });

    it('maps an empty or unrecognised state and postcode to null', function (?string $state, ?string $postcode): void {
        $details = AbnDetails::fromPayload(minimalAbnPayload(['AddressState' => $state, 'AddressPostcode' => $postcode]));

        expect($details->state)->toBeNull()
            ->and($details->postcode)->toBeNull();
    })->with([
        'empty strings' => ['', ''],
        'nulls' => [null, null],
        'an unknown code and a postcode in no state' => ['XYZ', '0000'],
        'another country and a three-digit postcode' => ['NZ', '260'],
    ]);
});
