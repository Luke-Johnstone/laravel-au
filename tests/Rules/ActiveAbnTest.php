<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\Testing\AbrFake;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Exceptions\MissingGuidException;
use EastpointLabs\LaravelAu\Facades\Abr;
use EastpointLabs\LaravelAu\LaravelAuServiceProvider;
use EastpointLabs\LaravelAu\Rules\ActiveAbn;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

mutates(ActiveAbn::class, LaravelAuServiceProvider::class);

/**
 * Validate an "abn" field with the rule and return every message it failed with, so a test
 * also sees when a rule fails more than once.
 *
 * @return list<string>
 */
function activeAbnErrors(mixed $value, ActiveAbn|string $rule): array
{
    return Validator::make(['abn' => $value], ['abn' => [$rule]])->errors()->get('abn');
}

/**
 * Assert that exactly one warning was logged, with the exception's class as its only context
 * and neither the GUID nor the ABN anywhere in it.
 *
 * @param  class-string<Throwable>  $exception
 */
function expectOneLookupWarning(string $exception): void
{
    Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context = []) use ($exception): bool {
        expect(loggedText($message, $context))
            ->not->toContain('testing-guid')
            ->not->toContain('51824753556')
            ->not->toContain('51 824 753 556')
            ->and(array_values($context))->toBe([$exception]);

        return true;
    });
}

// Each way ABN Lookup can be unavailable, and the exception it raises.
dataset('ABN Lookup unavailable', [
    'ABN Lookup is unreachable' => [fn (): AbrFake => Abr::fake()->failing(), AbrException::class],
    'no GUID is set' => [fn () => config(['au.abr.guid' => null]), MissingGuidException::class],
]);

describe('input that is not looked up', function (): void {
    it('fails a value that is not a valid ABN with the ABN message, without a lookup', function (mixed $value): void {
        Abr::fake();

        expect(activeAbnErrors($value, new ActiveAbn))->toBe(['The abn must be a valid ABN.']);

        Abr::assertNothingLookedUp();
    })->with([
        'a bad checksum' => '51 824 753 557',
        'letters' => 'abc',
        'an integer holding a valid ABN' => 51824753556,
    ]);

    it('leaves null to required and nullable, without a lookup', function (): void {
        Abr::fake();

        expect(ruleFails(new ActiveAbn, null))->toBeFalse()
            ->and(Validator::make(['abn' => null], ['abn' => 'abn_active'])->passes())->toBeTrue();

        Abr::assertNothingLookedUp();
    });

    it('fails a blank string itself, without a lookup, though the validator leaves blanks to required', function (): void {
        Abr::fake();

        expect(ruleFails(new ActiveAbn, '   '))->toBeTrue();

        Abr::assertNothingLookedUp();
    });
});

describe('register status', function (): void {
    it('looks the ABN up once by its digits and passes it only when it is active', function (AbrFake $abr, array $errors): void {
        expect(activeAbnErrors('51 824 753 556', new ActiveAbn))->toBe($errors);

        $abr->assertLookedUp('51824753556');
        $abr->assertLookupCount(1);
    })->with([
        'active, and not registered for GST' => [fn (): AbrFake => Abr::fake(['51824753556' => []]), []],
        'active, in capitals' => [fn (): AbrFake => Abr::fake(['51824753556' => ['AbnStatus' => 'ACTIVE']]), []],
        'not found' => [fn (): AbrFake => Abr::fake()->notFound('51824753556'), ['The abn must be an active ABN.']],
        'cancelled' => [
            fn (): AbrFake => Abr::fake(['51824753556' => ['AbnStatus' => 'Cancelled']]),
            ['The abn must be an active ABN.'],
        ],
        'suppressed but active' => [fn (): AbrFake => Abr::fake()->suppressed('51824753556'), []],
        'suppressed and cancelled' => [
            fn (): AbrFake => Abr::fake()->suppressed('51824753556', 'Cancelled'),
            ['The abn must be an active ABN.'],
        ],
    ]);
});

describe('GST registration', function (): void {
    it('fails an active ABN that is not registered for GST, once it knows the ABN is active', function (ActiveAbn|string $rule): void {
        Abr::fake([
            '51824753556' => ['Gst' => '2000-07-01'],
            '83914571673' => [],
            '53004085616' => ['AbnStatus' => 'Cancelled'],
        ]);

        expect(activeAbnErrors('51 824 753 556', $rule))->toBe([])
            ->and(activeAbnErrors('83 914 571 673', $rule))->toBe(['The abn must be registered for GST.'])
            ->and(activeAbnErrors('53 004 085 616', $rule))->toBe(['The abn must be an active ABN.']);
    })->with([
        'requireGst()' => fn (): ActiveAbn => (new ActiveAbn)->requireGst(),
        'abn_active:gst' => 'abn_active:gst',
        'abn_active:gst in capitals, with spaces' => 'abn_active: GST ',
    ]);
});

describe('when ABN Lookup is unavailable', function (): void {
    it('fails closed and logs a warning naming only the exception', function (Closure $breakAbnLookup, string $exception): void {
        $breakAbnLookup();
        Log::spy();

        expect(activeAbnErrors('51 824 753 556', new ActiveAbn))
            ->toBe(['The abn could not be checked right now. Please try again.']);

        expectOneLookupWarning($exception);
        Http::assertNothingSent();
    })->with('ABN Lookup unavailable');

    it('passes when told to pass when unavailable, and still logs the warning', function (Closure $breakAbnLookup, string $exception): void {
        $breakAbnLookup();
        Log::spy();

        expect(activeAbnErrors('51 824 753 556', (new ActiveAbn)->passWhenUnavailable()))->toBe([]);

        expectOneLookupWarning($exception);
    })->with('ABN Lookup unavailable');

    it('still fails an ABN that is not active when told to pass when unavailable', function (): void {
        Abr::fake(['51824753556' => ['AbnStatus' => 'Cancelled']]);

        expect(activeAbnErrors('51 824 753 556', (new ActiveAbn)->passWhenUnavailable()))
            ->toBe(['The abn must be an active ABN.']);
    });

    it('lets an error that is not an ABN Lookup failure through', function (): void {
        Abr::fake()->preventStrayLookups();

        expect(fn () => activeAbnErrors('51 824 753 556', (new ActiveAbn)->passWhenUnavailable()))
            ->toThrow(function (RuntimeException $exception): void {
                expect($exception)->not->toBeInstanceOf(AbrException::class)
                    ->and($exception->getMessage())->toContain('stray lookups are prevented');
            });
    });
});

describe('the lookup it uses', function (): void {
    it('resolves ABN Lookup when validating, so a rule made before faking uses the fake', function (): void {
        $rule = new ActiveAbn;
        $abr = Abr::fake(['51824753556' => ['AbnStatus' => 'Cancelled']]);

        expect(activeAbnErrors('51 824 753 556', $rule))->toBe(['The abn must be an active ABN.']);

        $abr->assertLookedUp('51824753556');
    });

    it('uses the lookup it is given instead of the container\'s', function (): void {
        $bound = Abr::fake(['51824753556' => []]);
        $given = new AbrFake(['51824753556' => ['AbnStatus' => 'Cancelled']]);

        expect(activeAbnErrors('51 824 753 556', new ActiveAbn($given)))->toBe(['The abn must be an active ABN.']);

        $given->assertLookedUp('51824753556');
        $bound->assertNothingLookedUp();
    });

    it('sends one request for an ABN validated twice, through the client\'s cache', function (): void {
        Http::fake(['abr.business.gov.au/*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);

        expect(Validator::make(['abn' => '51 824 753 556'], ['abn' => [new ActiveAbn]])->passes())->toBeTrue()
            ->and(Validator::make(['abn' => '51 824 753 556'], ['abn' => [new ActiveAbn]])->passes())->toBeTrue();

        expectOneGetRequest('https://abr.business.gov.au/json/AbnDetails.aspx', [
            'abn' => '51824753556',
            'callback' => 'callback',
            'guid' => 'testing-guid',
        ]);
    });

    it('returns the same rule from its fluent setters', function (): void {
        $rule = new ActiveAbn;

        expect($rule->requireGst())->toBe($rule)
            ->and($rule->passWhenUnavailable())->toBe($rule);
    });
});

describe('the abn_active string rule', function (): void {
    it('gives the same result and messages as the rule object', function (string $input, Closure $setUpAbnLookup): void {
        $setUpAbnLookup();
        Log::spy();

        $object = Validator::make(['abn' => $input], ['abn' => [new ActiveAbn]]);
        $string = Validator::make(['abn' => $input], ['abn' => 'abn_active']);

        expect($string->passes())->toBe($object->passes())
            ->and($string->errors()->all())->toBe($object->errors()->all());
    })->with([
        'an active ABN' => ['51 824 753 556', fn (): AbrFake => Abr::fake(['51824753556' => []])],
        'a cancelled ABN' => ['51 824 753 556', fn (): AbrFake => Abr::fake(['51824753556' => ['AbnStatus' => 'Cancelled']])],
        'an invalid ABN' => ['51 824 753 557', fn (): AbrFake => Abr::fake()],
        'ABN Lookup is unreachable' => ['51 824 753 556', fn (): AbrFake => Abr::fake()->failing()],
    ]);

    it('throws for a parameter other than gst', function (string $rule): void {
        Abr::fake(['51824753556' => []]);

        expect(fn () => Validator::make(['abn' => '51 824 753 556'], ['abn' => $rule])->passes())
            ->toThrow(function (InvalidArgumentException $exception): void {
                expect($exception->getMessage())->toBe('The abn_active rule accepts only gst.');
            });
    })->with([
        'an unknown parameter' => 'abn_active:vat',
        'gst and an unknown parameter' => 'abn_active:gst,vat',
    ]);

    it('prefers the application\'s own message', function (Closure $customMessages): void {
        Abr::fake(['51824753556' => ['AbnStatus' => 'Cancelled']]);

        $validator = Validator::make(['abn' => '51 824 753 556'], ['abn' => 'abn_active'], $customMessages());

        expect($validator->errors()->get('abn'))->toBe(['Custom message for abn.']);
    })->with([
        'an inline message' => fn (): array => ['abn.abn_active' => 'Custom message for :attribute.'],
        'a custom line in the application\'s translations' => function (): array {
            app('translator')->addLines(['validation.custom.abn.abn_active' => 'Custom message for :attribute.'], 'en');

            return [];
        },
    ]);
});
