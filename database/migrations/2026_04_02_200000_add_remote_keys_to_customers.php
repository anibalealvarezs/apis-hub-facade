<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('remote_admin_api_key')->nullable()->after('monitoring_token');
            $table->text('remote_app_api_key')->nullable()->after('remote_admin_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['remote_admin_api_key', 'remote_app_api_key']);
        });
    }
};
