<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_kpi_versions', function (Blueprint $table) {
            $table->string('label')->nullable()->after('change_summary');
        });
        Schema::table('derived_metric_versions', function (Blueprint $table) {
            $table->string('label')->nullable()->after('change_summary');
        });
        Schema::table('dashboard_versions', function (Blueprint $table) {
            $table->string('label')->nullable()->after('change_summary');
        });
        Schema::table('widget_versions', function (Blueprint $table) {
            $table->string('label')->nullable()->after('change_summary');
        });
    }

    public function down(): void
    {
        Schema::table('custom_kpi_versions', fn (Blueprint $t) => $t->dropColumn('label'));
        Schema::table('derived_metric_versions', fn (Blueprint $t) => $t->dropColumn('label'));
        Schema::table('dashboard_versions', fn (Blueprint $t) => $t->dropColumn('label'));
        Schema::table('widget_versions', fn (Blueprint $t) => $t->dropColumn('label'));
    }
};
