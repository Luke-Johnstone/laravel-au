<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Abr\Testing;

use EastpointLabs\LaravelAu\Abr\AbrClient;
use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;
use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Abr\Data\NameMatch;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use RuntimeException;

/**
 * Stands in for the ABN Lookup client in tests, so no request is ever sent. Swap it in with
 * Abr::fake(), set the answers, then assert on the lookups that were made.
 */
final class AbrFake implements LooksUpAbns
{
    /** @var array<string, AbnDetails|null> Details by ABN digits; null means not found. */
    private array $abns = [];

    /** @var list<NameMatch> */
    private array $nameMatches = [];

    /** @var list<string> Every lookup made, such as "abn:51824753556", "acn:004085616" or "name". */
    private array $lookups = [];

    private bool $failing = false;

    private bool $preventStrayLookups = false;

    /**
     * Each value is the details for the ABN it's keyed by, or an array of ABN Lookup fields
     * merged over an active record for that ABN.
     *
     * @param  array<array-key, AbnDetails|array<string, mixed>>  $abns
     *
     * @throws InvalidValueException when a key isn't a valid ABN
     * @throws InvalidArgumentException when details have no valid ABN or status, or are keyed by
     *                                  a different ABN
     */
    public function __construct(array $abns = [])
    {
        foreach ($abns as $abn => $details) {
            // PHP turns a numeric string key such as '51824753556' into an int.
            $abn = Abn::from((string) $abn);

            if (is_array($details)) {
                $details = self::details([...['Abn' => $abn->value, 'AbnStatus' => 'Active'], ...$details]);
            }

            if (! $details->abn->equals($abn)) {
                throw new InvalidArgumentException('Faked ABN details must be keyed by their own ABN.');
            }

            $this->abns[$abn->value] = $details;
        }
    }

    public function abn(Abn|string $abn): ?AbnDetails
    {
        $abn = self::toAbn($abn);

        $this->record("abn:{$abn->value}");

        if (array_key_exists($abn->value, $this->abns)) {
            return $this->abns[$abn->value];
        }

        return $this->stray();
    }

    public function acn(Acn|string $acn): ?AbnDetails
    {
        $acn = is_string($acn) ? Acn::from($acn) : $acn;

        $this->record("acn:{$acn->value}");

        foreach ($this->abns as $details) {
            if ($details?->acn?->equals($acn) === true) {
                return $details;
            }
        }

        return $this->stray();
    }

    public function searchByName(string $name, int $maxResults = 10): array
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException('The name to search ABN Lookup for must not be empty.');
        }

        $this->record('name');

        return array_slice($this->nameMatches, 0, max(1, min(AbrClient::MAX_RESULTS, $maxResults)));
    }

    /**
     * Make these ABNs return null, as if they aren't on the register.
     */
    public function notFound(Abn|string ...$abns): static
    {
        foreach ($abns as $abn) {
            $this->abns[self::toAbn($abn)->value] = null;
        }

        return $this;
    }

    /**
     * Make this ABN return suppressed details: its status and GST registration, but no names
     * or address.
     *
     * @throws InvalidArgumentException when the status is empty
     */
    public function suppressed(Abn|string $abn, string $status = 'Active', bool $gst = false): static
    {
        $abn = self::toAbn($abn);

        $this->abns[$abn->value] = self::details([
            'Abn' => $abn->value,
            'AbnStatus' => $status,
            // The day GST began.
            'Gst' => $gst ? '2000-07-01' : null,
        ]);

        return $this;
    }

    /**
     * Set what searchByName() returns, whatever the name. Each match is a NameMatch or a
     * MatchingNames.aspx entry.
     *
     * @param  array<array-key, NameMatch|array<string, mixed>>  $matches
     *
     * @throws InvalidValueException when an entry has no valid ABN
     */
    public function withNameMatches(array $matches): static
    {
        $this->nameMatches = array_values(array_map(self::toNameMatch(...), $matches));

        return $this;
    }

    /**
     * Make every lookup throw, as if ABN Lookup couldn't be reached.
     */
    public function failing(): static
    {
        $this->failing = true;

        return $this;
    }

    /**
     * Throw instead of returning null when an ABN or ACN without details is looked up.
     */
    public function preventStrayLookups(): static
    {
        $this->preventStrayLookups = true;

        return $this;
    }

    /**
     * Assert that abn() was called with this ABN.
     */
    public function assertLookedUp(Abn|string $abn): void
    {
        Assert::assertTrue($this->wasLookedUp($abn), 'The ABN was not looked up.');
    }

    /**
     * Assert that abn() wasn't called with this ABN.
     */
    public function assertNotLookedUp(Abn|string $abn): void
    {
        Assert::assertFalse($this->wasLookedUp($abn), 'The ABN was looked up.');
    }

    /**
     * Assert that no lookup or name search was made.
     */
    public function assertNothingLookedUp(): void
    {
        Assert::assertSame(0, count($this->lookups), 'ABN Lookup was used.');
    }

    /**
     * Assert how many lookups and name searches were made in all.
     */
    public function assertLookupCount(int $count): void
    {
        Assert::assertSame($count, count($this->lookups), 'ABN Lookup was not used the expected number of times.');
    }

    /**
     * Record a lookup, then fail it when the fake is failing.
     *
     * @throws AbrException
     */
    private function record(string $lookup): void
    {
        $this->lookups[] = $lookup;

        if ($this->failing) {
            throw AbrException::connectionFailed();
        }
    }

    /**
     * The answer for a number without details: null, or an exception when strays are prevented.
     * It isn't an AbrException, so code that handles ABN Lookup errors can't swallow it.
     *
     * @throws RuntimeException
     */
    private function stray(): null
    {
        if ($this->preventStrayLookups) {
            throw new RuntimeException('The ABN Lookup fake has no details for the number looked up, and stray lookups are prevented.');
        }

        return null;
    }

    private function wasLookedUp(Abn|string $abn): bool
    {
        return in_array('abn:'.self::toAbn($abn)->value, $this->lookups, true);
    }

    /**
     * Build details the way a real lookup does, but blame the test setup when they're invalid.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws InvalidArgumentException
     */
    private static function details(array $payload): AbnDetails
    {
        try {
            return AbnDetails::fromPayload($payload);
        } catch (AbrException) {
            throw new InvalidArgumentException('Faked ABN details need a valid ABN and a status.');
        }
    }

    private static function toAbn(Abn|string $abn): Abn
    {
        return is_string($abn) ? Abn::from($abn) : $abn;
    }

    /**
     * @param  NameMatch|array<string, mixed>  $match
     */
    private static function toNameMatch(NameMatch|array $match): NameMatch
    {
        if ($match instanceof NameMatch) {
            return $match;
        }

        return NameMatch::listFromPayload(['Names' => [$match]])[0] ?? throw InvalidValueException::abn();
    }
}
