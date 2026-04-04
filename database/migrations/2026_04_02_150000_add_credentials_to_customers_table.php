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
        Schema::table('customers', function (Blueprint $table) {
            // Facebook Credentials
            $table->string('facebook_app_id')->nullable();
            $table->text('facebook_app_secret')->nullable();
            $table->text('facebook_user_token')->nullable();

            // Google Credentials
            $table->string('google_client_id')->nullable();
            $table->text('google_client_secret')->nullable();
            $table->text('google_refresh_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
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
};
