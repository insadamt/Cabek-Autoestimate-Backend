<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('autoestimate-api', function (Request $request): Limit {
            $client = $request->user();

            $key = $client
                ? 'api-client:'.$client->getAuthIdentifier()
                : 'api-token:'.hash('sha256', (string) ($request->bearerToken() ?? $request->ip()));

            return Limit::perMinute(config('autoestimate.api_rate_limit_per_minute', 120))
                ->by($key);
        });
    }
}
