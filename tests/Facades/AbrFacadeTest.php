<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;
use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Abr\Data\NameMatch;
use EastpointLabs\LaravelAu\Facades\Abr;
use Illuminate\Support\Facades\Http;

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
