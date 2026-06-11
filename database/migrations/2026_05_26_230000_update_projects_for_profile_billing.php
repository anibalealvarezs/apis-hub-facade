<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\UserTier;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add tier and status to billing_profiles
        Schema::table('billing_profiles', function (Blueprint $table) {
            $table->string('tier')->default(UserTier::FREE->value)->after('type');
            $table->string('status')->default('active')->after('tier'); // 'active', 'suspended'
        });

        // 2. Add billing_profile_id to projects
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('billing_profile_id')->nullable()->after('user_id')->constrained('billing_profiles')->nullOnDelete();
        });

        // 3. Migrate any existing pivot relations to direct foreign keys on projects
        if (Schema::hasTable('billing_profile_project')) {
            $pivotData = DB::table('billing_profile_project')
                ->where('is_primary', true)
                ->where('status', 'approved')
                ->get();

            foreach ($pivotData as $pivot) {
                DB::table('projects')
                    ->where('id', $pivot->project_id)
                    ->update(['billing_profile_id' => $pivot->billing_profile_id]);
            }

            // Drop pivot table since relation is now strict One-to-Many
            Schema::dropIfExists('billing_profile_project');
        }

        // 4. Drop user-level tier
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add user-level tier
        Schema::table('users', function (Blueprint $table) {
            $table->string('tier')->default(UserTier::FREE->value)->after('email');
        });

        // 2. Re-create billing_profile_project pivot table
        Schema::create('billing_profile_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('pending');
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['billing_profile_id', 'project_id'], 'bp_proj_unique');
        });

        // 3. Migrate direct foreign keys back to pivot
        $projects = DB::table('projects')->whereNotNull('billing_profile_id')->get();
        foreach ($projects as $project) {
            DB::table('billing_profile_project')->insert([
                'billing_profile_id' => $project->billing_profile_id,
                'project_id' => $project->id,
                'is_primary' => true,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Drop columns from projects
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['billing_profile_id']);
            $table->dropColumn('billing_profile_id');
        });

        // 5. Drop columns from billing_profiles
        Schema::table('billing_profiles', function (Blueprint $table) {
            $table->dropColumn(['tier', 'status']);
        });
    }
};
