<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('billing_profiles', function (Blueprint $table) {
            $table->timestamp('current_cycle_starts_at')->nullable()->after('status');
            $table->timestamp('current_cycle_ends_at')->nullable()->after('current_cycle_starts_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_profiles', function (Blueprint $table) {
            $table->dropColumn(['current_cycle_starts_at', 'current_cycle_ends_at']);
        });
    }
};
