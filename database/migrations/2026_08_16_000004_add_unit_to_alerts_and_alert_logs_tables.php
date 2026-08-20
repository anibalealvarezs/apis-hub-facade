<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->string('unit')->default('number')->after('lower_limit');
        });

        Schema::table('alert_logs', function (Blueprint $table) {
            $table->string('unit')->default('number')->after('threshold_value');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('alert_logs', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
