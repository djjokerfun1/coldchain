<?php

declare(strict_types=1);

use App\Integrations\Support\Enums\IntegrationMessageStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->unique();
            $table->string('channel');
            $table->string('partner_key')->nullable();
            $table->string('status')->default(IntegrationMessageStatus::Received->value);
            $table->jsonb('raw_payload');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_messages');
    }
};
