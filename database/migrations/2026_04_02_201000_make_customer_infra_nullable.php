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
            $table->string('git_repo')->nullable()->change();
            $table->string('git_branch')->nullable()->default('main')->change();
            $table->foreignId('server_id')->nullable()->change();
            $table->string('db_name')->nullable()->change();
            $table->string('db_user')->nullable()->change();
            $table->string('db_password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('git_repo')->nullable(false)->change();
            $table->string('git_branch')->nullable(false)->change();
            $table->foreignId('server_id')->nullable(false)->change();
            $table->string('db_name')->nullable(false)->change();
            $table->string('db_user')->nullable(false)->change();
            $table->string('db_password')->nullable(false)->change();
        });
    }
};
