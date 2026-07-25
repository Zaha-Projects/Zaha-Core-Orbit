<?php

namespace App\Providers;

use App\Models\ActivityEvaluation;
use App\Models\MonthlyActivity;
use App\Policies\MonthlyActivityEvaluationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        MonthlyActivity::class => MonthlyActivityEvaluationPolicy::class,
        ActivityEvaluation::class => MonthlyActivityEvaluationPolicy::class,
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
