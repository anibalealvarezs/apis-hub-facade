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
        Schema::rename('customers', 'projects');
        Schema::rename('customer_user', 'project_user');
        
        Schema::table('project_user', function (Blueprint $table) {
            $table->renameColumn('customer_id', 'project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_user', function (Blueprint $table) {
            $table->renameColumn('project_id', 'customer_id');
        });
        
        Schema::rename('projects', 'customers');
        Schema::rename('project_user', 'customer_user');
    }
};
