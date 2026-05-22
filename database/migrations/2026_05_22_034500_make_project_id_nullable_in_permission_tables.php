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
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        Schema::table('model_has_roles', function (Blueprint $table) use ($teamForeignKey, $pivotRole, $modelMorphKey) {
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
            $table->unique([$teamForeignKey, $pivotRole, $modelMorphKey, 'model_type'], 'model_has_roles_unique');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) use ($teamForeignKey, $pivotPermission, $modelMorphKey) {
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
            $table->unique([$teamForeignKey, $pivotPermission, $modelMorphKey, 'model_type'], 'model_has_permissions_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'project_id';
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        Schema::table('model_has_roles', function (Blueprint $table) use ($teamForeignKey, $pivotRole, $modelMorphKey) {
            $table->dropUnique('model_has_roles_unique');
            $table->unsignedBigInteger($teamForeignKey)->nullable(false)->change();
            $table->primary([$teamForeignKey, $pivotRole, $modelMorphKey, 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) use ($teamForeignKey, $pivotPermission, $modelMorphKey) {
            $table->dropUnique('model_has_permissions_unique');
            $table->unsignedBigInteger($teamForeignKey)->nullable(false)->change();
            $table->primary([$teamForeignKey, $pivotPermission, $modelMorphKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });
    }
};
