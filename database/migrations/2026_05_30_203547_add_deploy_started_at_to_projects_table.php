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
        Schema::table('projects', function (Blueprint $table) {
            // Tracks when a full redeployment (full-deploy.sh) was initiated.
            // Cleared when the deploy completes or fails.
            $table->timestamp('deploy_started_at')->nullable()->after('last_deployed_at');

            // Tracks when a lightweight sync sequence (start-sync.sh) was last requested.
            // Used to show "sync in progress" feedback without polling the node.
            $table->timestamp('last_sync_started_at')->nullable()->after('deploy_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['deploy_started_at', 'last_sync_started_at']);
        });
    }
};
