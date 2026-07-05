<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(function (): void {
                Route::domain(config('domains.public', 'anugerah3d.com'))
                    ->name('public.')
                    ->group(base_path('routes/public.php'));

                foreach (config('domains.public_aliases', ['www' => 'www.anugerah3d.com']) as $alias => $domain) {
                    Route::domain($domain)
                        ->name("public.{$alias}.")
                        ->group(base_path('routes/public.php'));
                }

                Route::domain(config('domains.admin', 'admin.anugerah3d.com'))
                    ->name('admin.')
                    ->group(base_path('routes/admin.php'));

                Route::domain(config('domains.agent', 'agent.anugerah3d.com'))
                    ->name('agent.')
                    ->group(base_path('routes/agent.php'));

                Route::domain(config('domains.customer', 'customer.anugerah3d.com'))
                    ->name('customer.')
                    ->group(base_path('routes/customer.php'));

                Route::group([], base_path('routes/web.php'));
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->routeIs('admin.*') ? '/login' : '/',
        );

        $middleware->redirectUsersTo(
            fn (Request $request): string => $request->routeIs('admin.*') ? route('admin.dashboard') : '/',
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
