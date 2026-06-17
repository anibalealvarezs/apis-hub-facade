<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('support_tickets', 'billing_profile_id')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->foreignId('billing_profile_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['billing_profile_id']);
            $table->dropColumn('billing_profile_id');
        });
    }
};
