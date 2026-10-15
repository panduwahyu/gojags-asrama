<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by('login:'.$email.'|'.$request->ip()),
                Limit::perMinute((int) config('asrama.login_attempts_per_ip'))->by('login-ip:'.$request->ip()),
            ];
        });

        $this->routes(function () {
            Route::middleware(['web', 'cache.headers:no_store'])
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')->group(base_path('routes/web.php'));
        });
    }
}
