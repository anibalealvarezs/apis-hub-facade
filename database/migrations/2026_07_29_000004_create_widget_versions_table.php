<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_widget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dashboard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('custom_kpi_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('derived_metric_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('source_type', 50);
            $table->json('source_config')->nullable();
            $table->string('widget_type', 50);
            $table->json('controls')->nullable();
            $table->integer('grid_x');
            $table->integer('grid_y');
            $table->integer('grid_w');
            $table->integer('grid_h');
            $table->text('change_summary')->nullable();
            $table->timestamps();

            $table->index(['dashboard_widget_id', 'version_number']);
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_versions');
    }
};
