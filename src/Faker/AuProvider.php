<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Faker;

use EastpointLabs\LaravelAu\Support\State;
use Faker\Provider\Base;

/**
 * Australian test data for Faker: checksum-valid ABNs and ACNs, BSBs, ACMA's fictional phone
 * numbers, and postcodes and states. Values are canonical digits; format them with the value
 * objects.
 *
 * Generated ABNs and ACNs pass the checks but may belong to real businesses, so never send them
 * to the live ABN Lookup service.
 */
final class AuProvider extends Base
{
    /** ABN weights for digits 3 to 11. abn() works out the first two digits. */
    private const array ABN_WEIGHTS = [3, 5, 7, 9, 11, 13, 15, 17, 19];

    private const array ACN_WEIGHTS = [8, 7, 6, 5, 4, 3, 2, 1];

    /** Six of the mobile numbers ACMA reserves for use in fiction. */
    private const array MOBILES = ['0491570006', '0491570156', '0491570157', '0491570158', '0491570159', '0491570110'];

    /**
     * An 11-digit ABN with a valid checksum.
     */
    public function abn(): string
    {
        $rest = self::numerify('#########');
        $sum = 0;

        foreach (self::ABN_WEIGHTS as $position => $weight) {
            $sum += $weight * (int) $rest[$position];
        }

        // The first two digits add 10 × (first − 1) + second to the sum: any of 0 to 89, so one
        // of them makes it a multiple of 89. When that's 0 ("10"), 89 ("99") works too.
        $lead = (89 - $sum % 89) % 89;

        if ($lead === 0 && self::numberBetween(0, 1) === 1) {
            $lead = 89;
        }

        return (intdiv($lead, 10) + 1).($lead % 10).$rest;
    }

    /**
     * 11 digits that fail the ABN checksum.
     */
    public function invalidAbn(): string
    {
        $abn = $this->abn();

        // The last digit's weight, 19, shares no factor with 89, so changing it always breaks
        // the checksum.
        return substr($abn, 0, 10).(((int) $abn[10] + self::numberBetween(1, 9)) % 10);
    }

    /**
     * A 9-digit ACN with a valid check digit.
     */
    public function acn(): string
    {
        $digits = self::numerify('########');
        $sum = 0;

        foreach (self::ACN_WEIGHTS as $position => $weight) {
            $sum += $weight * (int) $digits[$position];
        }

        return $digits.((10 - $sum % 10) % 10);
    }

    /**
     * Any 6 digits, which is all a BSB is checked for.
     */
    public function bsb(): string
    {
        return self::numerify('######');
    }

    /**
     * One of ACMA's fictional mobile numbers, as national digits.
     */
    public function auMobileNumber(): string
    {
        return self::pick(self::MOBILES);
    }

    /**
     * A fictional landline in the state's area code, from ACMA's 5550 block, as national digits.
     */
    public function auLandlineNumber(?State $state = null): string
    {
        $areaCode = match ($state ?? $this->auState()) {
            State::NSW, State::ACT => '2',
            State::VIC, State::TAS => '3',
            State::QLD => '7',
            State::SA, State::WA, State::NT => '8',
        };

        return "0{$areaCode}5550".self::numerify('####');
    }

    /**
     * A postcode in one of the state's ranges, as 4 digits.
     */
    public function auPostcode(?State $state = null): string
    {
        [$from, $to] = self::pick(($state ?? $this->auState())->postcodeRanges());

        return sprintf('%04d', self::numberBetween($from, $to));
    }

    /**
     * A random state or territory.
     */
    public function auState(): State
    {
        return self::pick(State::cases());
    }

    /**
     * @template T
     *
     * @param  non-empty-list<T>  $items
     * @return T
     */
    private static function pick(array $items): mixed
    {
        return $items[self::numberBetween(0, count($items) - 1)];
    }
}
