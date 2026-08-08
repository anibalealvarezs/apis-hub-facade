<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('derived_metrics', function (Blueprint $table) {
            $table->string('format')->nullable()->after('calculation_type');
        });
    }

    public function down(): void
    {
        Schema::table('derived_metrics', function (Blueprint $table) {
            $table->dropColumn('format');
        });
    }
};
