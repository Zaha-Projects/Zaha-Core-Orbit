<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;

class TrackUserOperations
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $user = $request->user();
        if (! $user || ! $request->route()) {
            return $response;
        }

        $method = strtoupper($request->method());
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        $route = $request->route();
        $action = (string) ($route->getName() ?: $route->uri());
        $module = explode('.', $action)[1] ?? 'web';

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => $method,
            'module' => $module,
            'entity_type' => (string) ($route->parameterNames()[0] ?? 'route'),
            'entity_id' => (int) (is_scalar($route->parameter($route->parameterNames()[0] ?? '')) ? $route->parameter($route->parameterNames()[0] ?? '') : 0),
            'new_values' => [
                'route' => $action,
                'status_code' => $response->getStatusCode(),
                'at' => now()->toDateTimeString(),
            ],
        ]);

        return $response;
    }
}
