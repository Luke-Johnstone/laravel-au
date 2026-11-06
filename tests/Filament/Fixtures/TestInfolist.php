<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Tests\Filament\Fixtures;

use Closure;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use LogicException;

/**
 * A bare Livewire infolist for testing entries, showing one record. Give it its entries with
 * TestInfolist::using() before mounting it with the record:
 *
 *     TestInfolist::using(fn () => [AbnEntry::make('abn')]);
 *     Livewire::test(TestInfolist::class, ['record' => $record]);
 */
final class TestInfolist extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /**
     * Builds the infolist's entries. Livewire makes a new instance for every request, so the
     * entries are kept here rather than on the instance. Pest.php clears it before each
     * Filament test.
     *
     * @var (Closure(): array<SchemaComponent>)|null
     */
    private static ?Closure $components = null;

    public TestRecord $record;

    /**
     * @param  Closure(): array<SchemaComponent>  $components
     */
    public static function using(Closure $components): void
    {
        self::$components = $components;
    }

    public static function forgetComponents(): void
    {
        self::$components = null;
    }

    public function mount(TestRecord $record): void
    {
        $this->record = $record;
    }

    public function infolist(Schema $schema): Schema
    {
        $components = self::$components ?? throw new LogicException('Call TestInfolist::using() to give the infolist its entries.');

        return $schema
            ->record($this->record)
            ->components($components());
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                {{ $this->infolist }}
            </div>
            BLADE;
    }
}
