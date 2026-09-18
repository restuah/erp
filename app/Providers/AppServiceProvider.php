<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationActivity;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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
        Vite::prefetch(concurrency: 3);

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Implicitly grant "Superadmin" role all permission checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Superadmin') ? true : null;
        });

        // Register Activity Logger Authentication subscriber
        Event::subscribe(LogAuthenticationActivity::class);
    }
}
