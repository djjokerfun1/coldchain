<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source_partner_key')->nullable()->after('client_id');
            $table->string('external_reference')->nullable()->after('source_partner_key');
        });

        // A plain unique(source_partner_key, external_reference) would reject
        // every order created through the dashboard after the first one,
        // since both columns are null there. Partial index: only orders that
        // actually came from a partner webhook need the duplicate guard.
        DB::statement(
            'create unique index orders_partner_reference_unique
             on orders (source_partner_key, external_reference)
             where source_partner_key is not null'
        );
    }

    public function down(): void
    {
        DB::statement('drop index if exists orders_partner_reference_unique');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['source_partner_key', 'external_reference']);
        });
    }
};
