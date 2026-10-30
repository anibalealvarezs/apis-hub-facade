<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_deployment_logs', function (Blueprint $table) {
            $table->string('progress_stage')->nullable();
            $table->timestamp('progress_updated_at')->nullable();
        });
        Schema::table('apis_hub_releases', fn (Blueprint $table) => $table->json('upgrade_impact')->nullable());
        \Illuminate\Support\Facades\DB::table('apis_hub_releases')->where('version_tag', 'v1.18.3')->update([
            'upgrade_impact' => json_encode([['introduced_in' => '1.18.3', 'channel' => 'google_search_console', 'label' => 'Google Search Console']]),
        ]);
    }

    public function down(): void
    {
        Schema::table('project_deployment_logs', fn (Blueprint $table) => $table->dropColumn(['progress_stage', 'progress_updated_at']));
        Schema::table('apis_hub_releases', fn (Blueprint $table) => $table->dropColumn('upgrade_impact'));
    }
};
