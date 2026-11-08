<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\LaravelAuServiceProvider;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Workbench\App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use Workbench\App\Filament\Resources\Suppliers\Pages\EditSupplier;
use Workbench\App\Models\Supplier;
use Workbench\App\Models\User;
use Workbench\App\Providers\WorkbenchServiceProvider;

pest()->use(WithWorkbench::class);

// The demo app isn't part of the package. Booting it covers the package's service provider, as
// an app installs it.
mutates(LaravelAuServiceProvider::class);

beforeEach(function (): void {
    // Build the demo's database as `composer build` does: migrating fresh runs its seeder.
    $this->artisan('migrate:fresh')->assertSuccessful();
});

it('opens the demo panel signed in, listing the seeded suppliers formatted', function (): void {
    // The demo signs its user in only in the local environment, which `composer serve` uses.
    $this->app->detectEnvironment(fn (): string => 'local');

    $this->get('/admin')->assertOk();
    $this->assertAuthenticated();

    // The list is sorted by name, so the first supplier by name is on the first page.
    $supplier = DB::table('suppliers')->orderBy('name')->first();

    $this->get('/admin/suppliers')
        ->assertOk()
        ->assertSee(Abn::from($supplier->abn)->format())
        ->assertSee(PhoneNumber::from($supplier->phone)->format())
        ->assertSeeText($supplier->state);
});

it('signs nobody in outside the local environment', function (): void {
    $this->get('/admin');

    $this->assertGuest();
});

it('keeps a browser session working after the demo is rebuilt', function (): void {
    // Only the array driver can be given session data before the first request.
    config(['session.driver' => 'array']);

    // Every build gives the demo user a new password hash, which no longer matches the one held
    // in a session from the last build.
    $this->actingAs(User::query()->firstOrFail())
        ->withSession(['password_hash_web' => 'hash from an earlier build'])
        ->get('/admin')
        ->assertOk();
});

describe('without an ABN Lookup GUID', function (): void {
    beforeEach(function (): void {
        // The test case sets a GUID, so boot the demo's provider again as `composer serve` does.
        config(['au.abr.guid' => '']);
        (new WorkbenchServiceProvider($this->app))->boot();

        $this->actingAs(User::query()->firstOrFail());
        Filament::setCurrentPanel('admin');
    });

    it('fills the business name from the ABN', function (): void {
        Livewire::test(CreateSupplier::class)
            ->set('data.abn', '51 824 753 556')
            ->assertSet('data.name', 'Example Holdings Pty Ltd');
    });

    it('only accepts an ABN that is active on the register when creating', function (): void {
        Livewire::test(CreateSupplier::class)
            ->set('data.abn', '83 914 571 673')
            ->call('create')
            ->assertHasErrors(['data.abn'])
            ->assertSee('The ABN must be an active ABN.')
            ->set('data.abn', '51 824 753 556')
            ->call('create')
            ->assertHasNoErrors(['data.abn']);
    });

    it('does not check a saved ABN again when editing', function (): void {
        // The seeded ABNs are generated, so they must stay away from ABN Lookup.
        Livewire::test(EditSupplier::class, ['record' => Supplier::query()->firstOrFail()->getKey()])
            ->call('save')
            ->assertHasNoErrors();
    });

    it('checks the postcode against the state', function (): void {
        Livewire::test(CreateSupplier::class)
            ->set('data.state', 'NSW')
            ->set('data.postcode', '3000')
            ->call('create')
            ->assertHasErrors(['data.postcode'])
            ->assertSee('The postcode must be in New South Wales.');
    });
});
