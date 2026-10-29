<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\JsonpDecoder;
use EastpointLabs\LaravelAu\Exceptions\AbrException;

mutates(JsonpDecoder::class);

dataset('decodable bodies', [
    'a callback wrapper' => [
        'callback({"Abn":"51824753556","Message":""})',
        ['Abn' => '51824753556', 'Message' => ''],
    ],
    'a wrapper with a trailing semicolon' => [
        'callback({"Abn":"51824753556","Message":""});',
        ['Abn' => '51824753556', 'Message' => ''],
    ],
    'surrounding whitespace and newlines' => [
        "\n  callback({\"Abn\":\"51824753556\",\"Message\":\"\"});  \r\n",
        ['Abn' => '51824753556', 'Message' => ''],
    ],
    'a different callback name' => [
        'jQuery123_456({"Abn":"53004085616","Message":""})',
        ['Abn' => '53004085616', 'Message' => ''],
    ],
    'a payload spread over several lines' => [
        "callback({\n    \"Abn\": \"83914571673\",\n    \"Message\": \"\"\n})\n",
        ['Abn' => '83914571673', 'Message' => ''],
    ],
    'parentheses inside a string value' => [
        'callback({"EntityName":"Example (Australia) Pty Ltd","Message":""})',
        ['EntityName' => 'Example (Australia) Pty Ltd', 'Message' => ''],
    ],
    'plain JSON' => [
        '{"Abn":"51824753556","Message":""}',
        ['Abn' => '51824753556', 'Message' => ''],
    ],
    'plain JSON with surrounding whitespace' => [
        "  {\"Abn\":\"51824753556\",\"Message\":\"\"}\n",
        ['Abn' => '51824753556', 'Message' => ''],
    ],
]);

dataset('malformed bodies', [
    'an empty string' => '',
    'whitespace only' => " \n\t ",
    'invalid JSON inside the wrapper' => 'callback({"Abn":})',
    'truncated JSON inside the wrapper' => 'callback({"Abn":"51824753556")',
    'an unclosed wrapper' => 'callback({"Abn":"51824753556"}',
    'an empty wrapper' => 'callback()',
    'an HTML error page' => '<html><body><h1>Service Unavailable</h1></body></html>',
    'plain text' => 'Service Unavailable',
    'a JSON string' => '"text"',
    'a JSON number' => '42',
    'a JSON boolean' => 'true',
    'JSON null' => 'null',
    'a wrapped JSON string' => 'callback("text")',
    'a wrapped JSON number' => 'callback(42)',
]);

dataset('abr fixtures', [
    'active ABN' => 'abn-active.jsonp',
    'cancelled ABN' => 'abn-cancelled.jsonp',
    'suppressed ABN' => 'abn-suppressed.jsonp',
    'ABN not found' => 'abn-not-found.jsonp',
    'active ACN' => 'acn-active.jsonp',
    'name search' => 'names-search.jsonp',
    'service error' => 'error.jsonp',
]);

describe('decode()', function (): void {
    it('unwraps and decodes a response body', function (string $body, array $expected): void {
        expect(JsonpDecoder::decode($body))->toBe($expected);
    })->with('decodable bodies');

    it('decodes every ABN Lookup fixture', function (string $fixture): void {
        $payload = JsonpDecoder::decode(fixtureContents("abr/{$fixture}"));

        expect($payload)->toBeArray()->toHaveKey('Message');
    })->with('abr fixtures');

    it('keeps the JSON types of each field', function (): void {
        $payload = JsonpDecoder::decode(fixtureContents('abr/abn-cancelled.jsonp'));

        expect($payload['Abn'])->toBe('83914571673')
            ->and($payload['BusinessName'])->toBe([])
            ->and($payload['Gst'])->toBeNull()
            ->and($payload['Message'])->toBe('');
    });

    it('decodes nested objects as associative arrays', function (): void {
        $payload = JsonpDecoder::decode(fixtureContents('abr/names-search.jsonp'));

        expect($payload['Names'])->toBeArray()->toHaveCount(4)
            ->and($payload['Names'][0])->toBe([
                'Abn' => '51824753556',
                'AbnStatus' => '0000000001',
                'IsCurrent' => true,
                'Name' => 'Example Holdings Pty Ltd',
                'NameType' => 'Entity Name',
                'Postcode' => '2600',
                'Score' => 100,
                'State' => 'ACT',
            ]);
    });

    it('throws a malformed response exception for a body it cannot decode', function (string $body): void {
        expect(fn () => JsonpDecoder::decode($body))
            ->toThrow(AbrException::class, AbrException::malformedResponse()->getMessage());
    })->with('malformed bodies');

    it('does not reveal the response body in the exception message', function (): void {
        $body = 'callback({"EntityName":"Example Holdings Pty Ltd",})';

        expect(fn () => JsonpDecoder::decode($body))->toThrow(function (AbrException $exception) use ($body): void {
            expect($exception->getMessage())
                ->not->toContain($body)
                ->not->toContain('Example Holdings Pty Ltd');
        });
    });
});
