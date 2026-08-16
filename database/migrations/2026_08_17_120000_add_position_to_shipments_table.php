<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Denormalized so a dashboard listing active shipments doesn't
            // scan the tracking_events log for each row. Kept current by
            // UpdateShipmentPosition, the listener that reacts to every
            // recorded telemetry ping.
            $table->decimal('current_latitude', 8, 5)->nullable()->after('status');
            $table->decimal('current_longitude', 8, 5)->nullable()->after('current_latitude');
            $table->timestamp('last_ping_at')->nullable()->after('current_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['current_latitude', 'current_longitude', 'last_ping_at']);
        });
    }
};
