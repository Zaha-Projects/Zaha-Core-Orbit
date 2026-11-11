<?php

namespace App\Providers;

use App\Models\ActivityEvaluation;
use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Movement\Models\Driver as MovementDriver;
use App\Modules\Movement\Models\Location as MovementLocation;
use App\Modules\Movement\Models\Unavailability as MovementUnavailability;
use App\Modules\Movement\Models\Vehicle as MovementVehicle;
use App\Modules\Movement\Models\VehicleType as MovementVehicleType;
use App\Modules\Movement\Policies\DriverPolicy as MovementDriverPolicy;
use App\Modules\Movement\Policies\LocationPolicy as MovementLocationPolicy;
use App\Modules\Movement\Policies\UnavailabilityPolicy as MovementUnavailabilityPolicy;
use App\Modules\Movement\Policies\VehiclePolicy as MovementVehiclePolicy;
use App\Modules\Movement\Policies\VehicleTypePolicy as MovementVehicleTypePolicy;
use App\Policies\MonthlyActivityEvaluationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        MonthlyActivity::class => MonthlyActivityEvaluationPolicy::class,
        ActivityEvaluation::class => MonthlyActivityEvaluationPolicy::class,
        // Movement module (plan §5.2): permission-based policies, no role checks.
        MovementVehicleType::class => MovementVehicleTypePolicy::class,
        MovementVehicle::class => MovementVehiclePolicy::class,
        MovementDriver::class => MovementDriverPolicy::class,
        MovementUnavailability::class => MovementUnavailabilityPolicy::class,
        MovementLocation::class => MovementLocationPolicy::class,
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
