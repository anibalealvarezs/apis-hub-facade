<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new JSON column
        Schema::table('dashboard_public_views', function (Blueprint $table) {
            $table->json('asset_group_ids')->nullable()->after('asset_group_id');
        });

        // 2. Migrate existing data: scalar asset_group_id → [asset_group_id]
        DB::table('dashboard_public_views')
            ->whereNotNull('asset_group_id')
            ->eachById(function ($row) {
                DB::table('dashboard_public_views')
                    ->where('id', $row->id)
                    ->update(['asset_group_ids' => json_encode([(int) $row->asset_group_id])]);
            });

        // 3. Drop old FK column
        Schema::table('dashboard_public_views', function (Blueprint $table) {
            $table->dropForeign(['asset_group_id']);
            $table->dropColumn('asset_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_public_views', function (Blueprint $table) {
            $table->foreignId('asset_group_id')->nullable()->after('dashboard_id')->constrained('asset_groups')->nullOnDelete();
        });

        // Migrate back: take first element of JSON array
        DB::table('dashboard_public_views')
            ->whereNotNull('asset_group_ids')
            ->eachById(function ($row) {
                $ids = json_decode($row->asset_group_ids, true);
                $firstId = !empty($ids) ? $ids[0] : null;
                DB::table('dashboard_public_views')
                    ->where('id', $row->id)
                    ->update(['asset_group_id' => $firstId]);
            });

        Schema::table('dashboard_public_views', function (Blueprint $table) {
            $table->dropColumn('asset_group_ids');
        });
    }
};
