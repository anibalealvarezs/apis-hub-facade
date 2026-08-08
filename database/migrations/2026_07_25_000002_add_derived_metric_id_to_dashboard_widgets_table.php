<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_widgets', function (Blueprint $table) {
            $table->foreignId('derived_metric_id')->nullable()->after('custom_kpi_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_widgets', function (Blueprint $table) {
            $table->dropForeign(['derived_metric_id']);
            $table->dropColumn('derived_metric_id');
        });
    }
};
