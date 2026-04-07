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
            $table->text('public_api_key')->nullable()->change();
            $table->text('remote_app_api_key')->nullable()->change();
            $table->text('remote_admin_api_key')->nullable()->change();
            $table->text('facebook_user_token')->nullable()->change();
            $table->text('google_refresh_token')->nullable()->change();
            $table->text('db_password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('public_api_key', 255)->nullable()->change();
            $table->string('remote_app_api_key', 255)->nullable()->change();
            $table->string('remote_admin_api_key', 255)->nullable()->change();
            $table->string('facebook_user_token', 255)->nullable()->change();
            $table->string('google_refresh_token', 255)->nullable()->change();
            $table->string('db_password', 191)->nullable()->change();
        });
    }
};
