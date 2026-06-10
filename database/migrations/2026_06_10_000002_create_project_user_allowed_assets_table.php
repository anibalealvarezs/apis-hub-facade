<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_user_allowed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->json('allowed_assets')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_user_allowed_assets');
    }
};
