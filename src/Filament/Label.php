<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament;

use Closure;
use Filament\Forms\Components\Field;

/**
 * Default labels for the package's fields, from the au::fields translations.
 *
 * @internal
 */
final class Label
{
    private function __construct() {}

    /**
     * A label that's translated when it's shown, so the current locale applies.
     *
     * @return Closure(): string
     */
    public static function for(string $key): Closure
    {
        return static function () use ($key): string {
            $label = __("au::fields.{$key}");

            return is_string($label) ? $label : $key;
        };
    }

    /**
     * Use the field's label as it is in validation messages. Filament lower-cases its first
     * letter, which would turn "ABN" into "aBN".
     *
     * @return Closure(Field): ?string
     */
    public static function unchangedInMessages(): Closure
    {
        return static function (Field $component): ?string {
            $label = $component->getLabel();

            return is_string($label) ? $label : null;
        };
    }
}
