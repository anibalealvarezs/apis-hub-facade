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
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'project_id';

        Schema::table('model_has_roles', function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
        });

        Schema::table('model_has_permissions', function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'project_id';

        Schema::table('model_has_roles', function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable(false)->change();
        });

        Schema::table('model_has_permissions', function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable(false)->change();
        });
    }
};
