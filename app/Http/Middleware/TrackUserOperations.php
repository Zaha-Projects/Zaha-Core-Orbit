<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        [$entityType, $entityId] = $this->resolveEntity($request);

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => $method,
            'module' => $module,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'new_values' => [
                'route' => $action,
                'status_code' => $response->getStatusCode(),
                'at' => now()->toDateTimeString(),
            ],
        ]);

        return $response;
    }

    /**
     * Route parameters are already model instances here (SubstituteBindings runs first), so the
     * key must be read from the model. Create routes have no parameter; their controller may
     * share the persisted aggregate through the "audit_entity" request attribute.
     *
     * @return array{0: string, 1: int}
     */
    private function resolveEntity(Request $request): array
    {
        $entity = $request->attributes->get('audit_entity');
        if ($entity instanceof Model && $entity->exists) {
            return [Str::snake(class_basename($entity)), (int) $entity->getKey()];
        }

        $route = $request->route();
        $name = $route->parameterNames()[0] ?? null;
        if ($name === null) {
            return ['route', 0];
        }

        $value = $route->parameter($name);
        if ($value instanceof Model) {
            return [$name, (int) $value->getKey()];
        }

        return [$name, is_numeric($value) ? (int) $value : 0];
    }
}
