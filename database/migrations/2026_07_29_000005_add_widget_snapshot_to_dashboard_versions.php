<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_versions', function (Blueprint $table) {
            $table->json('widget_ids')->nullable()->after('controls');
            $table->json('widget_version_ids')->nullable()->after('widget_ids');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_versions', function (Blueprint $table) {
            $table->dropColumn(['widget_ids', 'widget_version_ids']);
        });
    }
};
