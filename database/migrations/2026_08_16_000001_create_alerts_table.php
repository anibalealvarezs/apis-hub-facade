<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('source_type'); // metric, kpi, derived_metric
            $table->json('source_config'); // channel, metric key — same shape as widget source_config
            $table->json('ast'); // The AST tree — identical schema to CustomKpi.ast / DerivedMetric.ast
            $table->json('filters')->nullable(); // Date range offsets, groupBy, etc.
            $table->string('aggregation_method'); // latest, sum, avg, min, max
            $table->decimal('upper_limit', 20, 6)->nullable();
            $table->decimal('lower_limit', 20, 6)->nullable();
            $table->boolean('notify_ui')->default(true);
            $table->boolean('notify_email')->default(false);
            $table->string('schedule_type'); // daily, weekly, biweekly, monthly, once
            $table->json('schedule_config'); // { time, day_of_week, days_of_month, date }
            $table->timestamp('next_evaluation_at')->nullable();
            $table->timestamp('last_evaluated_at')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'is_active', 'next_evaluation_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
