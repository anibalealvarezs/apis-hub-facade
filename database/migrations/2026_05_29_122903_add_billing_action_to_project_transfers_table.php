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
        Schema::table('project_transfers', function (Blueprint $table) {
            $table->string('billing_action')->nullable()->after('retain_access');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_transfers', function (Blueprint $table) {
            $table->dropColumn('billing_action');
        });
    }
};
