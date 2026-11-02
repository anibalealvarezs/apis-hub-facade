<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dashboard_id')->nullable()->constrained()->nullOnDelete();
            $table->string('draft_token', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('target_audience')->default('client'); // client, agency, internal, executive
            $table->string('account_mode')->default('asset_groups'); // standalone, asset_groups, blended
            $table->json('widgets');
            $table->json('grid_layout')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index(['project_id', 'draft_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_drafts');
    }
};
