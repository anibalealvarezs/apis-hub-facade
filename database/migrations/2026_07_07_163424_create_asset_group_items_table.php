<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_group_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('asset_id');
            $table->timestamps();
            
            $table->unique(['asset_group_id', 'channel', 'asset_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_group_items');
    }
};
