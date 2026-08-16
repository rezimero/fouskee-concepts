<?php

namespace App\Providers;

use App\Services\Authentication\AuthenticateActiveUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(AuthenticateActiveUser $authenticate): void
    {
        Fortify::authenticateUsing(
            fn (Request $request) => $authenticate($request),
        );

        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request): Limit {
            $loginId = (string) $request->session()->get('login.id', 'guest');

            return Limit::perMinute(5)->by($loginId.'|'.$request->ip());
        });
    }
}
