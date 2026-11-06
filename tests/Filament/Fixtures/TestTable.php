<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Tests\Filament\Fixtures;

use Closure;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\Column;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use LogicException;

/**
 * A bare Livewire table for testing columns. It lists every TestRecord, or every record of the
 * model it's mounted with. Give it its columns with TestTable::using() before mounting it:
 *
 *     TestTable::using(fn () => [AbnColumn::make('abn')]);
 *     Livewire::test(TestTable::class, ['recordClass' => CastTestRecord::class]);
 */
final class TestTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    /**
     * Builds the table's columns. Livewire makes a new instance for every request, so the
     * columns are kept here rather than on the instance. Pest.php clears it before each
     * Filament test.
     *
     * @var (Closure(): array<Column>)|null
     */
    private static ?Closure $columns = null;

    /** @var class-string<TestRecord> */
    public string $recordClass = TestRecord::class;

    /**
     * @param  Closure(): array<Column>  $columns
     */
    public static function using(Closure $columns): void
    {
        self::$columns = $columns;
    }

    public static function forgetColumns(): void
    {
        self::$columns = null;
    }

    /**
     * @param  class-string<TestRecord>  $recordClass
     */
    public function mount(string $recordClass = TestRecord::class): void
    {
        $this->recordClass = $recordClass;
    }

    public function table(Table $table): Table
    {
        $columns = self::$columns ?? throw new LogicException('Call TestTable::using() to give the table its columns.');

        return $table
            ->query($this->recordClass::query())
            ->columns($columns());
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                {{ $this->table }}
            </div>
            BLADE;
    }
}
