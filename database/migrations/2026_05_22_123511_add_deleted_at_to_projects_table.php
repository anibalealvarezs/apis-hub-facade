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
        // La columna deleted_at ya existía en la base de datos
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 
    }
};
