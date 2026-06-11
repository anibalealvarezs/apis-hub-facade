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
        Schema::create('billing_profile_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // Track who assigned it
            $table->timestamps();

            // A profile can only be mapped to a project once
            $table->unique(['billing_profile_id', 'project_id'], 'bp_proj_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_profile_project');
    }
};
