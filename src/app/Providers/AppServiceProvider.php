<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        if ($this->app->runningInConsole() && $this->app->environment('testing')) {
            set_error_handler(function ($severity, $message, $file, $line) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            });

            set_exception_handler(function (\Throwable $e) {
                $log = storage_path('logs/test_error.log');
                file_put_contents($log, (string) $e->__toString() . PHP_EOL . print_r($e->getTrace(), true));
                // Re-throw so PHPUnit can still handle it
                throw $e;
            });
        }
    }
}
