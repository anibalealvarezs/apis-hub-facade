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
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('annual_price', 10, 2)->default(0)->after('price');
            $table->string('stripe_annual_price_id')->nullable()->after('stripe_price_id');
            $table->string('paypal_annual_plan_id')->nullable()->after('paypal_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['annual_price', 'stripe_annual_price_id', 'paypal_annual_plan_id']);
        });
    }
};
