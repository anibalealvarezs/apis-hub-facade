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
        Schema::create('apis_hub_releases', function (Blueprint $table) {
            $table->id();
            $table->string('version_tag')->unique()->comment('Ej: v1.0.0, stable');
            $table->boolean('is_active')->default(true);
            $table->json('supported_channels')->nullable()->comment('Array de canales disponibles en esta versión');
            $table->json('config_schemas')->nullable()->comment('Estructuras JSON esperadas por cada canal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apis_hub_releases');
    }
};
