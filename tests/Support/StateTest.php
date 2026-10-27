<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Support\Digits;
use EastpointLabs\LaravelAu\Support\State;

mutates(State::class, Digits::class);

dataset('state labels', [
    'New South Wales' => ['NSW', 'New South Wales'],
    'Victoria' => ['VIC', 'Victoria'],
    'Queensland' => ['QLD', 'Queensland'],
    'South Australia' => ['SA', 'South Australia'],
    'Western Australia' => ['WA', 'Western Australia'],
    'Tasmania' => ['TAS', 'Tasmania'],
    'Northern Territory' => ['NT', 'Northern Territory'],
    'Australian Capital Territory' => ['ACT', 'Australian Capital Territory'],
]);

dataset('loose state inputs', [
    // Codes in any case.
    'NSW code' => ['NSW', 'NSW'],
    'VIC code' => ['VIC', 'VIC'],
    'QLD code' => ['QLD', 'QLD'],
    'SA code' => ['SA', 'SA'],
    'WA code' => ['WA', 'WA'],
    'TAS code' => ['TAS', 'TAS'],
    'NT code' => ['NT', 'NT'],
    'ACT code' => ['ACT', 'ACT'],
    'lower-case NSW code' => ['nsw', 'NSW'],
    'lower-case VIC code' => ['vic', 'VIC'],
    'lower-case QLD code' => ['qld', 'QLD'],
    'lower-case SA code' => ['sa', 'SA'],
    'lower-case WA code' => ['wa', 'WA'],
    'lower-case TAS code' => ['tas', 'TAS'],
    'lower-case NT code' => ['nt', 'NT'],
    'lower-case ACT code' => ['act', 'ACT'],
    'mixed-case code' => ['nSw', 'NSW'],
    'title-case Qld' => ['Qld', 'QLD'],
    'title-case Tas' => ['Tas', 'TAS'],

    // Full names in any case.
    'New South Wales' => ['New South Wales', 'NSW'],
    'Victoria' => ['Victoria', 'VIC'],
    'Queensland' => ['Queensland', 'QLD'],
    'South Australia' => ['South Australia', 'SA'],
    'Western Australia' => ['Western Australia', 'WA'],
    'Tasmania' => ['Tasmania', 'TAS'],
    'Northern Territory' => ['Northern Territory', 'NT'],
    'Australian Capital Territory' => ['Australian Capital Territory', 'ACT'],
    'lower-case name' => ['new south wales', 'NSW'],
    'upper-case name' => ['NEW SOUTH WALES', 'NSW'],
    'mixed-case name' => ['australian CAPITAL territory', 'ACT'],
    'lower-case single-word name' => ['victoria', 'VIC'],

    // Surrounding whitespace.
    'code with surrounding spaces' => ['  nsw  ', 'NSW'],
    'name with surrounding spaces' => ['  Western Australia  ', 'WA'],
    'code with a surrounding tab and newline' => ["\tTAS\n", 'TAS'],
    'name with a surrounding tab and newline' => ["\tNorthern Territory\n", 'NT'],

    // Abbreviations with a trailing dot.
    'Vic.' => ['Vic.', 'VIC'],
    'qld.' => ['qld.', 'QLD'],
    'Qld.' => ['Qld.', 'QLD'],
    'Tas.' => ['Tas.', 'TAS'],
    'NSW.' => ['NSW.', 'NSW'],
    'act.' => ['act.', 'ACT'],
    'trailing dot with surrounding spaces' => ['  Vic.  ', 'VIC'],
    'full name with a trailing dot' => ['Victoria.', 'VIC'],
    'surrounding non-breaking spaces' => ["\u{00A0}NSW\u{00A0}", 'NSW'],
]);

dataset('unknown state inputs', [
    'an empty string' => '',
    'spaces only' => '   ',
    'a tab and newline only' => "\t\n",
    'a lone dot' => '.',
    'an unknown code' => 'XYZ',
    'another country' => 'New Zealand',
    'the country' => 'Australia',
    'part of a name' => 'South',
    'a territory that is not a state case' => 'Norfolk Island',
    'a postcode' => '2000',
    'a code with a digit' => 'NSW1',
    'two codes' => 'NSW VIC',
    'a code with internal spaces' => 'N S W',
    'a name with a suffix' => 'Victorian',
    'a code with a prefix' => 'State of NSW',
    'two trailing dots' => 'Vic..',
]);

dataset('postcode boundaries', [
    // 0000-0199: no state.
    'lowest postcode, in no state' => ['0000', null],
    'inside 0000-0199, in no state' => ['0100', null],
    'last of 0000-0199, in no state' => ['0199', null],

    // ACT 0200-0299.
    'first of ACT 0200-0299' => ['0200', 'ACT'],
    'last of ACT 0200-0299' => ['0299', 'ACT'],

    // 0300-0799: no state.
    'first of 0300-0799, in no state' => ['0300', null],
    'inside 0300-0799, in no state' => ['0500', null],
    'last of 0300-0799, in no state' => ['0799', null],

    // NT 0800-0999.
    'first of NT 0800-0999' => ['0800', 'NT'],
    'last of NT 0800-0999' => ['0999', 'NT'],

    // NSW 1000-1999.
    'first of NSW 1000-1999' => ['1000', 'NSW'],
    'last of NSW 1000-1999' => ['1999', 'NSW'],

    // NSW 2000-2599.
    'first of NSW 2000-2599' => ['2000', 'NSW'],
    'last of NSW 2000-2599' => ['2599', 'NSW'],

    // ACT 2600-2618.
    'first of ACT 2600-2618' => ['2600', 'ACT'],
    'last of ACT 2600-2618' => ['2618', 'ACT'],

    // NSW 2619-2899.
    'first of NSW 2619-2899' => ['2619', 'NSW'],
    'last of NSW 2619-2899' => ['2899', 'NSW'],

    // ACT 2900-2920.
    'first of ACT 2900-2920' => ['2900', 'ACT'],
    'last of ACT 2900-2920' => ['2920', 'ACT'],

    // NSW 2921-2999.
    'first of NSW 2921-2999' => ['2921', 'NSW'],
    'last of NSW 2921-2999' => ['2999', 'NSW'],

    // VIC 3000-3999.
    'first of VIC 3000-3999' => ['3000', 'VIC'],
    'last of VIC 3000-3999' => ['3999', 'VIC'],

    // QLD 4000-4999.
    'first of QLD 4000-4999' => ['4000', 'QLD'],
    'last of QLD 4000-4999' => ['4999', 'QLD'],

    // SA 5000-5999.
    'first of SA 5000-5999' => ['5000', 'SA'],
    'last of SA 5000-5999' => ['5999', 'SA'],

    // WA 6000-6999.
    'first of WA 6000-6999' => ['6000', 'WA'],
    'last of WA 6000-6999' => ['6999', 'WA'],

    // TAS 7000-7999.
    'first of TAS 7000-7999' => ['7000', 'TAS'],
    'last of TAS 7000-7999' => ['7999', 'TAS'],

    // VIC 8000-8999.
    'first of VIC 8000-8999' => ['8000', 'VIC'],
    'last of VIC 8000-8999' => ['8999', 'VIC'],

    // QLD 9000-9999.
    'first of QLD 9000-9999' => ['9000', 'QLD'],
    'last of QLD 9000-9999' => ['9999', 'QLD'],
]);

dataset('postcode strings', [
    'four characters with a leading zero' => ['0800', 'NT'],
    'three characters' => ['800', null],
    'three characters with surrounding spaces' => [' 800 ', null],
    'five characters' => ['10000', null],
    'five characters with a leading zero' => ['08000', null],
    'surrounding spaces' => ['  0800  ', 'NT'],
    'a surrounding tab and newline' => ["\t2600\n", 'ACT'],
    'an empty string' => ['', null],
    'four spaces' => ['    ', null],
    'a letter in place of a digit' => ['08a0', null],
    'an internal space' => ['08 00', null],
    'a leading minus sign' => ['-800', null],
    'a leading plus sign' => ['+800', null],
    'a trailing dot' => ['800.', null],
    'exponent notation' => ['8e02', null],
    'an internal non-breaking space' => ["08\u{00A0}00", null],
    'surrounding non-breaking spaces' => ["\u{00A0}0800\u{00A0}", 'NT'],
]);

dataset('postcode ints', [
    'zero, padded to 0000' => [0, null],
    '200, padded to 0200' => [200, 'ACT'],
    '800, padded to 0800' => [800, 'NT'],
    '999, padded to 0999' => [999, 'NT'],
    'four digits' => [2600, 'ACT'],
    'the largest postcode' => [9999, 'QLD'],
    'a negative int' => [-1, null],
    'a negative int whose absolute value is a postcode' => [-800, null],
    'just above 9999' => [10000, null],
    'well above 9999' => [26000, null],
    'the smallest int' => [PHP_INT_MIN, null],
    'the largest int' => [PHP_INT_MAX, null],
]);

dataset('state postcode ranges', [
    'NSW' => ['NSW', [[1000, 1999], [2000, 2599], [2619, 2899], [2921, 2999]]],
    'VIC' => ['VIC', [[3000, 3999], [8000, 8999]]],
    'QLD' => ['QLD', [[4000, 4999], [9000, 9999]]],
    'SA' => ['SA', [[5000, 5999]]],
    'WA' => ['WA', [[6000, 6999]]],
    'TAS' => ['TAS', [[7000, 7999]]],
    'NT' => ['NT', [[800, 999]]],
    'ACT' => ['ACT', [[200, 299], [2600, 2618], [2900, 2920]]],
]);

describe('cases', function (): void {
    it('is a string-backed enum', function (): void {
        expect(State::NSW)->toBeInstanceOf(BackedEnum::class)
            ->and((new ReflectionEnum(State::class))->getBackingType()?->getName())->toBe('string');
    });

    it('has exactly the eight states and territories, in order', function (): void {
        $values = array_map(fn (State $state): string => $state->value, State::cases());

        expect($values)->toBe(['NSW', 'VIC', 'QLD', 'SA', 'WA', 'TAS', 'NT', 'ACT']);
    });

    it('names each case the same as its value', function (): void {
        foreach (State::cases() as $state) {
            expect($state->name)->toBe($state->value);
        }
    });

    it('resolves each case from its code', function (): void {
        expect(State::from('NSW'))->toBe(State::NSW)
            ->and(State::from('VIC'))->toBe(State::VIC)
            ->and(State::from('QLD'))->toBe(State::QLD)
            ->and(State::from('SA'))->toBe(State::SA)
            ->and(State::from('WA'))->toBe(State::WA)
            ->and(State::from('TAS'))->toBe(State::TAS)
            ->and(State::from('NT'))->toBe(State::NT)
            ->and(State::from('ACT'))->toBe(State::ACT);
    });
});

describe('label()', function (): void {
    it('returns the full name of the state or territory', function (string $code, string $label): void {
        expect(State::from($code)->label())->toBe($label);
    })->with('state labels');
});

describe('fromLoose()', function (): void {
    it('accepts codes and full names in any case, with whitespace and a trailing dot', function (string $input, string $code): void {
        expect(State::fromLoose($input))->toBe(State::from($code));
    })->with('loose state inputs');

    it('accepts the label of every case', function (): void {
        foreach (State::cases() as $state) {
            expect(State::fromLoose($state->label()))->toBe($state)
                ->and(State::fromLoose(strtolower($state->label())))->toBe($state)
                ->and(State::fromLoose(strtoupper($state->label())))->toBe($state);
        }
    });

    it('returns null for unknown or blank input', function (string $input): void {
        expect(State::fromLoose($input))->toBeNull();
    })->with('unknown state inputs');

    it('returns null for null', function (): void {
        expect(State::fromLoose(null))->toBeNull();
    });
});

describe('options()', function (): void {
    it('maps each code to its label', function (): void {
        expect(State::options())->toBe([
            'NSW' => 'New South Wales',
            'VIC' => 'Victoria',
            'QLD' => 'Queensland',
            'SA' => 'South Australia',
            'WA' => 'Western Australia',
            'TAS' => 'Tasmania',
            'NT' => 'Northern Territory',
            'ACT' => 'Australian Capital Territory',
        ]);
    });

    it('lists the states in case order', function (): void {
        $codes = array_map(fn (State $state): string => $state->value, State::cases());

        expect(array_keys(State::options()))->toBe($codes);
    });

    it('uses label() for each value', function (): void {
        foreach (State::cases() as $state) {
            expect(State::options())->toHaveKey($state->value, $state->label());
        }
    });
});

describe('postcodeRanges()', function (): void {
    it('returns the inclusive postcode ranges for the state', function (string $code, array $ranges): void {
        expect(State::from($code)->postcodeRanges())->toBe($ranges);
    })->with('state postcode ranges');

    it('returns well-formed ranges within 0000-9999', function (): void {
        foreach (State::cases() as $state) {
            foreach ($state->postcodeRanges() as [$start, $end]) {
                expect($start)->toBeGreaterThanOrEqual(0)
                    ->and($end)->toBeLessThanOrEqual(9999)
                    ->and($start)->toBeLessThanOrEqual($end);
            }
        }
    });

    it('has no two ranges that overlap, across all states', function (): void {
        $ranges = [];

        foreach (State::cases() as $state) {
            foreach ($state->postcodeRanges() as [$start, $end]) {
                $ranges[] = ['state' => $state->value, 'start' => $start, 'end' => $end];
            }
        }

        $overlaps = [];
        $count = count($ranges);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $ranges[$i];
                $b = $ranges[$j];

                if ($a['start'] <= $b['end'] && $b['start'] <= $a['end']) {
                    $overlaps[] = sprintf(
                        '%s %04d-%04d overlaps %s %04d-%04d',
                        $a['state'], $a['start'], $a['end'],
                        $b['state'], $b['start'], $b['end'],
                    );
                }
            }
        }

        expect($ranges)->not->toBeEmpty()
            ->and($overlaps)->toBe([]);
    });
});

describe('fromPostcode()', function (): void {
    it('maps the first and last postcode of every range, and the gaps either side', function (string $postcode, ?string $code): void {
        expect(State::fromPostcode($postcode))->toBe($code === null ? null : State::from($code));
    })->with('postcode boundaries');

    it('maps an int the same as its zero-padded string', function (string $postcode, ?string $code): void {
        expect(State::fromPostcode((int) $postcode))->toBe($code === null ? null : State::from($code));
    })->with('postcode boundaries');

    it('requires a string to be exactly four digits after trimming whitespace', function (string $postcode, ?string $code): void {
        expect(State::fromPostcode($postcode))->toBe($code === null ? null : State::from($code));
    })->with('postcode strings');

    it('zero-pads an int to four digits and rejects ints outside 0-9999', function (int $postcode, ?string $code): void {
        expect(State::fromPostcode($postcode))->toBe($code === null ? null : State::from($code));
    })->with('postcode ints');

    it('treats "0800" and 800 as NT, but not "800"', function (): void {
        expect(State::fromPostcode('0800'))->toBe(State::NT)
            ->and(State::fromPostcode(800))->toBe(State::NT)
            ->and(State::fromPostcode('800'))->toBeNull()
            ->and(State::fromPostcode(-1))->toBeNull()
            ->and(State::fromPostcode(10000))->toBeNull();
    });

    it('maps Jervis Bay (2540) and Norfolk Island (2899) to NSW', function (string|int $postcode): void {
        expect(State::fromPostcode($postcode))->toBe(State::NSW);
    })->with([
        'Jervis Bay as a string' => '2540',
        'Jervis Bay as an int' => 2540,
        'Norfolk Island as a string' => '2899',
        'Norfolk Island as an int' => 2899,
    ]);

    it('maps every postcode to the one state whose ranges contain it', function (): void {
        $mismatches = [];

        for ($number = 0; $number <= 9999; $number++) {
            $postcode = sprintf('%04d', $number);

            $containing = array_values(array_filter(
                State::cases(),
                function (State $state) use ($number): bool {
                    foreach ($state->postcodeRanges() as [$start, $end]) {
                        if ($number >= $start && $number <= $end) {
                            return true;
                        }
                    }

                    return false;
                },
            ));

            $expected = count($containing) === 1 ? $containing[0] : null;

            if (count($containing) > 1 || State::fromPostcode($postcode) !== $expected) {
                $mismatches[] = $postcode;
            }
        }

        expect($mismatches)->toBe([]);
    });

    it('maps the unallocated blocks 0000-0199 and 0300-0799 to no state', function (): void {
        $mapped = [];

        foreach ([...range(0, 199), ...range(300, 799)] as $number) {
            if (State::fromPostcode(sprintf('%04d', $number)) !== null) {
                $mapped[] = sprintf('%04d', $number);
            }
        }

        expect($mapped)->toBe([]);
    });
});
