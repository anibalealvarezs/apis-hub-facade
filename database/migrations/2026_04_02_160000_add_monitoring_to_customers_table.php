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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('health_status')->default('offline');
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->integer('error_count')->default(0);
            $table->string('monitoring_token')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['health_status', 'last_heartbeat_at', 'error_count']);
        });
    }
};
