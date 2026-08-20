<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alert_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('alert_calculation_line_id')->nullable();
            $table->string('alert_name');
            $table->text('alert_description')->nullable();
            $table->string('source_type');
            $table->string('source_summary');
            $table->string('asset_summary');
            $table->json('ast_snapshot');
            $table->json('asset_filter_snapshot');
            $table->decimal('evaluated_value', 20, 6)->nullable();
            $table->string('threshold_type')->nullable(); // upper, lower
            $table->decimal('threshold_value', 20, 6)->nullable();
            $table->string('aggregation_method');
            $table->json('evaluation_window'); // { start, end }
            $table->string('status'); // triggered, ok, warning
            $table->text('warning_message')->nullable();
            $table->boolean('notified_ui')->default(false);
            $table->boolean('notified_email')->default(false);
            $table->timestamp('triggered_at');
            $table->timestamp('created_at')->nullable();

            // Foreign key for calculation line (nullable, survives deletion)
            $table->foreign('alert_calculation_line_id')
                ->references('id')
                ->on('alert_calculation_lines')
                ->nullOnDelete();

            // Idempotency: prevent duplicate callbacks for the same evaluation
            $table->unique(['alert_id', 'alert_calculation_line_id', 'triggered_at'], 'alert_logs_idempotency');

            // Fast 30-day pruning queries
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
    }
};
