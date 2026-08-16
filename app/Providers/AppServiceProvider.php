<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Ordering\Models\Client;
use App\Domain\Ordering\Models\Order;
use App\Domain\Ordering\Models\Product;
use App\Domain\Shipping\Events\TelemetryRecorded;
use App\Domain\Shipping\Listeners\RecordTelemetryAuditEntry;
use App\Domain\Shipping\Listeners\UpdateShipmentPosition;
use App\Domain\Shipping\Models\Driver;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Models\Vehicle;
use App\Policies\ClientPolicy;
use App\Policies\DriverPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ShipmentPolicy;
use App\Policies\VehiclePolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        // Same reason as the factory guesser above: domain models live
        // outside App\Models, so Laravel's convention-based policy
        // discovery never finds them. Registered explicitly instead.
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Driver::class, DriverPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(Shipment::class, ShipmentPolicy::class);

        // Listeners live under Domain\Shipping instead of the conventional
        // app/Listeners, which is the only directory Laravel auto-discovers
        // events in — registered explicitly for the same reason as above.
        Event::listen(TelemetryRecorded::class, UpdateShipmentPosition::class);
        Event::listen(TelemetryRecorded::class, RecordTelemetryAuditEntry::class);
    }
}
