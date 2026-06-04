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
        Schema::table('custom_kpis', function (Blueprint $table) {
            $table->string('calculation_type')->nullable()->after('description');
            $table->json('filters')->nullable()->after('ast');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_kpis', function (Blueprint $table) {
            $table->dropColumn(['calculation_type', 'filters']);
        });
    }
};
