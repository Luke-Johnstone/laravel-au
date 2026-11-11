<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Workbench\Database\Factories\UserFactory;

/**
 * The demo app's user. The seeder creates one, and the panel signs it in automatically.
 *
 * It doesn't implement Filament's FilamentUser, so Filament only lets it into the panel in the
 * local environment, where the demo runs. Testbench loads this class on every boot, so it must
 * also load when Filament isn't installed.
 */
#[UseFactory(UserFactory::class)]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    public const string DEMO_EMAIL = 'demo@example.com';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
