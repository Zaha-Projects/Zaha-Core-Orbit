<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!app()->runningInConsole() && app()->environment('production')) {
            URL::forceScheme('https');
        }
        $spatieAliases = [
            'Spatie\\Permission\\Middlewares\\RoleMiddleware' => 'Spatie\\Permission\\Middleware\\RoleMiddleware',
            'Spatie\\Permission\\Middlewares\\PermissionMiddleware' => 'Spatie\\Permission\\Middleware\\PermissionMiddleware',
            'Spatie\\Permission\\Middlewares\\RoleOrPermissionMiddleware' => 'Spatie\\Permission\\Middleware\\RoleOrPermissionMiddleware',
        ];

        foreach ($spatieAliases as $expected => $actual) {
            if (!class_exists($expected) && class_exists($actual)) {
                class_alias($actual, $expected);
            }
        }

    }
}
