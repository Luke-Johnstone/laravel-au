<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Abr\Data;

use DateTimeImmutable;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;

/**
 * An ABN Lookup record for one ABN.
 *
 * For a suppressed ABN, where the holder has asked the register to hide their details, only
 * the ABN, its status and GST registration are known; the names and address are null.
 */
final readonly class AbnDetails
{
    /**
     * @param  list<string>  $businessNames
     */
    public function __construct(
        public Abn $abn,
        public string $status,
        public ?Acn $acn = null,
        public ?DateTimeImmutable $statusEffectiveFrom = null,
        public ?string $entityName = null,
        public ?string $entityTypeCode = null,
        public ?string $entityTypeName = null,
        public array $businessNames = [],
        public ?DateTimeImmutable $gstRegisteredFrom = null,
        public ?Postcode $postcode = null,
        public ?State $state = null,
    ) {}

    /**
     * Build from an AbnDetails.aspx or AcnDetails.aspx payload. Empty fields become null.
     *
     * @param  array<array-key, mixed>  $payload
     *
     * @throws AbrException when the payload has no valid ABN or no status
     */
    public static function fromPayload(array $payload): self
    {
        $abn = Abn::tryFrom(self::text($payload, 'Abn'));
        $status = self::text($payload, 'AbnStatus');

        if ($abn === null || $status === null) {
            throw AbrException::malformedResponse();
        }

        return new self(
            abn: $abn,
            status: $status,
            acn: Acn::tryFrom(self::text($payload, 'Acn')),
            statusEffectiveFrom: self::date($payload, 'AbnStatusEffectiveFrom'),
            entityName: self::text($payload, 'EntityName'),
            entityTypeCode: self::text($payload, 'EntityTypeCode'),
            entityTypeName: self::text($payload, 'EntityTypeName'),
            businessNames: self::businessNames($payload),
            gstRegisteredFrom: self::date($payload, 'Gst'),
            postcode: Postcode::tryFrom(self::text($payload, 'AddressPostcode')),
            state: State::fromLoose(self::text($payload, 'AddressState')),
        );
    }

    public function isActive(): bool
    {
        return strcasecmp($this->status, 'Active') === 0;
    }

    public function isGstRegistered(): bool
    {
        return $this->gstRegisteredFrom !== null;
    }

    /**
     * Whether the holder has had their details hidden: the register returns no entity name and
     * no business names.
     */
    public function isSuppressed(): bool
    {
        return $this->entityName === null && $this->businessNames === [];
    }

    /**
     * The name to show for the ABN: the first business name, else the entity name. Null for a
     * suppressed ABN, so callers must handle it.
     */
    public function displayName(): ?string
    {
        return $this->businessNames[0] ?? $this->entityName;
    }

    /**
     * @return array{
     *     abn: string,
     *     acn: ?string,
     *     status: string,
     *     statusEffectiveFrom: ?string,
     *     entityName: ?string,
     *     entityTypeCode: ?string,
     *     entityTypeName: ?string,
     *     businessNames: list<string>,
     *     gstRegisteredFrom: ?string,
     *     postcode: ?string,
     *     state: ?string,
     * }
     */
    public function toArray(): array
    {
        return [
            'abn' => $this->abn->value,
            'acn' => $this->acn?->value,
            'status' => $this->status,
            'statusEffectiveFrom' => $this->statusEffectiveFrom?->format('Y-m-d'),
            'entityName' => $this->entityName,
            'entityTypeCode' => $this->entityTypeCode,
            'entityTypeName' => $this->entityTypeName,
            'businessNames' => $this->businessNames,
            'gstRegisteredFrom' => $this->gstRegisteredFrom?->format('Y-m-d'),
            'postcode' => $this->postcode?->value,
            'state' => $this->state?->value,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    private static function text(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    /**
     * Parse a "Y-m-d" or ISO 8601 date. Empty, unparseable and impossible dates (2020-02-31)
     * become null.
     *
     * @param  array<array-key, mixed>  $payload
     */
    private static function date(array $payload, string $key): ?DateTimeImmutable
    {
        $value = self::text($payload, $key);

        if ($value === null) {
            return null;
        }

        // .NET sends up to 7 fractional digits; PHP reads at most 6.
        $value = (string) preg_replace('/(\.\d{6})\d+/', '$1', $value);

        foreach (['!Y-m-d', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s.u', 'Y-m-d\TH:i:s.uP'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);

            if ($date !== false && DateTimeImmutable::getLastErrors() === false) {
                return $date;
            }
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return list<string>
     */
    private static function businessNames(array $payload): array
    {
        $names = $payload['BusinessName'] ?? [];

        if (! is_array($names)) {
            return [];
        }

        return array_values(array_filter(
            $names,
            fn (mixed $name): bool => is_string($name) && trim($name) !== '',
        ));
    }
}
