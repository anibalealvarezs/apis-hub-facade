<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First rename the existing columns to preserve data safely
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->renameColumn('name', 'old_name');
            $table->renameColumn('description', 'old_description');
        });

        // Add the new JSON columns
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->json('name')->nullable();
            $table->json('description')->nullable();
        });

        // Migrate the data to the new JSON columns
        $plans = DB::table('subscription_plans')->get();
        foreach ($plans as $plan) {
            $nameJson = json_encode(['en' => $plan->old_name]);
            $descJson = $plan->old_description ? json_encode(['en' => $plan->old_description]) : null;

            DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update([
                    'name' => $nameJson,
                    'description' => $descJson,
                ]);
        }

        // Make 'name' non-nullable after filling data, and drop old columns
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->json('name')->nullable(false)->change();
            $table->dropColumn('old_name');
            $table->dropColumn('old_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To reverse, we do the same dance in reverse
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->renameColumn('name', 'old_json_name');
            $table->renameColumn('description', 'old_json_description');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->text('description')->nullable();
        });

        $plans = DB::table('subscription_plans')->get();
        foreach ($plans as $plan) {
            $nameArr = json_decode($plan->old_json_name, true);
            $descArr = $plan->old_json_description ? json_decode($plan->old_json_description, true) : null;

            DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update([
                    'name' => is_array($nameArr) ? ($nameArr['en'] ?? '') : '',
                    'description' => is_array($descArr) ? ($descArr['en'] ?? null) : null,
                ]);
        }

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dropColumn('old_json_name');
            $table->dropColumn('old_json_description');
        });
    }
};
