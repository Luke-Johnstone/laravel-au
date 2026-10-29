<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\Data\NameMatch;
use EastpointLabs\LaravelAu\Support\State;

mutates(NameMatch::class);

describe('listFromPayload()', function (): void {
    it('builds a match for each entry with a valid ABN, in order, skipping the one that fails the checksum', function (): void {
        $matches = NameMatch::listFromPayload(abrPayload('names-search.jsonp'));

        expect(array_keys($matches))->toBe([0, 1, 2])
            ->and(array_map(fn (NameMatch $match): string => $match->abn->value, $matches))
            ->toBe(['51824753556', '53004085616', '83914571673'])
            ->and(array_map(fn (NameMatch $match): string => $match->name, $matches))
            ->not->toContain('Example Invalid Pty Ltd');
    });

    it('maps every field of a match', function (int $position, string $abn, string $name, string $nameType, ?State $state, ?string $postcode, int $score, bool $isCurrent): void {
        $match = NameMatch::listFromPayload(abrPayload('names-search.jsonp'))[$position];

        expect($match->abn->value)->toBe($abn)
            ->and($match->name)->toBe($name)
            ->and($match->nameType)->toBe($nameType)
            ->and($match->state)->toBe($state)
            ->and($match->postcode?->value)->toBe($postcode)
            ->and($match->score)->toBe($score)
            ->and($match->isCurrent)->toBe($isCurrent);
    })->with([
        // Position in the result, ABN, name, name type, state, postcode, score, current.
        'a current entity name' => [0, '51824753556', 'Example Holdings Pty Ltd', 'Entity Name', State::ACT, '2600', 100, true],
        'a former business name' => [1, '53004085616', 'Example Trading Co', 'Business Name', State::NSW, '2000', 94, false],
        'a trading name with an empty state and postcode' => [2, '83914571673', 'Example Services', 'Trading Name', null, null, 88, true],
    ]);

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

        expect(array_keys($matches))->toBe([0, 1])
            ->and($matches[0]->name)->toBe('Example Trading Co')
            ->and($matches[1]->name)->toBe('Example Services');
    })->with([
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

    it('returns an empty list when there are no names', function (array $payload): void {
        expect(NameMatch::listFromPayload($payload))->toBe([]);
    })->with([
        'an empty payload' => [[]],
        'no Names field' => [['Message' => '']],
        'null Names' => [['Message' => '', 'Names' => null]],
        'an empty Names list' => [['Message' => 'No records found', 'Names' => []]],
        'Names as a string' => [['Message' => '', 'Names' => 'Example Holdings Pty Ltd']],
        'a not-found response' => fn (): array => abrPayload('abn-not-found.jsonp'),
        'a service error response' => fn (): array => abrPayload('error.jsonp'),
    ]);
});

describe('missing or unexpected fields', function (): void {
    it('falls back to a default instead of failing', function (string $field, mixed $value, string $property, mixed $default): void {
        $matches = NameMatch::listFromPayload(['Names' => [nameSearchEntry([$field => $value])]]);

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
