<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('partner_key');
            $table->string('partner_sku');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['partner_key', 'partner_sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_product_mappings');
    }
};
