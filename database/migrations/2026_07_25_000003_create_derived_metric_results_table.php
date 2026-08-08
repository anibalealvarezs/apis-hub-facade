<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('derived_metric_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('derived_metric_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('controls_hash', 64);
            $table->json('result');
            $table->timestamp('cached_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['derived_metric_id', 'controls_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derived_metric_results');
    }
};
