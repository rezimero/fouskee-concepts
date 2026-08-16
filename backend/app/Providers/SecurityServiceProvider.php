<?php

namespace App\Providers;

use App\Listeners\Security\RecordFailedLogin;
use App\Listeners\Security\RecordLogout;
use App\Listeners\Security\RecordSuccessfulLogin;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureApiRateLimiting();
        $this->configureSecurityEvents();
    }

    private function configureApiRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): array {
            $userKey = $request->user()
                ? 'user:'.$request->user()->getAuthIdentifier()
                : 'guest:'.($request->ip() ?? 'unknown');

            return [
                Limit::perMinute(180)->by($userKey),
                Limit::perMinute(600)->by(
                    'ip:'.($request->ip() ?? 'unknown'),
                ),
            ];
        });
    }

    private function configureSecurityEvents(): void
    {
        Event::listen(Login::class, RecordSuccessfulLogin::class);
        Event::listen(Failed::class, RecordFailedLogin::class);
        Event::listen(Logout::class, RecordLogout::class);
    }
}
