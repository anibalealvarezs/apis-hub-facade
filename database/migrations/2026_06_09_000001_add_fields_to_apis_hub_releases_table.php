<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apis_hub_releases', function (Blueprint $table) {
            $table->text('description')->nullable()->after('is_active');
            $table->text('changelog')->nullable()->after('description');
            $table->json('upgrade_commands')->nullable()->after('config_schemas')
                ->comment('Bash commands to execute on the remote instance after git checkout');
        });
    }

    public function down(): void
    {
        Schema::table('apis_hub_releases', function (Blueprint $table) {
            $table->dropColumn(['description', 'changelog', 'upgrade_commands']);
        });
    }
};
