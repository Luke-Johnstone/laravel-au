<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\Supplier;
use Workbench\App\Models\User;

/**
 * The demo user, who the panel signs in automatically, and some suppliers to list.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Demo User',
            'email' => User::DEMO_EMAIL,
        ]);

        Supplier::factory()->count(12)->create();
    }
}
