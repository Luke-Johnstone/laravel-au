<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\Data\NameMatch;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;

mutates(NameMatch::class);

dataset('unusable entries', [
    'an ABN that fails the checksum' => fn (): array => nameSearchEntry(['Abn' => '51824753557']),
    'an ABN that is too short' => fn (): array => nameSearchEntry(['Abn' => '5182475355']),
    'an ABN with a letter' => fn (): array => nameSearchEntry(['Abn' => '5182475355a']),
    'an empty ABN' => fn (): array => nameSearchEntry(['Abn' => '']),
    'a null ABN' => fn (): array => nameSearchEntry(['Abn' => null]),
    'no ABN' => fn (): array => array_diff_key(nameSearchEntry(), ['Abn' => true]),
    'a string instead of an object' => 'Example Holdings Pty Ltd',
    'a number instead of an object' => 42,
    'null instead of an object' => null,
]);

dataset('payloads without names', [
    'an empty payload' => [[]],
    'no Names field' => [['Message' => '']],
    'null Names' => [['Message' => '', 'Names' => null]],
    'an empty Names list' => [['Message' => 'No records found', 'Names' => []]],
    'Names as a string' => [['Message' => '', 'Names' => 'Example Holdings Pty Ltd']],
]);

describe('listFromPayload()', function (): void {
    it('builds a match for each entry with a valid ABN, in order', function (): void {
        $matches = NameMatch::listFromPayload(abrPayload('names-search.jsonp'));

        expect($matches)->toHaveCount(3)
            ->each->toBeInstanceOf(NameMatch::class)
            ->and(array_keys($matches))->toBe([0, 1, 2])
            ->and(array_map(fn (NameMatch $match): string => $match->abn->value, $matches))
            ->toBe(['51824753556', '53004085616', '83914571673']);
    });

    it('skips the entry whose ABN fails the checksum', function (): void {
        $matches = NameMatch::listFromPayload(abrPayload('names-search.jsonp'));

        expect(array_map(fn (NameMatch $match): string => $match->name, $matches))
            ->not->toContain('Example Invalid Pty Ltd');
    });

    it('maps every field of an entry', function (): void {
        $match = NameMatch::listFromPayload(abrPayload('names-search.jsonp'))[0];

        expect($match->abn)->toBeInstanceOf(Abn::class)
            ->and($match->abn->value)->toBe('51824753556')
            ->and($match->name)->toBe('Example Holdings Pty Ltd')
            ->and($match->nameType)->toBe('Entity Name')
            ->and($match->state)->toBe(State::ACT)
            ->and($match->postcode)->toBeInstanceOf(Postcode::class)
            ->and($match->postcode?->value)->toBe('2600')
            ->and($match->score)->toBe(100)
            ->and($match->isCurrent)->toBeTrue();
    });

    it('maps a former business name', function (): void {
        $match = NameMatch::listFromPayload(abrPayload('names-search.jsonp'))[1];

        expect($match->abn->value)->toBe('53004085616')
            ->and($match->name)->toBe('Example Trading Co')
            ->and($match->nameType)->toBe('Business Name')
            ->and($match->state)->toBe(State::NSW)
            ->and($match->postcode?->value)->toBe('2000')
            ->and($match->score)->toBe(94)
            ->and($match->isCurrent)->toBeFalse();
    });

    it('maps an empty state and postcode to null', function (): void {
        $match = NameMatch::listFromPayload(abrPayload('names-search.jsonp'))[2];

        expect($match->abn->value)->toBe('83914571673')
            ->and($match->name)->toBe('Example Services')
            ->and($match->nameType)->toBe('Trading Name')
            ->and($match->state)->toBeNull()
            ->and($match->postcode)->toBeNull()
            ->and($match->score)->toBe(88)
            ->and($match->isCurrent)->toBeTrue();
    });

    it('maps an unknown state or invalid postcode to null', function (): void {
        $match = NameMatch::listFromPayload(['Names' => [
            nameSearchEntry(['State' => 'XYZ', 'Postcode' => '0000']),
        ]])[0];

        expect($match->state)->toBeNull()
            ->and($match->postcode)->toBeNull();
    });

    it('skips an entry it cannot use without throwing', function (mixed $entry): void {
        $matches = NameMatch::listFromPayload(['Names' => [
            nameSearchEntry(['Abn' => '53004085616', 'Name' => 'Example Trading Co']),
            $entry,
            nameSearchEntry(['Abn' => '83914571673', 'Name' => 'Example Services']),
        ]]);

        expect($matches)->toHaveCount(2)
            ->and(array_keys($matches))->toBe([0, 1])
            ->and($matches[0]->name)->toBe('Example Trading Co')
            ->and($matches[1]->name)->toBe('Example Services');
    })->with('unusable entries');

    it('returns an empty list when there are no names', function (array $payload): void {
        expect(NameMatch::listFromPayload($payload))->toBe([]);
    })->with('payloads without names');

    it('returns an empty list for a not-found or error response', function (string $fixture): void {
        expect(NameMatch::listFromPayload(abrPayload($fixture)))->toBe([]);
    })->with([
        'ABN not found' => 'abn-not-found.jsonp',
        'service error' => 'error.jsonp',
    ]);
});

describe('missing or unexpected fields', function (): void {
    it('falls back to a default instead of failing', function (string $field, mixed $value, string $property, mixed $default): void {
        $entry = nameSearchEntry([$field => $value]);
        $matches = NameMatch::listFromPayload(['Names' => [$entry]]);

        expect($matches)->toHaveCount(1)
            ->and($matches[0]->{$property})->toBe($default);
    })->with([
        'a missing name' => ['Name', null, 'name', ''],
        'a name that is not a string' => ['Name', 42, 'name', ''],
        'a missing name type' => ['NameType', null, 'nameType', ''],
        'a score sent as a string' => ['Score', '94', 'score', 0],
        'a missing score' => ['Score', null, 'score', 0],
        'IsCurrent sent as a string' => ['IsCurrent', 'true', 'isCurrent', false],
        'a missing IsCurrent' => ['IsCurrent', null, 'isCurrent', false],
        'a state that is not a string' => ['State', 2, 'state', null],
        'a postcode that is not a string' => ['Postcode', 2000, 'postcode', null],
    ]);
});
