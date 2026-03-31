<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];

    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user) {
            if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
                return true;
            }

            return null;
        });
    }
}
