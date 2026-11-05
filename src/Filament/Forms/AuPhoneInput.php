<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Forms;

use EastpointLabs\LaravelAu\Filament\Label;
use EastpointLabs\LaravelAu\Rules\AuPhone;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\PhoneType;
use Filament\Forms\Components\TextInput;

/**
 * An Australian phone number field. It accepts mobiles and landlines by default, and saves in
 * E.164 (+61491570006) or as national digits (0491570006), as set in config/au.php.
 */
class AuPhoneInput extends TextInput
{
    /** @var list<PhoneType> */
    protected array $phoneTypes = [PhoneType::Mobile, PhoneType::Landline];

    protected bool $allowsServiceNumbers = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('phone'))
            // type="tel" brings up the phone keypad. tel() would also add a generic format check
            // whose message shows before the package's own.
            ->type('tel')
            ->rule(static fn (self $component): AuPhone => new AuPhone(...$component->getPhoneTypes()))
            ->formatStateUsing(static fn (mixed $state): mixed => is_string($state) ? (PhoneNumber::tryFrom($state)?->format() ?? $state) : $state)
            ->dehydrateStateUsing(static fn (mixed $state): mixed => is_string($state) ? self::storedValue($state) : $state);
    }

    public function mobileOnly(): static
    {
        $this->phoneTypes = [PhoneType::Mobile];

        return $this;
    }

    public function landlineOnly(): static
    {
        $this->phoneTypes = [PhoneType::Landline];

        return $this;
    }

    /**
     * Also accept 13, 1300 and 1800 numbers, whichever order this is called in.
     */
    public function allowServiceNumbers(): static
    {
        $this->allowsServiceNumbers = true;

        return $this;
    }

    /**
     * @return list<PhoneType>
     */
    public function getPhoneTypes(): array
    {
        return $this->allowsServiceNumbers
            ? [...$this->phoneTypes, PhoneType::Service]
            : $this->phoneTypes;
    }

    private static function storedValue(string $state): string
    {
        $phone = PhoneNumber::tryFrom($state);

        if ($phone === null) {
            return $state;
        }

        if (config('au.phone.store_as') === 'national') {
            return $phone->value;
        }

        // Service numbers can't be dialled from overseas, so they have no E.164 form.
        return $phone->toE164() ?? $phone->value;
    }
}
