<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('derived_metric_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('derived_metric_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('calculation_type', 100)->nullable();
            $table->json('ast');
            $table->json('source_series');
            $table->string('output_granularity', 50)->nullable();
            $table->boolean('is_active');
            $table->text('change_summary')->nullable();
            $table->timestamps();

            $table->index(['derived_metric_id', 'version_number']);
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derived_metric_versions');
    }
};
