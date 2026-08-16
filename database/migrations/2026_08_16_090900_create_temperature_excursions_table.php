<?php

declare(strict_types=1);

use App\Domain\ColdChain\Enums\ExcursionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temperature_excursions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(ExcursionStatus::Candidate->value);
            $table->decimal('min_celsius', 5, 2);
            $table->decimal('max_celsius', 5, 2);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temperature_excursions');
    }
};
