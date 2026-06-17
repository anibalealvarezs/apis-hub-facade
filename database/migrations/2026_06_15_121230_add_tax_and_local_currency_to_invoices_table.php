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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->after('amount')->nullable();
            $table->decimal('tax_rate', 5, 2)->after('subtotal')->nullable();
            $table->decimal('tax_amount', 10, 2)->after('tax_rate')->nullable();
            
            $table->string('local_currency', 3)->after('currency')->nullable();
            $table->decimal('exchange_rate', 15, 6)->after('local_currency')->nullable();
            $table->foreignId('exchange_rate_id')->after('exchange_rate')->nullable()->constrained('exchange_rates')->nullOnDelete();
            $table->decimal('local_subtotal', 15, 2)->after('exchange_rate_id')->nullable();
            $table->decimal('local_tax_amount', 15, 2)->after('local_subtotal')->nullable();
            $table->decimal('local_total', 15, 2)->after('local_tax_amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['exchange_rate_id']);
            $table->dropColumn([
                'subtotal',
                'tax_rate',
                'tax_amount',
                'local_currency',
                'exchange_rate',
                'exchange_rate_id',
                'local_subtotal',
                'local_tax_amount',
                'local_total',
            ]);
        });
    }
};
