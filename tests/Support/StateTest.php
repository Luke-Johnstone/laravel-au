<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Support\Digits;
use EastpointLabs\LaravelAu\Support\State;

mutates(State::class, Digits::class);

it('has the eight states and territories as string-backed cases, in order', function (): void {
    $codes = ['NSW', 'VIC', 'QLD', 'SA', 'WA', 'TAS', 'NT', 'ACT'];

    expect((new ReflectionEnum(State::class))->getBackingType()?->getName())->toBe('string')
        ->and(array_column(State::cases(), 'value'))->toBe($codes)
        ->and(array_column(State::cases(), 'name'))->toBe($codes);
});

it('labels each state and territory with its full name', function (): void {
    expect(State::NSW->label())->toBe('New South Wales')
        ->and(State::VIC->label())->toBe('Victoria')
        ->and(State::QLD->label())->toBe('Queensland')
        ->and(State::SA->label())->toBe('South Australia')
        ->and(State::WA->label())->toBe('Western Australia')
        ->and(State::TAS->label())->toBe('Tasmania')
        ->and(State::NT->label())->toBe('Northern Territory')
        ->and(State::ACT->label())->toBe('Australian Capital Territory');
});

it('lists each code with its label as options, in case order', function (): void {
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

describe('fromLoose()', function (): void {
    it('matches a code or full name in any case, ignoring surrounding whitespace and one trailing dot', function (string $input, State $state): void {
        expect(State::fromLoose($input))->toBe($state);
    })->with([
        'an upper-case code' => ['NSW', State::NSW],
        'a lower-case code' => ['vic', State::VIC],
        'a title-case code' => ['Qld', State::QLD],
        'a full name' => ['South Australia', State::SA],
        'a lower-case full name' => ['western australia', State::WA],
        'an upper-case full name' => ['TASMANIA', State::TAS],
        'a mixed-case full name' => ['northern TERRITORY', State::NT],
        'a code with a trailing dot' => ['Vic.', State::VIC],
        'a full name with a trailing dot' => ['Victoria.', State::VIC],
        'surrounding spaces' => ['  nsw  ', State::NSW],
        'a trailing dot inside a surrounding tab and newline' => ["\tQld.\n", State::QLD],
        'surrounding non-breaking spaces (U+00A0)' => ["\u{00A0}Australian Capital Territory\u{00A0}", State::ACT],
    ]);

    it('returns null when nothing matches', function (?string $input): void {
        expect(State::fromLoose($input))->toBeNull();
    })->with([
        'null' => null,
        'an empty string' => '',
        'whitespace only' => " \t\u{00A0}",
        'a lone dot' => '.',
        'two trailing dots' => 'Vic..',
        'an unknown code' => 'XYZ',
        'another country' => 'New Zealand',
        'part of a name' => 'South',
        'a longer word' => 'Victorian',
    ]);
});

describe('postcode ranges', function (): void {
    it('lists the inclusive postcode ranges of each state', function (): void {
        $ranges = array_combine(
            array_column(State::cases(), 'value'),
            array_map(fn (State $state): array => $state->postcodeRanges(), State::cases()),
        );

        expect($ranges)->toBe([
            'NSW' => [[1000, 1999], [2000, 2599], [2619, 2899], [2921, 2999]],
            'VIC' => [[3000, 3999], [8000, 8999]],
            'QLD' => [[4000, 4999], [9000, 9999]],
            'SA' => [[5000, 5999]],
            'WA' => [[6000, 6999]],
            'TAS' => [[7000, 7999]],
            'NT' => [[800, 999]],
            'ACT' => [[200, 299], [2600, 2618], [2900, 2920]],
        ]);
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
    it('maps the first and last postcode of every range, and the gaps between them', function (string $postcode, ?State $state): void {
        expect(State::fromPostcode($postcode))->toBe($state);
    })->with([
        'lowest postcode, in no state' => ['0000', null],
        'last of 0000-0199, in no state' => ['0199', null],
        'first of ACT 0200-0299' => ['0200', State::ACT],
        'last of ACT 0200-0299' => ['0299', State::ACT],
        'first of 0300-0799, in no state' => ['0300', null],
        'last of 0300-0799, in no state' => ['0799', null],
        'first of NT 0800-0999' => ['0800', State::NT],
        'last of NT 0800-0999' => ['0999', State::NT],
        'first of NSW 1000-1999' => ['1000', State::NSW],
        'last of NSW 1000-1999' => ['1999', State::NSW],
        'first of NSW 2000-2599' => ['2000', State::NSW],
        'last of NSW 2000-2599' => ['2599', State::NSW],
        'first of ACT 2600-2618' => ['2600', State::ACT],
        'last of ACT 2600-2618' => ['2618', State::ACT],
        'first of NSW 2619-2899' => ['2619', State::NSW],
        'last of NSW 2619-2899' => ['2899', State::NSW],
        'first of ACT 2900-2920' => ['2900', State::ACT],
        'last of ACT 2900-2920' => ['2920', State::ACT],
        'first of NSW 2921-2999' => ['2921', State::NSW],
        'last of NSW 2921-2999' => ['2999', State::NSW],
        'first of VIC 3000-3999' => ['3000', State::VIC],
        'last of VIC 3000-3999' => ['3999', State::VIC],
        'first of QLD 4000-4999' => ['4000', State::QLD],
        'last of QLD 4000-4999' => ['4999', State::QLD],
        'first of SA 5000-5999' => ['5000', State::SA],
        'last of SA 5000-5999' => ['5999', State::SA],
        'first of WA 6000-6999' => ['6000', State::WA],
        'last of WA 6000-6999' => ['6999', State::WA],
        'first of TAS 7000-7999' => ['7000', State::TAS],
        'last of TAS 7000-7999' => ['7999', State::TAS],
        'first of VIC 8000-8999' => ['8000', State::VIC],
        'last of VIC 8000-8999' => ['8999', State::VIC],
        'first of QLD 9000-9999' => ['9000', State::QLD],
        'last of QLD 9000-9999' => ['9999', State::QLD],
    ]);

    it('requires a string to be exactly four digits after trimming whitespace', function (string $postcode, ?State $state): void {
        expect(State::fromPostcode($postcode))->toBe($state);
    })->with([
        'three digits, without the leading zero' => ['800', null],
        'five digits' => ['08000', null],
        'an internal space' => ['08 00', null],
        'a numeric string in exponent notation' => ['8e02', null],
        'surrounding whitespace' => [" \t0800\n", State::NT],
        'surrounding non-breaking spaces (U+00A0)' => ["\u{00A0}0800\u{00A0}", State::NT],
    ]);

    it('reads an int as zero-padded to four digits, and rejects ints outside 0-9999', function (int $postcode, ?State $state): void {
        expect(State::fromPostcode($postcode))->toBe($state);
    })->with([
        '800, read as 0800' => [800, State::NT],
        '200, read as 0200' => [200, State::ACT],
        '9999, the largest postcode' => [9999, State::QLD],
        '0, read as 0000' => [0, null],
        'a negative int' => [-1, null],
        'a negative int whose absolute value is a postcode' => [-800, null],
        'just above 9999' => [10000, null],
        'the largest int' => [PHP_INT_MAX, null],
    ]);

    it('treats Jervis Bay (2540) and Norfolk Island (2899) as NSW', function (): void {
        expect(State::fromPostcode('2540'))->toBe(State::NSW)
            ->and(State::fromPostcode('2899'))->toBe(State::NSW);
    });

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
});
