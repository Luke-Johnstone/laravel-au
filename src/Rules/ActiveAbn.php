<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Rules;

use Closure;
use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;
use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Support\Abn as AbnNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

/**
 * Validates that an ABN is active on the Australian Business Register, and optionally that it's
 * registered for GST (string rule: "abn_active", or "abn_active:gst").
 *
 * Each check looks the ABN up with ABN Lookup, so validation can make a network call;
 * successful lookups are cached. When ABN Lookup can't be reached the rule fails, unless
 * passWhenUnavailable() is called.
 */
final class ActiveAbn implements ValidationRule
{
    private bool $requiresGst = false;

    private bool $passesWhenUnavailable = false;

    /**
     * Without a client, the one bound to LooksUpAbns is used when validating, so Abr::fake()
     * applies even to a rule made before it.
     */
    public function __construct(private readonly ?LooksUpAbns $client = null) {}

    /**
     * Also require the ABN to be registered for GST.
     */
    public function requireGst(): static
    {
        $this->requiresGst = true;

        return $this;
    }

    /**
     * Pass when ABN Lookup can't be reached, rather than fail.
     */
    public function passWhenUnavailable(): static
    {
        $this->passesWhenUnavailable = true;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $abn = is_string($value) ? AbnNumber::tryFrom($value) : null;

        if ($abn === null) {
            $fail('au::validation.abn')->translate();

            return;
        }

        try {
            $details = $this->client()->abn($abn);
        } catch (AbrException $exception) {
            // The exception class only: no GUID and no ABN.
            Log::warning('An ABN could not be checked with ABN Lookup.', ['exception_class' => $exception::class]);

            if (! $this->passesWhenUnavailable) {
                $fail('au::validation.abn_unverifiable')->translate();
            }

            return;
        }

        $message = $this->failureMessage($details);

        if ($message !== null) {
            $fail($message)->translate();
        }
    }

    /**
     * The translation key for why the details fail, or null when they pass.
     */
    private function failureMessage(?AbnDetails $details): ?string
    {
        if ($details === null || ! $details->isActive()) {
            return 'au::validation.abn_active';
        }

        if ($this->requiresGst && ! $details->isGstRegistered()) {
            return 'au::validation.abn_gst';
        }

        return null;
    }

    private function client(): LooksUpAbns
    {
        return $this->client ?? app(LooksUpAbns::class);
    }
}
