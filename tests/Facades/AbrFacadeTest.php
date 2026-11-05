<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\AbrClient;
use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;
use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Abr\Data\NameMatch;
use EastpointLabs\LaravelAu\Abr\Testing\AbrFake;
use EastpointLabs\LaravelAu\Facades\Abr;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\ExpectationFailedException;

mutates(Abr::class);

it('looks up an ABN', function (): void {
    Http::fake(['abr.business.gov.au/json/AbnDetails.aspx*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);

    expect(Abr::abn('51824753556')?->toArray())
        ->toBe(AbnDetails::fromPayload(abrPayload('abn-active.jsonp'))->toArray());
});

it('looks up an ACN', function (): void {
    Http::fake(['abr.business.gov.au/json/AcnDetails.aspx*' => Http::response(fixtureContents('abr/acn-active.jsonp'))]);

    expect(Abr::acn('004 085 616')?->toArray())
        ->toBe(AbnDetails::fromPayload(abrPayload('acn-active.jsonp'))->toArray());
});

it('searches by name', function (): void {
    Http::fake(['abr.business.gov.au/json/MatchingNames.aspx*' => Http::response(fixtureContents('abr/names-search.jsonp'))]);

    expect(Abr::searchByName('Example Holdings'))
        ->toEqual(NameMatch::listFromPayload(abrPayload('names-search.jsonp')));
});

it('resolves whatever is bound to the lookup contract', function (): void {
    $replacement = Mockery::mock(LooksUpAbns::class);

    app()->instance(LooksUpAbns::class, $replacement);

    expect(Abr::getFacadeRoot())->toBe($replacement);
});

describe('fake()', function (): void {
    it('swaps in a fake that answers lookups without sending a request', function (): void {
        // Faked so that any request reaching the real client would be recorded rather than refused.
        Http::fake(['*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);
        $client = app(AbrClient::class);

        $fake = Abr::fake(['51824753556' => ['EntityName' => 'Example Holdings Pty Ltd']]);
        $details = Abr::abn('51 824 753 556');

        expect($fake)->toBeInstanceOf(AbrFake::class)
            ->and(app(LooksUpAbns::class))->toBe($fake)
            ->and(Abr::getFacadeRoot())->toBe($fake)
            ->and(app(AbrClient::class))->toBe($client)
            ->and($details?->entityName)->toBe('Example Holdings Pty Ltd')
            ->and($details?->status)->toBe('Active')
            ->and($details?->isActive())->toBeTrue()
            ->and(Abr::acn('004085616'))->toBeNull()
            ->and(Abr::searchByName('Example Holdings'))->toBe([]);

        Http::assertNothingSent();
    });

    it('passes the assertions through to the fake', function (): void {
        Abr::fake();

        Abr::assertNothingLookedUp();

        Abr::abn('51824753556');

        Abr::assertLookedUp('51 824 753 556');
        Abr::assertNotLookedUp('83914571673');
        Abr::assertLookupCount(1);

        expect(fn () => Abr::assertNothingLookedUp())->toThrow(ExpectationFailedException::class)
            ->and(fn () => Abr::assertLookedUp('83914571673'))->toThrow(ExpectationFailedException::class);
    });
});
