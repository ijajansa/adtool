<?php

namespace App\Providers;

use App\Listeners\UpdateLastLoginAt;
use App\Models\Business;
use App\Models\MetaConnection;
use App\Policies\BusinessPolicy;
use App\Policies\MetaConnectionPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        Event::listen(Login::class, UpdateLastLoginAt::class);
        Gate::policy(Business::class, BusinessPolicy::class);
        Gate::policy(MetaConnection::class, MetaConnectionPolicy::class);
    }
}
