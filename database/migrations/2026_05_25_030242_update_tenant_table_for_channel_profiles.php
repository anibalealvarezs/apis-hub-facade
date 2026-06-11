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
            $columnsToDrop = ['google_refresh_token', 'facebook_user_token', 'google_user_id', 'facebook_user_id'];
            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('projects', $col)) {
                    $table->dropColumn($col);
                }
            }

            $table->foreignId('google_profile_id')->nullable()->constrained('channel_profiles')->nullOnDelete();
            $table->foreignId('facebook_profile_id')->nullable()->constrained('channel_profiles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['google_profile_id']);
            $table->dropForeign(['facebook_profile_id']);
            $table->dropColumn(['google_profile_id', 'facebook_profile_id']);

            $table->text('google_refresh_token')->nullable();
            $table->text('facebook_user_token')->nullable();
            $table->string('google_user_id')->nullable();
            $table->string('facebook_user_id')->nullable();
        });
    }
};
