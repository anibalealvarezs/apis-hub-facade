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
            $table->dropColumn([
                'facebook_app_id',
                'facebook_app_secret',
                'facebook_user_token',
                'google_client_id',
                'google_client_secret',
                'google_refresh_token',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Restore legacy columns if needed for rollback
            $table->string('facebook_app_id')->nullable();
            $table->text('facebook_app_secret')->nullable();
            $table->text('facebook_user_token')->nullable();
            $table->string('google_client_id')->nullable();
            $table->text('google_client_secret')->nullable();
            $table->text('google_refresh_token')->nullable();
        });
    }
};
