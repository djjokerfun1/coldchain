<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            // Devices retry on unacknowledged sends. This id makes duplicate
            // pings a no-op instead of a phantom extra event; Postgres treats
            // NULLs as distinct, so manually created events (no device) are unaffected.
            $table->string('external_event_id')->nullable();
            $table->decimal('latitude', 8, 5)->nullable();
            $table->decimal('longitude', 8, 5)->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['shipment_id', 'external_event_id']);
            $table->index(['shipment_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
