<?php

declare(strict_types=1);

namespace Workbench\App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Workbench\App\Models\User;

/**
 * Signs the seeded demo user in to the panel, so the demo opens without a login page. It only
 * does so in the local environment.
 */
final class SignInDemoUser
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isLocal() && Filament::auth()->guest()) {
            $user = User::query()->where('email', User::DEMO_EMAIL)->first();

            if ($user !== null) {
                Filament::auth()->login($user);
            }
        }

        return $next($request);
    }
}
