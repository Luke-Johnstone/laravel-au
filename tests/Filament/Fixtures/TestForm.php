<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Tests\Filament\Fixtures;

use Closure;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use LogicException;

/**
 * A bare Livewire form for testing fields. Give it its fields with TestForm::using() before
 * mounting it, and optionally the state to fill it with:
 *
 *     TestForm::using(fn () => [AbnInput::make('abn')]);
 *     Livewire::test(TestForm::class, ['state' => ['abn' => '51824753556']]);
 *
 * Calling save() validates and dehydrates the form, keeping the result in $saved.
 */
final class TestForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /**
     * Builds the form's fields. Livewire makes a new instance for every request, so the fields
     * are kept here rather than on the instance. Pest.php clears it before each Filament test.
     *
     * @var (Closure(): array<SchemaComponent>)|null
     */
    private static ?Closure $components = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null The validated, dehydrated state from the last save. */
    public ?array $saved = null;

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

    /**
     * @param  array<string, mixed>  $state
     */
    public function mount(array $state = []): void
    {
        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $components = self::$components ?? throw new LogicException('Call TestForm::using() to give the form its fields.');

        return $schema
            ->components($components())
            ->statePath('data');
    }

    public function save(): void
    {
        $this->saved = $this->form->getState();
    }

    public function render(): string
    {
        return <<<'BLADE'
            <form wire:submit="save">
                {{ $this->form }}
            </form>
            BLADE;
    }
}
