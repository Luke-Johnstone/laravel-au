<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Digits;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;
use Pest\Expectation;

mutates(Postcode::class, Digits::class);

dataset('valid postcodes', [
    'first ACT postcode with a leading zero' => '0200',
    'first NT postcode with a leading zero' => '0800',
    'last NT postcode' => '0999',
    'first NSW postcode' => '1000',
    'NSW' => '2000',
    'Jervis Bay' => '2540',
    'first of ACT 2600-2618' => '2600',
    'Norfolk Island' => '2899',
    'ACT' => '2913',
    'VIC' => '3000',
    'QLD' => '4000',
    'SA' => '5000',
    'WA' => '6000',
    'TAS' => '7000',
    'VIC 8000-8999' => '8000',
    'last QLD postcode' => '9999',
]);

dataset('invalid postcodes', [
    'too short (3 digits, no leading zero)' => '800',
    'too short (1 digit)' => '2',
    'too long (5 digits)' => '20000',
    'too long (5 digits with a leading zero)' => '08000',
    'lowest postcode, in no state' => '0000',
    'inside 0000-0199, in no state' => '0100',
    'last of 0000-0199, in no state' => '0199',
    'first of 0300-0799, in no state' => '0300',
    'inside 0300-0799, in no state' => '0500',
    'last of 0300-0799, in no state' => '0799',
    'a letter in place of a digit' => '20a0',
    'a letter O in place of a zero' => 'O800',
    'a state prefix' => 'NSW 2000',
    'an internal space' => '20 00',
    'an internal non-breaking space (U+00A0)' => "20\u{00A0}00",
    'a hyphen' => '20-00',
    'a trailing dot' => '2000.',
    'a decimal' => '2000.0',
    'a leading plus sign' => '+2000',
    'a leading minus sign' => '-200',
    'exponent notation' => '2e03',
    'full-width digits' => '２０００',
]);

dataset('blank postcode inputs', [
    'an empty string' => '',
    'spaces only' => '    ',
    'a tab and newline only' => "\t\n",
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
]);

dataset('padded postcodes', [
    'canonical digits' => ['2000', '2000'],
    'surrounding spaces' => ['  2000  ', '2000'],
    'a leading space' => [' 3000', '3000'],
    'a trailing space' => ['4000 ', '4000'],
    'a surrounding tab and newline, keeping the leading zero' => ["\t0800\n", '0800'],
    'a trailing newline' => ["2600\n", '2600'],
    'surrounding non-breaking spaces' => ["\u{00A0}2000\u{00A0}", '2000'],
]);

dataset('postcode states', [
    'ACT 0200-0299' => ['0200', 'ACT'],
    'NT 0800-0999' => ['0800', 'NT'],
    'NSW 1000-1999' => ['1999', 'NSW'],
    'NSW 2000-2599' => ['2000', 'NSW'],
    'ACT 2600-2618' => ['2618', 'ACT'],
    'NSW 2619-2899' => ['2619', 'NSW'],
    'ACT 2900-2920' => ['2920', 'ACT'],
    'NSW 2921-2999' => ['2921', 'NSW'],
    'VIC 3000-3999' => ['3000', 'VIC'],
    'QLD 4000-4999' => ['4999', 'QLD'],
    'SA 5000-5999' => ['5000', 'SA'],
    'WA 6000-6999' => ['6999', 'WA'],
    'TAS 7000-7999' => ['7000', 'TAS'],
    'VIC 8000-8999' => ['8999', 'VIC'],
    'QLD 9000-9999' => ['9000', 'QLD'],
    'Jervis Bay' => ['2540', 'NSW'],
    'Norfolk Island' => ['2899', 'NSW'],
]);

dataset('formatted postcodes', [
    'canonical digits' => ['2000', '2000'],
    'a leading zero' => ['0800', '0800'],
    'surrounding spaces' => ['  0200 ', '0200'],
    'a surrounding tab and newline' => ["\t9999\n", '9999'],
]);

describe('isValid()', function (): void {
    it('accepts a four-digit postcode inside a state range', function (string $input): void {
        expect(Postcode::isValid($input))->toBeTrue();
    })->with('valid postcodes');

    it('rejects an invalid postcode', function (string $input): void {
        expect(Postcode::isValid($input))->toBeFalse();
    })->with('invalid postcodes');

    it('rejects blank input', function (string $input): void {
        expect(Postcode::isValid($input))->toBeFalse();
    })->with('blank postcode inputs');

    it('rejects null', function (): void {
        expect(Postcode::isValid(null))->toBeFalse();
    });

    it('requires the leading zero, so "0800" is valid and "800" is not', function (): void {
        expect(Postcode::isValid('0800'))->toBeTrue()
            ->and(Postcode::isValid('800'))->toBeFalse();
    });

    it('is valid exactly when a state range contains the postcode', function (): void {
        $mismatches = [];

        for ($number = 0; $number <= 9999; $number++) {
            $postcode = sprintf('%04d', $number);

            if (Postcode::isValid($postcode) !== (State::fromPostcode($postcode) !== null)) {
                $mismatches[] = $postcode;
            }
        }

        expect($mismatches)->toBe([]);
    });
});

describe('surrounding whitespace', function (): void {
    it('trims surrounding whitespace and keeps the four digits', function (string $input, string $value): void {
        expect(Postcode::isValid($input))->toBeTrue()
            ->and(Postcode::from($input)->value)->toBe($value)
            ->and(Postcode::tryFrom($input)?->value)->toBe($value);
    })->with('padded postcodes');
});

describe('from()', function (): void {
    it('creates a postcode holding the four digits as a string', function (): void {
        $postcode = Postcode::from('2000');

        expect($postcode)->toBeInstanceOf(Postcode::class)
            ->and($postcode->value)->toBe('2000');
    });

    it('keeps the leading zero', function (): void {
        expect(Postcode::from('0800')->value)->toBe('0800');
    });

    it('throws an InvalidValueException for an invalid postcode', function (string $input): void {
        expect(fn () => Postcode::from($input))
            ->toThrow(InvalidValueException::class, 'The given value is not a valid postcode.');
    })->with('invalid postcodes');

    it('throws an InvalidValueException for blank input', function (string $input): void {
        expect(fn () => Postcode::from($input))
            ->toThrow(InvalidValueException::class, 'The given value is not a valid postcode.');
    })->with('blank postcode inputs');

    it('does not reveal the input in the exception message', function (string $input): void {
        $digits = (string) preg_replace('/\D+/', '', $input);

        expect(fn () => Postcode::from($input))->toThrow(function (InvalidValueException $exception) use ($input, $digits): void {
            expect($exception->getMessage())
                ->not->toContain($input)
                ->when($digits !== '', fn (Expectation $message) => $message->not->toContain($digits));
        });
    })->with('invalid postcodes');
});

describe('tryFrom()', function (): void {
    it('returns a postcode for a valid postcode', function (string $input): void {
        expect(Postcode::tryFrom($input))->toBeInstanceOf(Postcode::class);
    })->with('valid postcodes');

    it('returns null for null', function (): void {
        expect(Postcode::tryFrom(null))->toBeNull();
    });

    it('returns null for blank input', function (string $input): void {
        expect(Postcode::tryFrom($input))->toBeNull();
    })->with('blank postcode inputs');

    it('returns null for an invalid postcode', function (string $input): void {
        expect(Postcode::tryFrom($input))->toBeNull();
    })->with('invalid postcodes');
});

describe('state()', function (): void {
    it('returns the state whose range contains the postcode', function (string $input, string $code): void {
        expect(Postcode::from($input)->state())->toBe(State::from($code));
    })->with('postcode states');

    it('treats Jervis Bay (2540) and Norfolk Island (2899) as NSW', function (): void {
        expect(Postcode::from('2540')->state())->toBe(State::NSW)
            ->and(Postcode::from('2899')->state())->toBe(State::NSW);
    });

    it('works with surrounding whitespace', function (): void {
        expect(Postcode::from(' 0800 ')->state())->toBe(State::NT);
    });
});

describe('belongsTo()', function (): void {
    it('belongs to its own state', function (string $input, string $code): void {
        expect(Postcode::from($input)->belongsTo(State::from($code)))->toBeTrue();
    })->with('postcode states');

    it('does not belong to any other state', function (string $input, string $code): void {
        $postcode = Postcode::from($input);
        $others = array_filter(State::cases(), fn (State $state): bool => $state->value !== $code);

        expect($others)->toHaveCount(7);

        foreach ($others as $state) {
            expect($postcode->belongsTo($state))->toBeFalse();
        }
    })->with('postcode states');
});

describe('format()', function (): void {
    it('returns the four-digit value', function (string $input, string $formatted): void {
        expect(Postcode::from($input)->format())->toBe($formatted);
    })->with('formatted postcodes');
});

describe('equals()', function (): void {
    it('is equal to the same postcode with surrounding whitespace', function (): void {
        expect(Postcode::from('0800')->equals(Postcode::from(' 0800 ')))->toBeTrue();
    });

    it('is not equal to a different postcode', function (): void {
        expect(Postcode::from('2600')->equals(Postcode::from('2619')))->toBeFalse();
    });

    it('is not equal to a different postcode in the same state', function (): void {
        expect(Postcode::from('2000')->equals(Postcode::from('2001')))->toBeFalse();
    });
});

describe('string conversion', function (): void {
    it('is stringable', function (): void {
        expect(Postcode::from('2000'))->toBeInstanceOf(Stringable::class);
    });

    it('casts to the four digits, keeping the leading zero', function (): void {
        $postcode = Postcode::from(' 0800 ');

        expect((string) $postcode)->toBe('0800')
            ->and("NT {$postcode}")->toBe('NT 0800');
    });
});

describe('JSON serialisation', function (): void {
    it('is JSON serialisable', function (): void {
        expect(Postcode::from('2000'))->toBeInstanceOf(JsonSerializable::class);
    });

    it('serialises to the four digits', function (): void {
        expect(Postcode::from(' 2000 ')->jsonSerialize())->toBe('2000');
    });

    it('encodes as a JSON string, keeping leading zeros', function (): void {
        expect(json_encode(Postcode::from('0800')))->toBe('"0800"')
            ->and(json_encode(['postcode' => Postcode::from(' 0200 ')]))->toBe('{"postcode":"0200"}');
    });
});

it('can only be created through its named constructors', function (): void {
    expect(fn () => new Postcode('2000'))->toThrow(Error::class, 'Call to private');
});
