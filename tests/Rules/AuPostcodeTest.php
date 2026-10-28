<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\AuPostcode;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

mutates(AuPostcode::class);

// Every form of the rule that accepts a postcode from any state.
dataset('any-state postcode rule forms', [
    'rule object' => [fn (): AuPostcode => new AuPostcode],
    'rule object with a null state' => [fn (): AuPostcode => new AuPostcode(null)],
    'string rule' => [fn (): string => 'au_postcode'],
    'matchingStateField() with no state in the data' => [fn (): AuPostcode => AuPostcode::matchingStateField('state')],
]);

// Every way to ask for a New South Wales postcode.
dataset('nsw postcode rule forms', [
    'State enum' => [fn (): AuPostcode => new AuPostcode(State::NSW)],
    'state code' => [fn (): AuPostcode => new AuPostcode('NSW')],
    'named state argument' => [fn (): AuPostcode => new AuPostcode(state: 'NSW')],
    'lower-case state code' => [fn (): AuPostcode => new AuPostcode('nsw')],
    'full state name' => [fn (): AuPostcode => new AuPostcode('New South Wales')],
    'lower-case state name with whitespace' => [fn (): AuPostcode => new AuPostcode('  new south wales  ')],
    'string rule with a state code' => [fn (): string => 'au_postcode:NSW'],
    'string rule with a lower-case state code' => [fn (): string => 'au_postcode:nsw'],
    'string rule with a state name' => [fn (): string => 'au_postcode:new south wales'],
]);

// Builds the rule for a given state, in each form.
dataset('state postcode rule makers', [
    'State enum' => [fn (State $state): AuPostcode => new AuPostcode($state)],
    'state code' => [fn (State $state): AuPostcode => new AuPostcode($state->value)],
    'state name' => [fn (State $state): AuPostcode => new AuPostcode($state->label())],
    'string rule with a state code' => [fn (State $state): string => "au_postcode:{$state->value}"],
    'string rule with a state name' => [fn (State $state): string => 'au_postcode:'.strtolower($state->label())],
]);

// State, a postcode inside it, a postcode outside it, and the name used in the message.
dataset('state postcodes', [
    'New South Wales' => [State::NSW, '2000', '3000', 'New South Wales'],
    'Victoria' => [State::VIC, '3000', '2000', 'Victoria'],
    'Queensland' => [State::QLD, '4000', '3000', 'Queensland'],
    'South Australia' => [State::SA, '5000', '6000', 'South Australia'],
    'Western Australia' => [State::WA, '6000', '5000', 'Western Australia'],
    'Tasmania' => [State::TAS, '7000', '3000', 'Tasmania'],
    'Northern Territory' => [State::NT, '0800', '2000', 'Northern Territory'],
    'Australian Capital Territory' => [State::ACT, '2600', '2000', 'Australian Capital Territory'],
]);

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
    'surrounding spaces' => ' 3000 ',
    'surrounding non-breaking spaces (U+00A0)' => "\u{00A0}2600\u{00A0}",
]);

dataset('invalid postcodes', [
    'too short (3 digits, no leading zero)' => '800',
    'too long (5 digits)' => '20000',
    'lowest postcode, in no state' => '0000',
    'inside 0000-0199, in no state' => '0100',
    'inside 0300-0799, in no state' => '0500',
    'last of 0300-0799, in no state' => '0799',
    'a letter in place of a digit' => '20a0',
    'a letter O in place of a zero' => 'O800',
    'a state prefix' => 'NSW 2000',
    'an internal space' => '20 00',
    'a trailing dot' => '2000.',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
]);

dataset('non-string postcode values', [
    'an integer' => 2000,
    'an integer without its leading zero' => 800,
    'a float' => 2000.0,
    'an array holding a valid postcode' => [['2000']],
    'an empty array' => [[]],
    'an object' => new stdClass,
    'a postcode value object' => Postcode::from('2000'),
    'true' => true,
    'false' => false,
]);

dataset('blank postcode strings', [
    'an empty string' => '',
    'spaces only' => '    ',
    'a tab and newline only' => "\t\n",
]);

dataset('unrecognised state names', [
    'Atlantis' => 'Atlantis',
    'New Zealand' => 'New Zealand',
]);

// The state field as the user sent it, the postcode that matches, the postcode that doesn't,
// and the state name used in the message.
dataset('recognised state field values', [
    'a state code' => ['NSW', '2000', '3000', 'New South Wales'],
    'a lower-case state code' => ['vic', '3000', '2000', 'Victoria'],
    'a lower-case state name' => ['victoria', '3000', '2000', 'Victoria'],
    'an abbreviation with a trailing dot' => ['Qld.', '4000', '3000', 'Queensland'],
    'surrounding whitespace' => ['  ACT  ', '2600', '2000', 'Australian Capital Territory'],
    'a State enum' => [State::NT, '0800', '2000', 'Northern Territory'],
]);

// The other fields in the request when the state can't be used.
dataset('unusable state data', [
    'a missing state' => [[]],
    'a null state' => [['state' => null]],
    'an empty state' => [['state' => '']],
    'a whitespace-only state' => [['state' => '   ']],
    'an unrecognised state' => [['state' => 'Atlantis']],
    'an integer state' => [['state' => 2]],
    'an array state' => [['state' => ['NSW']]],
    'a boolean state' => [['state' => true]],
    'an object state' => [['state' => new stdClass]],
]);

dataset('postcodes across states', [
    'NSW' => '2000',
    'VIC' => '3000',
    'NT' => '0800',
    'ACT' => '2600',
]);

describe('construction', function (): void {
    it('is a validation rule', function (): void {
        expect(new AuPostcode)->toBeInstanceOf(ValidationRule::class)
            ->and(new AuPostcode(State::NSW))->toBeInstanceOf(ValidationRule::class);
    });

    it('throws an InvalidArgumentException for an unrecognised state', function (string $state): void {
        expect(fn () => new AuPostcode($state))->toThrow(InvalidArgumentException::class);
    })->with('unrecognised state names');

    it('does not reveal the unrecognised state in the exception message', function (string $state): void {
        expect(fn () => new AuPostcode($state))->toThrow(function (InvalidArgumentException $exception) use ($state): void {
            expect(strtolower($exception->getMessage()))->not->toContain(strtolower($state));
        });
    })->with('unrecognised state names');

    it('makes a data-aware rule with matchingStateField()', function (): void {
        expect(AuPostcode::matchingStateField('state'))
            ->toBeInstanceOf(AuPostcode::class)
            ->toBeInstanceOf(ValidationRule::class)
            ->toBeInstanceOf(DataAwareRule::class);
    });
});

describe('without a state', function (): void {
    it('accepts a postcode from any state', function (Closure $rule, string $input): void {
        $validator = Validator::make(['postcode' => $input], ['postcode' => [$rule()]]);

        expect(Postcode::isValid($input))->toBeTrue()
            ->and($validator->passes())->toBeTrue();
    })->with('any-state postcode rule forms')->with('valid postcodes');

    it('rejects an invalid postcode with the postcode message', function (Closure $rule, string $input): void {
        $validator = Validator::make(['postcode' => $input], ['postcode' => [$rule()]]);

        expect(Postcode::isValid($input))->toBeFalse()
            ->and($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    })->with('any-state postcode rule forms')->with('invalid postcodes');

    it('names the attribute in the message', function (Closure $rule): void {
        $validator = Validator::make(
            ['postcode' => '0100'],
            ['postcode' => [$rule()]],
            [],
            ['postcode' => 'delivery postcode'],
        );

        expect($validator->errors()->first('postcode'))
            ->toBe('The delivery postcode must be a valid Australian postcode.');
    })->with('any-state postcode rule forms');
});

describe('with a state', function (): void {
    it('accepts a postcode in that state', function (Closure $rule, string $input): void {
        expect(Validator::make(['postcode' => $input], ['postcode' => [$rule()]])->passes())->toBeTrue();
    })->with('nsw postcode rule forms')->with([
        'Sydney' => '2000',
        'Jervis Bay' => '2540',
        'first NSW postcode' => '1000',
        'surrounding spaces' => ' 2000 ',
    ]);

    it('rejects a postcode from another state, naming the state', function (Closure $rule, string $input): void {
        $validator = Validator::make(['postcode' => $input], ['postcode' => [$rule()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be in New South Wales.');
    })->with('nsw postcode rule forms')->with([
        'VIC' => '3000',
        'NT' => '0800',
        'ACT' => '2600',
    ]);

    it('rejects an invalid postcode with the postcode message', function (Closure $rule, string $input): void {
        $validator = Validator::make(['postcode' => $input], ['postcode' => [$rule()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    })->with('nsw postcode rule forms')->with([
        'in no state' => '0100',
        'too short' => '800',
        'a letter in place of a digit' => '20a0',
    ]);

    it('checks each state and names it in the message', function (
        Closure $makeRule,
        State $state,
        string $inside,
        string $outside,
        string $label,
    ): void {
        $rule = $makeRule($state);

        $passing = Validator::make(['postcode' => $inside], ['postcode' => [$rule]]);
        $failing = Validator::make(['postcode' => $outside], ['postcode' => [$rule]]);

        expect($passing->passes())->toBeTrue()
            ->and($failing->fails())->toBeTrue()
            ->and($failing->errors()->first('postcode'))->toBe("The postcode must be in {$label}.");
    })->with('state postcode rule makers')->with('state postcodes');

    it('names the attribute in the message', function (Closure $rule): void {
        $validator = Validator::make(
            ['postcode' => '3000'],
            ['postcode' => [$rule()]],
            [],
            ['postcode' => 'delivery postcode'],
        );

        expect($validator->errors()->first('postcode'))->toBe('The delivery postcode must be in New South Wales.');
    })->with('nsw postcode rule forms');
});

describe('matchingStateField()', function (): void {
    it('rejects a postcode outside the chosen state', function (): void {
        $validator = Validator::make(
            ['state' => 'NSW', 'postcode' => '3000'],
            ['postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be in New South Wales.');
    });

    it('accepts a postcode inside the chosen state', function (string $state): void {
        $validator = Validator::make(
            ['state' => $state, 'postcode' => '3000'],
            ['postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->passes())->toBeTrue();
    })->with([
        'VIC' => 'VIC',
        'victoria' => 'victoria',
    ]);

    it('reads the state loosely, or as a State enum', function (
        State|string $state,
        string $inside,
        string $outside,
        string $label,
    ): void {
        $rules = ['postcode' => [AuPostcode::matchingStateField('state')]];

        $passing = Validator::make(['state' => $state, 'postcode' => $inside], $rules);
        $failing = Validator::make(['state' => $state, 'postcode' => $outside], $rules);

        expect($passing->passes())->toBeTrue()
            ->and($failing->fails())->toBeTrue()
            ->and($failing->errors()->first('postcode'))->toBe("The postcode must be in {$label}.");
    })->with('recognised state field values');

    it('rejects an invalid postcode with the postcode message when the state is recognised', function (): void {
        $validator = Validator::make(
            ['state' => 'NSW', 'postcode' => '0100'],
            ['postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    });

    it('accepts a postcode from any state when the state is unusable', function (array $stateData, string $postcode): void {
        $validator = Validator::make(
            [...$stateData, 'postcode' => $postcode],
            ['postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->passes())->toBeTrue();
    })->with('unusable state data')->with('postcodes across states');

    it('never throws when the state is unusable', function (array $stateData): void {
        $validator = Validator::make(
            [...$stateData, 'postcode' => '3000'],
            ['postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect(fn () => $validator->validate())->not->toThrow(Throwable::class);
    })->with('unusable state data');

    it('still rejects an invalid postcode when the state is unusable', function (array $stateData): void {
        $validator = Validator::make(
            [...$stateData, 'postcode' => '0100'],
            ['postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    })->with('unusable state data');

    it('passes null, leaving it to required and nullable', function (): void {
        $validator = Validator::make(
            ['state' => 'NSW', 'postcode' => null],
            ['postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->passes())->toBeTrue();
    });

    it('rejects a postcode that is not a string even when the state matches', function (): void {
        $validator = Validator::make(
            ['state' => 'NSW', 'postcode' => 2000],
            ['postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    });
});

describe('matchingStateField() with nested attributes', function (): void {
    it('checks each address against its own state', function (): void {
        $validator = Validator::make(
            ['addresses' => [
                ['state' => 'NSW', 'postcode' => '2000'],
                ['state' => 'VIC', 'postcode' => '3000'],
            ]],
            ['addresses.*.postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->passes())->toBeTrue();
    });

    it('reports each address against its own state', function (): void {
        $validator = Validator::make(
            ['addresses' => [
                ['state' => 'NSW', 'postcode' => '3000'],
                ['state' => 'VIC', 'postcode' => '2000'],
            ]],
            ['addresses.*.postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('addresses.0.postcode'))
            ->toBe('The addresses.0.postcode must be in New South Wales.')
            ->and($validator->errors()->first('addresses.1.postcode'))
            ->toBe('The addresses.1.postcode must be in Victoria.');
    });

    it('only fails the address whose postcode is outside its state', function (): void {
        $validator = Validator::make(
            ['addresses' => [
                ['state' => 'NSW', 'postcode' => '2000'],
                ['state' => 'VIC', 'postcode' => '2000'],
            ]],
            ['addresses.*.postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->errors()->has('addresses.0.postcode'))->toBeFalse()
            ->and($validator->errors()->first('addresses.1.postcode'))
            ->toBe('The addresses.1.postcode must be in Victoria.');
    });

    it('reads the sibling state rather than a top-level state', function (): void {
        $validator = Validator::make(
            [
                'state' => 'QLD',
                'addresses' => [
                    ['state' => 'NSW', 'postcode' => '2000'],
                    ['state' => 'VIC', 'postcode' => '3000'],
                ],
            ],
            ['addresses.*.postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->passes())->toBeTrue();
    });

    it('falls back to any state when a sibling state is unusable', function (): void {
        $validator = Validator::make(
            ['addresses' => [
                ['state' => 'Atlantis', 'postcode' => '3000'],
                ['postcode' => '0800'],
                ['state' => '', 'postcode' => '2600'],
            ]],
            ['addresses.*.postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->passes())->toBeTrue();
    });

    it('reads the sibling for an attribute written without a wildcard', function (): void {
        $validator = Validator::make(
            ['addresses' => [
                ['state' => 'NSW', 'postcode' => '3000'],
            ]],
            ['addresses.0.postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('addresses.0.postcode'))
            ->toBe('The addresses.0.postcode must be in New South Wales.');
    });
});

describe('matchingStateField() with a dotted field', function (): void {
    it('reads the field as an absolute path', function (): void {
        $validator = Validator::make(
            ['billing' => ['state' => 'NSW'], 'postcode' => '3000'],
            ['postcode' => [AuPostcode::matchingStateField('billing.state')]],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be in New South Wales.');
    });

    it('accepts a postcode in the state at that path', function (): void {
        $validator = Validator::make(
            ['billing' => ['state' => 'VIC'], 'postcode' => '3000'],
            ['postcode' => [AuPostcode::matchingStateField('billing.state')]],
        );

        expect($validator->passes())->toBeTrue();
    });

    it('reads the absolute path from a nested attribute, ignoring the sibling', function (): void {
        $validator = Validator::make(
            [
                'billing' => ['state' => 'VIC'],
                'addresses' => [
                    ['state' => 'NSW', 'postcode' => '3000'],
                    ['state' => 'NSW', 'postcode' => '2000'],
                ],
            ],
            ['addresses.*.postcode' => [AuPostcode::matchingStateField('billing.state')]],
        );

        expect($validator->errors()->has('addresses.0.postcode'))->toBeFalse()
            ->and($validator->errors()->first('addresses.1.postcode'))
            ->toBe('The addresses.1.postcode must be in Victoria.');
    });

    it('falls back to any state when nothing is at that path', function (): void {
        $validator = Validator::make(
            ['postcode' => '3000'],
            ['postcode' => [AuPostcode::matchingStateField('billing.state')]],
        );

        expect($validator->passes())->toBeTrue();
    });
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (Closure $rule, mixed $value): void {
        $validator = Validator::make(['postcode' => $value], ['postcode' => [$rule()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    })->with('any-state postcode rule forms')->with('non-string postcode values');

    it('rejects a value that is not a string when a state is given', function (Closure $rule): void {
        $validator = Validator::make(['postcode' => 2000], ['postcode' => [$rule()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    })->with('nsw postcode rule forms');
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (Closure $rule): void {
        expect(Validator::make(['postcode' => null], ['postcode' => [$rule()]])->passes())->toBeTrue();
    })->with('any-state postcode rule forms');

    it('passes null when a state is given', function (Closure $rule): void {
        expect(Validator::make(['postcode' => null], ['postcode' => [$rule()]])->passes())->toBeTrue();
    })->with('nsw postcode rule forms');

    it('passes null alongside nullable', function (Closure $rule): void {
        expect(Validator::make(['postcode' => null], ['postcode' => ['nullable', $rule()]])->passes())->toBeTrue();
    })->with('any-state postcode rule forms');

    it('leaves an empty or whitespace-only string to required', function (Closure $rule, string $input): void {
        expect(Validator::make(['postcode' => $input], ['postcode' => [$rule()]])->passes())->toBeTrue();
    })->with('any-state postcode rule forms')->with('blank postcode strings');

    it('rejects a blank string when run on its own', function (string $input): void {
        expect(Postcode::isValid($input))->toBeFalse()
            ->and(ruleFails(new AuPostcode, $input))->toBeTrue()
            ->and(ruleFails(new AuPostcode(State::NSW), $input))->toBeTrue();
    })->with('blank postcode strings');

    it('passes null when run on its own', function (): void {
        expect(ruleFails(new AuPostcode, null))->toBeFalse()
            ->and(ruleFails(new AuPostcode(State::NSW), null))->toBeFalse();
    });
});
