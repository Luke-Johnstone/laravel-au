<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\LaravelAuServiceProvider;
use EastpointLabs\LaravelAu\Rules\Abn;
use EastpointLabs\LaravelAu\Rules\AbnOrAcn;
use EastpointLabs\LaravelAu\Rules\Acn;
use EastpointLabs\LaravelAu\Rules\AuPhone;
use EastpointLabs\LaravelAu\Rules\AuPostcode;
use EastpointLabs\LaravelAu\Rules\Bsb;
use EastpointLabs\LaravelAu\Rules\StringRule;
use Illuminate\Support\Facades\Validator;

mutates(LaravelAuServiceProvider::class, StringRule::class);

// String rule, an input it rejects, and the English message.
dataset('string rules', [
    'abn' => ['abn', '51 824 753 557', 'The field must be a valid ABN.'],
    'acn' => ['acn', '000 000 018', 'The field must be a valid ACN.'],
    'abn_or_acn' => ['abn_or_acn', '5182475355', 'The field must be a valid ABN or ACN.'],
    'bsb' => ['bsb', '06200', 'The field must be a valid BSB.'],
    'au_phone' => ['au_phone', '0491 570 15a', 'The field must be a valid Australian phone number.'],
    'au_postcode' => ['au_postcode', '0100', 'The field must be a valid Australian postcode.'],
]);

// The same, with the rule object each string rule stands for.
dataset('string rules and rule objects', [
    'abn' => ['abn', fn (): Abn => new Abn, '51 824 753 557', 'The field must be a valid ABN.'],
    'acn' => ['acn', fn (): Acn => new Acn, '000 000 018', 'The field must be a valid ACN.'],
    'abn_or_acn' => ['abn_or_acn', fn (): AbnOrAcn => new AbnOrAcn, '5182475355', 'The field must be a valid ABN or ACN.'],
    'bsb' => ['bsb', fn (): Bsb => new Bsb, '06200', 'The field must be a valid BSB.'],
    'au_phone' => ['au_phone', fn (): AuPhone => new AuPhone, '0491 570 15a', 'The field must be a valid Australian phone number.'],
    'au_postcode' => ['au_postcode', fn (): AuPostcode => new AuPostcode, '0100', 'The field must be a valid Australian postcode.'],
]);

describe('package messages', function (): void {
    it('gives the same message from the string rule and the rule object', function (
        string $stringRule,
        Closure $rule,
        string $input,
        string $message,
    ): void {
        $string = Validator::make(['field' => $input], ['field' => $stringRule]);
        $object = Validator::make(['field' => $input], ['field' => [$rule()]]);

        expect($string->fails())->toBeTrue()
            ->and($string->errors()->first('field'))->toBe($message)
            ->and($object->fails())->toBeTrue()
            ->and($object->errors()->first('field'))->toBe($message);
    })->with('string rules and rule objects');

    it('names the state in the message from the string rule and the rule object', function (): void {
        $string = Validator::make(['field' => '3000'], ['field' => 'au_postcode:NSW']);
        $object = Validator::make(['field' => '3000'], ['field' => [new AuPostcode('NSW')]]);

        expect($string->errors()->first('field'))->toBe('The field must be in New South Wales.')
            ->and($object->errors()->first('field'))->toBe('The field must be in New South Wales.');
    });

    it('works alongside other rules in a pipe-delimited string', function (): void {
        $passing = Validator::make(['field' => '51 824 753 556'], ['field' => 'required|string|abn']);
        $failing = Validator::make(['field' => '51 824 753 557'], ['field' => 'required|string|abn']);

        expect($passing->passes())->toBeTrue()
            ->and($failing->errors()->first('field'))->toBe('The field must be a valid ABN.');
    });
});

describe('attribute names', function (): void {
    it('gives the package message for an attribute with an escaped dot', function (
        string $stringRule,
        Closure $rule,
        string $input,
        string $message,
    ): void {
        $expected = str_replace('The field', 'The user.number', $message);

        $string = Validator::make(['user.number' => $input], ['user\\.number' => $stringRule]);
        $object = Validator::make(['user.number' => $input], ['user\\.number' => [$rule()]]);

        expect($string->errors()->first('user.number'))->toBe($expected)
            ->and($object->errors()->first('user.number'))->toBe($expected);
    })->with('string rules and rule objects');

    it('gives each wildcard item its own message', function (): void {
        $validator = Validator::make(
            ['addresses' => [['postcode' => '2000'], ['postcode' => '3000'], ['postcode' => '0100']]],
            ['addresses.*.postcode' => 'au_postcode:NSW'],
        );

        expect($validator->errors()->has('addresses.0.postcode'))->toBeFalse()
            ->and($validator->errors()->first('addresses.1.postcode'))->toBe('The addresses.1.postcode must be in New South Wales.')
            ->and($validator->errors()->first('addresses.2.postcode'))->toBe('The addresses.2.postcode must be a valid Australian postcode.');
    });
});

describe('parameters', function (): void {
    it('matches phone types in any case and with spaces', function (): void {
        expect(Validator::make(['field' => '0491 570 156'], ['field' => 'au_phone:Mobile, Landline'])->passes())->toBeTrue()
            ->and(Validator::make(['field' => '1800 160 401'], ['field' => 'au_phone:MOBILE'])->fails())->toBeTrue();
    });

    it('throws for an unknown phone type without repeating it', function (): void {
        expect(fn () => Validator::make(['field' => '0491 570 156'], ['field' => 'au_phone:fax'])->passes())
            ->toThrow(function (InvalidArgumentException $exception): void {
                expect($exception->getMessage())->not->toContain('fax');
            });
    });

    it('throws for an unknown state without repeating it', function (): void {
        expect(fn () => Validator::make(['field' => '2000'], ['field' => 'au_postcode:Atlantis'])->passes())
            ->toThrow(function (InvalidArgumentException $exception): void {
                expect($exception->getMessage())->not->toContain('Atlantis');
            });
    });
});

describe('application overrides', function (): void {
    it('prefers an inline custom message', function (string $stringRule, string $input): void {
        $validator = Validator::make(
            ['field' => $input],
            ['field' => $stringRule],
            ["field.{$stringRule}" => 'Custom message for :attribute.'],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('field'))->toBe('Custom message for field.');
    })->with('string rules');

    it('prefers the application translation line', function (string $stringRule, string $input): void {
        app('translator')->addLines(["validation.{$stringRule}" => 'App message for :attribute.'], 'en');

        $validator = Validator::make(['field' => $input], ['field' => $stringRule]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('field'))->toBe('App message for field.');
    })->with('string rules');
});

describe('locale', function (): void {
    it('translates the message when validating, not when the package boots', function (
        string $stringRule,
        Closure $rule,
        string $input,
    ): void {
        app('translator')->addLines(["validation.{$stringRule}" => 'Localised message for :attribute.'], 'fr', 'au');
        app()->setLocale('fr');

        $string = Validator::make(['field' => $input], ['field' => $stringRule]);
        $object = Validator::make(['field' => $input], ['field' => [$rule()]]);

        expect($string->errors()->first('field'))->toBe('Localised message for field.')
            ->and($object->errors()->first('field'))->toBe('Localised message for field.');
    })->with('string rules and rule objects');

    it('translates the state message when validating', function (): void {
        app('translator')->addLines(['validation.au_postcode_state' => 'Localised :attribute in :state.'], 'fr', 'au');
        app()->setLocale('fr');

        $string = Validator::make(['field' => '3000'], ['field' => 'au_postcode:NSW']);
        $object = Validator::make(['field' => '3000'], ['field' => [new AuPostcode('NSW')]]);

        expect($string->errors()->first('field'))->toBe('Localised field in New South Wales.')
            ->and($object->errors()->first('field'))->toBe('Localised field in New South Wales.');
    });

    it('falls back to English for a locale with no translation', function (
        string $stringRule,
        Closure $rule,
        string $input,
        string $message,
    ): void {
        app()->setLocale('fr');

        $string = Validator::make(['field' => $input], ['field' => $stringRule]);
        $object = Validator::make(['field' => $input], ['field' => [$rule()]]);

        expect($string->errors()->first('field'))->toBe($message)
            ->and($object->errors()->first('field'))->toBe($message);
    })->with('string rules and rule objects');
});
