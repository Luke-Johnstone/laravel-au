<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\AuPostcode;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Support\Facades\Validator;

mutates(AuPostcode::class);

describe('without a state', function (): void {
    it('accepts a postcode from any state', function (string $input): void {
        expect(Validator::make(['postcode' => $input], ['postcode' => [new AuPostcode]])->passes())->toBeTrue();
    })->with([
        'Victoria' => '3000',
        'a leading zero' => '0800',
        'surrounding spaces' => ' 2600 ',
    ]);

    it('rejects an invalid postcode with the postcode message', function (string $input): void {
        $validator = Validator::make(['postcode' => $input], ['postcode' => [new AuPostcode]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    })->with([
        'in no state' => '0100',
        'missing its leading zero' => '800',
        'too long (5 digits)' => '20000',
        'a letter in place of a digit' => '20a0',
    ]);

    it('gives the same result and message from the au_postcode string rule', function (string $input): void {
        $object = Validator::make(['postcode' => $input], ['postcode' => [new AuPostcode]]);
        $string = Validator::make(['postcode' => $input], ['postcode' => 'au_postcode']);

        expect($string->passes())->toBe($object->passes())
            ->and($string->errors()->all())->toBe($object->errors()->all());
    })->with([
        'a valid postcode' => '3000',
        'an invalid postcode' => '0100',
    ]);
});

describe('with a state', function (): void {
    it('accepts a postcode in New South Wales and rejects one outside it, naming the state', function (AuPostcode|string $rule): void {
        $inside = Validator::make(['postcode' => '2000'], ['postcode' => [$rule]]);
        $outside = Validator::make(['postcode' => '3000'], ['postcode' => [$rule]]);

        expect($inside->passes())->toBeTrue()
            ->and($outside->fails())->toBeTrue()
            ->and($outside->errors()->first('postcode'))->toBe('The postcode must be in New South Wales.');
    })->with([
        'State::NSW' => [fn (): AuPostcode => new AuPostcode(State::NSW)],
        "state: 'NSW'" => [fn (): AuPostcode => new AuPostcode(state: 'NSW')],
        "'nsw'" => [fn (): AuPostcode => new AuPostcode('nsw')],
        "'new south wales'" => [fn (): AuPostcode => new AuPostcode('new south wales')],
        'au_postcode:NSW' => ['au_postcode:NSW'],
        'au_postcode:new south wales' => ['au_postcode:new south wales'],
    ]);

    it('names each state in the message', function (State $state, string $inside, string $outside, string $label): void {
        $rule = new AuPostcode($state);

        $passing = Validator::make(['postcode' => $inside], ['postcode' => [$rule]]);
        $failing = Validator::make(['postcode' => $outside], ['postcode' => [$rule]]);

        expect($passing->passes())->toBeTrue()
            ->and($failing->errors()->first('postcode'))->toBe("The postcode must be in {$label}.");
    })->with([
        'New South Wales' => [State::NSW, '2000', '3000', 'New South Wales'],
        'Victoria' => [State::VIC, '3000', '2000', 'Victoria'],
        'Queensland' => [State::QLD, '4000', '3000', 'Queensland'],
        'South Australia' => [State::SA, '5000', '6000', 'South Australia'],
        'Western Australia' => [State::WA, '6000', '5000', 'Western Australia'],
        'Tasmania' => [State::TAS, '7000', '3000', 'Tasmania'],
        'Northern Territory' => [State::NT, '0800', '2000', 'Northern Territory'],
        'Australian Capital Territory' => [State::ACT, '2600', '2000', 'Australian Capital Territory'],
    ]);

    it('rejects an invalid postcode with the postcode message rather than the state message', function (): void {
        $validator = Validator::make(['postcode' => '0100'], ['postcode' => [new AuPostcode(State::NSW)]]);

        expect($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    });

    it('throws for an unrecognised state without repeating it', function (): void {
        expect(fn () => new AuPostcode('Atlantis'))
            ->toThrow(function (InvalidArgumentException $exception): void {
                expect(strtolower($exception->getMessage()))->not->toContain('atlantis');
            });
    });
});

it('names the attribute in both messages', function (): void {
    $attributes = ['postcode' => 'delivery postcode'];

    $invalid = Validator::make(['postcode' => '0100'], ['postcode' => [new AuPostcode]], [], $attributes);
    $wrongState = Validator::make(['postcode' => '3000'], ['postcode' => [new AuPostcode(State::NSW)]], [], $attributes);

    expect($invalid->errors()->first('postcode'))->toBe('The delivery postcode must be a valid Australian postcode.')
        ->and($wrongState->errors()->first('postcode'))->toBe('The delivery postcode must be in New South Wales.');
});

describe('matchingStateField()', function (): void {
    it('is a data-aware rule', function (): void {
        expect(AuPostcode::matchingStateField('state'))->toBeInstanceOf(DataAwareRule::class);
    });

    it('rejects a postcode outside the chosen state, naming the state', function (): void {
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
            ->and($failing->errors()->first('postcode'))->toBe("The postcode must be in {$label}.");
    })->with([
        'an abbreviation with a trailing dot' => ['Qld.', '4000', '3000', 'Queensland'],
        'surrounding whitespace' => ['  ACT  ', '2600', '2000', 'Australian Capital Territory'],
        'a State enum' => [State::NT, '0800', '2000', 'Northern Territory'],
    ]);

    it('accepts a postcode from any state, and never throws, when the state is unusable', function (array $stateData): void {
        $validator = Validator::make(
            [...$stateData, 'postcode' => '3000'],
            ['postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect(fn () => $validator->passes())->not->toThrow(Throwable::class)
            ->and($validator->passes())->toBeTrue();
    })->with([
        'a missing state' => [[]],
        'a null state' => [['state' => null]],
        'an empty state' => [['state' => '']],
        'a whitespace-only state' => [['state' => '   ']],
        'an unrecognised state' => [['state' => 'Atlantis']],
        'an integer state' => [['state' => 2]],
        'an array state' => [['state' => ['NSW']]],
        'a boolean state' => [['state' => true]],
    ]);

    it('still rejects an invalid postcode with the postcode message', function (string $state): void {
        $validator = Validator::make(
            ['state' => $state, 'postcode' => '0100'],
            ['postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    })->with([
        'a recognised state' => 'NSW',
        'an unrecognised state' => 'Atlantis',
    ]);
});

describe('matchingStateField() with nested attributes', function (): void {
    it('checks each address against its own state', function (): void {
        $validator = Validator::make(
            ['addresses' => [
                ['state' => 'NSW', 'postcode' => '3000'],
                ['state' => 'VIC', 'postcode' => '2000'],
                ['state' => 'VIC', 'postcode' => '3000'],
            ]],
            ['addresses.*.postcode' => [AuPostcode::matchingStateField('state')]],
        );

        expect($validator->errors()->first('addresses.0.postcode'))
            ->toBe('The addresses.0.postcode must be in New South Wales.')
            ->and($validator->errors()->first('addresses.1.postcode'))
            ->toBe('The addresses.1.postcode must be in Victoria.')
            ->and($validator->errors()->has('addresses.2.postcode'))->toBeFalse();
    });

    it('ignores a top-level state field', function (): void {
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
});

describe('matchingStateField() with a dotted field', function (): void {
    it('reads the field as an absolute path', function (): void {
        $validator = Validator::make(
            ['billing' => ['state' => 'NSW'], 'postcode' => '3000'],
            ['postcode' => [AuPostcode::matchingStateField('billing.state')]],
        );

        expect($validator->errors()->first('postcode'))->toBe('The postcode must be in New South Wales.');
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
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (mixed $value): void {
        $validator = Validator::make(['postcode' => $value], ['postcode' => [new AuPostcode]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('postcode'))->toBe('The postcode must be a valid Australian postcode.');
    })->with([
        'an integer' => 2000,
        'a float holding a valid postcode' => 2000.0,
        'an array holding a valid postcode' => [['2000']],
        'an empty array' => [[]],
        'an object' => new stdClass,
        'true' => true,
        'false' => false,
        'a postcode value object' => Postcode::from('2000'),
    ]);
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (): void {
        expect(Validator::make(['postcode' => null], ['postcode' => [new AuPostcode]])->passes())->toBeTrue()
            ->and(Validator::make(['postcode' => null], ['postcode' => ['nullable', new AuPostcode]])->passes())->toBeTrue();
    });

    it('rejects a blank string, which the validator leaves to required', function (): void {
        $optional = Validator::make(['postcode' => '   '], ['postcode' => [new AuPostcode]]);
        $required = Validator::make(['postcode' => '   '], ['postcode' => ['required', new AuPostcode]]);

        expect(ruleFails(new AuPostcode, ''))->toBeTrue()
            ->and(ruleFails(new AuPostcode, '   '))->toBeTrue()
            ->and($optional->passes())->toBeTrue()
            ->and($required->fails())->toBeTrue()
            ->and($required->failed())->toBe(['postcode' => ['Required' => []]]);
    });
});
