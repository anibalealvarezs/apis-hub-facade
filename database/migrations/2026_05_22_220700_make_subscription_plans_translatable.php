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
        // First we read existing data
        $plans = DB::table('subscription_plans')->get();

        // Alter the table columns to JSON
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->json('name')->change();
            $table->json('description')->nullable()->change();
        });

        // Seed back the existing data using JSON structure
        foreach ($plans as $plan) {
            $nameJson = json_encode(['en' => $plan->name]);
            $descJson = $plan->description ? json_encode(['en' => $plan->description]) : null;

            DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update([
                    'name' => DB::raw("'$nameJson'"),
                    'description' => $descJson ? DB::raw("'$descJson'") : null,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Reversing JSON to string can be lossy and DB specific.
        // We'll just cast back to string. Data might remain as stringified JSON.
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
        });
    }
};
