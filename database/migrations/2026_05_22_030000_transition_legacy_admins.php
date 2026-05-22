<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear cache just in case
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Ensure super_admin role exists
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // Find legacy admins and assign role
        $legacyAdmins = DB::table('users')->where('is_admin', true)->get();
        
        foreach ($legacyAdmins as $adminData) {
            $user = User::find($adminData->id);
            if ($user) {
                // Assign role. Since teams is enabled, we need to assign it globally.
                // Spatie global roles when teams is true have team_id = null.
                $user->assignRole($superAdminRole);
            }
        }

        // Drop the legacy column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });

        // We won't remove the role because other parts of the app might use it,
        // but we could map super_admin back to is_admin if needed.
        $superAdmins = User::role('super_admin')->get();
        foreach ($superAdmins as $admin) {
            $admin->is_admin = true;
            $admin->save();
        }
    }
};
