<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\Testing\AbrFake;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Facades\Abr;
use EastpointLabs\LaravelAu\Filament\Forms\AbnInput;
use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

mutates(AbnInput::class, Label::class);

/**
 * An ABN field that looks up the ABN, beside a business name someone has already typed.
 *
 * @return Closure(): array<TextInput>
 */
function abnWithBusinessName(?string $fillField = 'business_name'): Closure
{
    return fn (): array => [
        AbnInput::make('abn')->lookup($fillField),
        TextInput::make('business_name'),
    ];
}

describe('with only make()', function (): void {
    it('is a masked text input labelled ABN, whatever its name', function (): void {
        $field = formField(testForm(fn (): array => [AbnInput::make('supplier_abn')]), 'supplier_abn');

        expect($field)->toBeInstanceOf(TextInput::class)
            ->and($field->getLabel())->toBe('ABN')
            ->and($field->getMask())->toBe('99 999 999 999');
    });

    it('rejects an invalid ABN, naming the field by its label as written', function (?string $label, string $message): void {
        $form = testForm(fn (): array => [$label === null ? AbnInput::make('abn') : AbnInput::make('abn')->label($label)])
            ->fillForm(['abn' => '51 824 753 557'])
            ->call('save');

        expect(formField($form, 'abn')->getLabel())->toBe($label ?? 'ABN')
            ->and($form->errors()->get('data.abn'))->toBe([$message])
            ->and($form->get('saved'))->toBeNull();
    })->with([
        'the default label' => [null, 'The ABN must be a valid ABN.'],
        'a label of your own' => ['Supplier ABN', 'The Supplier ABN must be a valid ABN.'],
    ]);

    it('saves a valid ABN as its 11 digits', function (string $input): void {
        $form = testForm(fn (): array => [AbnInput::make('abn')])
            ->fillForm(['abn' => $input])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($form->get('saved'))->toBe(['abn' => '51824753556']);
    })->with([
        'as the mask writes it' => '51 824 753 556',
        'as digits' => '51824753556',
    ]);

    it('shows a stored ABN formatted, and saves it back as digits', function (): void {
        $form = testForm(fn (): array => [AbnInput::make('abn')], ['abn' => '53004085616'])
            ->assertSchemaStateSet(['abn' => '53 004 085 616'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($form->get('saved'))->toBe(['abn' => '53004085616']);
    });

    it('shows a stored value that is not a valid ABN as it is', function (): void {
        testForm(fn (): array => [AbnInput::make('abn')], ['abn' => '51824753557'])
            ->assertSchemaStateSet(['abn' => '51824753557']);
    });

    it('saves an empty optional field as empty', function (?string $input): void {
        $form = testForm(fn (): array => [AbnInput::make('abn')])
            ->fillForm(['abn' => $input])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($form->get('saved'))->toHaveKey('abn')
            ->and($form->get('saved.abn'))->toBeEmpty();
    })->with([
        'an empty string' => [''],
        'null' => [null],
    ]);

    it('looks nothing up', function (): void {
        Abr::fake(['51824753556' => ['EntityName' => 'Example Holdings Pty Ltd']]);

        $form = testForm(fn (): array => [AbnInput::make('abn')])
            ->fillForm(['abn' => '51 824 753 556']);

        expect(formField($form, 'abn')->isLive())->toBeFalse();

        Abr::assertNothingLookedUp();
    });
});

describe('lookup()', function (): void {
    it('fills the other field with the name ABN Lookup finds', function (array $fields, string $name): void {
        Abr::fake(['51824753556' => $fields]);

        $form = testForm(abnWithBusinessName(), ['business_name' => 'Typed By Hand Pty Ltd']);

        expect(formField($form, 'abn')->isLiveOnBlur())->toBeTrue();

        $form->fillForm(['abn' => '51 824 753 556'])
            ->assertSchemaStateSet(['abn' => '51 824 753 556', 'business_name' => $name]);

        Abr::assertLookedUp('51824753556');
        Abr::assertLookupCount(1);
    })->with([
        'the entity name' => [
            ['EntityName' => 'Example Holdings Pty Ltd'],
            'Example Holdings Pty Ltd',
        ],
        'a business name over the entity name' => [
            ['EntityName' => 'Example Holdings Pty Ltd', 'BusinessName' => ['Example Trading Co']],
            'Example Trading Co',
        ],
    ]);

    it('leaves the other field alone when ABN Lookup has no name for the ABN', function (AbrFake $abr): void {
        testForm(abnWithBusinessName(), ['business_name' => 'Typed By Hand Pty Ltd'])
            ->fillForm(['abn' => '51 824 753 556'])
            ->assertSchemaStateSet(['business_name' => 'Typed By Hand Pty Ltd'])
            ->call('save')
            ->assertHasNoFormErrors();

        $abr->assertLookedUp('51824753556');
    })->with([
        'an ABN that is not found' => fn (): AbrFake => Abr::fake()->notFound('51824753556'),
        'a suppressed ABN' => fn (): AbrFake => Abr::fake()->suppressed('51824753556'),
    ]);

    it('logs a warning without the GUID or the ABN when ABN Lookup fails, and the form carries on', function (Closure $breakAbnLookup): void {
        $breakAbnLookup();
        Log::spy();

        $form = testForm(abnWithBusinessName(), ['business_name' => 'Typed By Hand Pty Ltd'])
            ->fillForm(['abn' => '51 824 753 556'])
            ->assertSchemaStateSet(['business_name' => 'Typed By Hand Pty Ltd'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($form->get('saved'))->toBe(['abn' => '51824753556', 'business_name' => 'Typed By Hand Pty Ltd']);

        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context = []): bool {
            expect(loggedText($message, $context))
                ->not->toContain('testing-guid')
                ->not->toContain('51824753556')
                ->not->toContain('51 824 753 556');

            return true;
        });
        Http::assertNothingSent();
    })->with([
        'ABN Lookup is unreachable' => [fn (): AbrFake => Abr::fake()->failing()],
        'no GUID is set' => [fn () => config(['au.abr.guid' => null])],
    ]);

    it('does not look up a value that is not a valid ABN', function (string $input): void {
        Abr::fake();

        testForm(abnWithBusinessName(), ['business_name' => 'Typed By Hand Pty Ltd'])
            ->fillForm(['abn' => $input])
            ->assertSchemaStateSet(['business_name' => 'Typed By Hand Pty Ltd']);

        Abr::assertNothingLookedUp();
    })->with([
        'a bad checksum' => '51 824 753 557',
        'an empty string' => '',
    ]);

    it('looks the ABN up without filling anything when given no field to fill', function (): void {
        Abr::fake(['51824753556' => ['EntityName' => 'Example Holdings Pty Ltd']]);

        $form = testForm(abnWithBusinessName(fillField: null), ['business_name' => 'Typed By Hand Pty Ltd']);

        expect(formField($form, 'abn')->isLiveOnBlur())->toBeTrue();

        $form->fillForm(['abn' => '51 824 753 556'])
            ->assertSchemaStateSet(['abn' => '51 824 753 556', 'business_name' => 'Typed By Hand Pty Ltd']);

        Abr::assertLookedUp('51824753556');
    });

    it('lets an error that is not an ABN Lookup failure through', function (): void {
        Abr::fake()->preventStrayLookups();

        $form = testForm(abnWithBusinessName());

        expect(fn () => $form->fillForm(['abn' => '51 824 753 556']))
            ->toThrow(function (RuntimeException $exception): void {
                expect($exception)->not->toBeInstanceOf(AbrException::class)
                    ->and($exception->getMessage())->toContain('stray lookups are prevented');
            });
    });
});
