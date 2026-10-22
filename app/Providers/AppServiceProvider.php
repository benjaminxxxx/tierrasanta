<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // API de los libros Excel con macro: 30 peticiones por minuto por clave (o IP), con el error que entiende la macro
        \Illuminate\Support\Facades\RateLimiter::for('libros-excel', fn(\Illuminate\Http\Request $r) =>
            \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by(sha1((string) ($r->bearerToken() ?? $r->ip())))
                ->response(fn() => \App\Http\Middleware\LibroExcelApiAcceso::error(429, 'DEMASIADOS_INTENTOS',
                    'Demasiadas peticiones seguidas. Espera un minuto y vuelve a intentar.')));

        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
