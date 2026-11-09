<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $locale = config('app.locale', 'id');
        Carbon::setLocale($locale);
        Number::useLocale($locale);

        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('operator', fn (User $user) => $user->isAdmin() || $user->isOperator());
        Gate::define('control-tracker', fn (User $user) => $user->canControl());
        Gate::define('manage-devices', fn (User $user) => $user->canControl());
        Gate::define('manage-users', fn (User $user) => $user->canManageUsers());
    }
}
