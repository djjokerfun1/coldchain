<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        // Domain models live under App\Domain\<Context>\Models instead of
        // App\Models, so Laravel's default App\Models -> Database\Factories
        // guess misses them. Everything else (e.g. App\Models\User) still
        // needs the default convention, so fall back to it explicitly.
        Factory::guessFactoryNamesUsing(function (string $model): string {
            if (preg_match('/^App\\\\Domain\\\\(.+)\\\\Models\\\\(.+)$/', $model, $matches) === 1) {
                return "Database\\Factories\\Domain\\{$matches[1]}\\{$matches[2]}Factory";
            }

            return 'Database\\Factories\\'.Str::after($model, 'App\\Models\\').'Factory';
        });
    }
}
