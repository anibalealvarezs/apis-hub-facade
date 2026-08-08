<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('derived_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('calculation_type')->nullable(); // e.g. "blend" — for future extensibility
            $table->json('ast'); // AST formula tree referencing source keys or other derived metrics
            $table->json('source_series'); // Array of source series specifications
            $table->string('output_granularity')->nullable(); // NULL = dynamic, value = fixed (daily, weekly, etc.)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['project_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derived_metrics');
    }
};
