<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
// keep existing imports...

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Register model policies here later (optional)
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Optionally gate some quick checks
        \Gate::define('manage-school', function ($user) {
            return $user->hasRole('super-admin');
        });

        \Gate::define('enter-scores', function ($user) {
            return $user->hasAnyRole(['super-admin','subject-teacher','class-teacher']);
        });
    }
}
