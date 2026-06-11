<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('grid_layout')->nullable();
            $table->json('controls')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_kpi_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('source_type'); // kpi, metric, entity
            $table->json('source_config')->nullable();
            $table->string('widget_type'); // tile, line_chart, bar_chart, table, gauge, sparkline, anomaly_list
            $table->json('controls')->nullable();
            $table->integer('grid_x')->default(0);
            $table->integer('grid_y')->default(0);
            $table->integer('grid_w')->default(4);
            $table->integer('grid_h')->default(2);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dashboard_user', function (Blueprint $table) {
            $table->foreignId('dashboard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['dashboard_id', 'user_id']);
        });

        Schema::create('kpi_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_kpi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('controls_hash', 64);
            $table->json('result');
            $table->timestamp('cached_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['custom_kpi_id', 'controls_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_results');
        Schema::dropIfExists('dashboard_user');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboards');
    }
};
