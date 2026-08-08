<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('grid_layout')->nullable();
            $table->json('controls')->nullable();
            $table->boolean('is_public');
            $table->boolean('is_default');
            $table->text('change_summary')->nullable();
            $table->timestamps();

            $table->index(['dashboard_id', 'version_number']);
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_versions');
    }
};
