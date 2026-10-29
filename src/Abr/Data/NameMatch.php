<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Abr\Data;

use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;

/**
 * One result from an ABN Lookup name search.
 */
final readonly class NameMatch
{
    public function __construct(
        public Abn $abn,
        public string $name,
        public string $nameType,
        public ?State $state,
        public ?Postcode $postcode,
        public int $score,
        public bool $isCurrent,
    ) {}

    /**
     * Build the matches from a MatchingNames.aspx payload, in the order returned. Entries
     * without a valid ABN are skipped rather than failing the whole search.
     *
     * @param  array<array-key, mixed>  $payload
     * @return list<self>
     */
    public static function listFromPayload(array $payload): array
    {
        $entries = $payload['Names'] ?? [];

        if (! is_array($entries)) {
            return [];
        }

        $matches = [];

        foreach ($entries as $entry) {
            $match = is_array($entry) ? self::tryFromEntry($entry) : null;

            if ($match !== null) {
                $matches[] = $match;
            }
        }

        return $matches;
    }

    /**
     * @param  array<array-key, mixed>  $entry
     */
    private static function tryFromEntry(array $entry): ?self
    {
        $abn = Abn::tryFrom(is_string($entry['Abn'] ?? null) ? $entry['Abn'] : null);

        if ($abn === null) {
            return null;
        }

        return new self(
            abn: $abn,
            name: is_string($entry['Name'] ?? null) ? $entry['Name'] : '',
            nameType: is_string($entry['NameType'] ?? null) ? $entry['NameType'] : '',
            state: is_string($entry['State'] ?? null) ? State::fromLoose($entry['State']) : null,
            postcode: is_string($entry['Postcode'] ?? null) ? Postcode::tryFrom($entry['Postcode']) : null,
            score: is_int($entry['Score'] ?? null) ? $entry['Score'] : 0,
            isCurrent: ($entry['IsCurrent'] ?? false) === true,
        );
    }
}
