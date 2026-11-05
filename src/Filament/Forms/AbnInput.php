<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Forms;

use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Filament\Label;
use EastpointLabs\LaravelAu\Rules\Abn as AbnRule;
use EastpointLabs\LaravelAu\Support\Abn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Log;

/**
 * An ABN field, masked as "51 824 753 556" and saved as 11 digits. It can also look the ABN up
 * and fill in the business name.
 */
class AbnInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('abn'))
            ->validationAttribute(Label::unchangedInMessages())
            ->mask('99 999 999 999')
            ->rule(new AbnRule)
            ->formatStateUsing(static fn (mixed $state): mixed => is_string($state) ? (Abn::tryFrom($state)?->format() ?? $state) : $state)
            ->dehydrateStateUsing(static fn (mixed $state): mixed => is_string($state) ? (Abn::tryFrom($state)->value ?? $state) : $state);
    }

    /**
     * Look the ABN up when the field loses focus and, when ABN Lookup has a name for it, put the
     * name in another field. A failed lookup is logged and leaves the form as it is.
     */
    public function lookup(?string $fillField = null): static
    {
        return $this->live(onBlur: true)
            ->afterStateUpdated(static function (mixed $state, Set $set) use ($fillField): void {
                $abn = is_string($state) ? Abn::tryFrom($state) : null;

                if ($abn === null) {
                    return;
                }

                $name = self::lookUpName($abn);

                if ($fillField !== null && $name !== null) {
                    $set($fillField, $name);
                }
            });
    }

    private static function lookUpName(Abn $abn): ?string
    {
        try {
            return app(LooksUpAbns::class)->abn($abn)?->displayName();
        } catch (AbrException $exception) {
            // ABN Lookup exception messages never contain the GUID.
            Log::warning('ABN Lookup failed, so the form was not filled in.', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
