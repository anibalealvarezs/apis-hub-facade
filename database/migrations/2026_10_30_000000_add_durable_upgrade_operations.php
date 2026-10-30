<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_deployment_logs', function (Blueprint $table) {
            $table->uuid('upgrade_operation_id')->nullable()->unique();
            $table->unsignedBigInteger('target_release_id')->nullable();
            $table->timestamp('remote_prepared_at')->nullable();
            $table->string('remote_output_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_deployment_logs', function (Blueprint $table) {
            $table->dropUnique(['upgrade_operation_id']);
            $table->dropColumn(['upgrade_operation_id', 'target_release_id', 'remote_prepared_at', 'remote_output_hash']);
        });
    }
};
