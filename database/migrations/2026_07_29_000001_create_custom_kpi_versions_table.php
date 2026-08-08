<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_kpi_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_kpi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('calculation_type', 100)->nullable();
            $table->json('ast')->nullable();
            $table->json('filters')->nullable();
            $table->boolean('is_active');
            $table->text('change_summary')->nullable();
            $table->timestamps();

            $table->index(['custom_kpi_id', 'version_number']);
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_kpi_versions');
    }
};
