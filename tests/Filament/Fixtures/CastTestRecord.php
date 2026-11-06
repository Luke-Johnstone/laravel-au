<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Tests\Filament\Fixtures;

use EastpointLabs\LaravelAu\Casts\AsAbn;
use EastpointLabs\LaravelAu\Casts\AsAcn;
use EastpointLabs\LaravelAu\Casts\AsBsb;
use EastpointLabs\LaravelAu\Casts\AsPhoneNumber;
use EastpointLabs\LaravelAu\Support\State;

/**
 * The same rows as TestRecord, read as value objects and a State through the package's casts
 * and Laravel's enum cast. Reading a stored value that isn't valid throws, as the casts do.
 */
final class CastTestRecord extends TestRecord
{
    /**
     * A record that's already stored, read again through the casts.
     */
    public static function of(TestRecord $record): self
    {
        return self::query()->findOrFail($record->getKey());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abn' => AsAbn::class,
            'acn' => AsAcn::class,
            'bsb' => AsBsb::class,
            'phone' => AsPhoneNumber::class,
            'state' => State::class,
        ];
    }
}
