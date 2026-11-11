<?php

namespace App\Modules\Movement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Services\MovementAvailabilityService;
use App\Modules\Movement\Support\MovementTime;
use Illuminate\Http\Request;

/**
 * JSON availability counts (plan §5.2, §8.2). Indicative only — final assignment is by the Movement
 * Department. Aggregates only (D7): every authenticated active user may call it.
 */
class AvailabilityController extends Controller
{
    public function summary(Request $request, MovementAvailabilityService $availability)
    {
        $this->authorize('viewOverview', Vehicle::class);

        $data = $request->validate([
            'required_arrival_at' => ['required', 'date_format:Y-m-d H:i'],
            'passenger_count' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        // Revision 5: window [required_arrival − indicative_window_hours, required_arrival).
        $until = MovementTime::toUtc($data['required_arrival_at']);
        $from = $until->subHours((int) config('movement.requests.indicative_window_hours', 3));

        return response()->json(['indicative' => true] + $availability->summary($from, $until, isset($data['passenger_count']) ? (int) $data['passenger_count'] : null));
    }
}
