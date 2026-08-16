<?php

declare(strict_types=1);

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::Planner->value)->after('email');
            // Set only for the matching role: a driver logs in as themselves,
            // a client logs in as their company. A planner is neither.
            $table->foreignId('driver_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->after('driver_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_id');
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn('role');
        });
    }
};
