<?php

use App\Models\ApisHubRelease;
use Database\Seeders\ApisHubReleaseV1183Seeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new ApisHubReleaseV1183Seeder())->run();
    }

    public function down(): void
    {
        DB::transaction(function () {
            $release = ApisHubRelease::where('version_tag', 'v1.18.3')->first();
            if (!$release) return;
            if ($release->projects()->exists()) {
                throw new RuntimeException('Cannot remove v1.18.3 while projects reference it.');
            }
            $wasDefault = $release->is_default;
            $release->delete();
            if ($wasDefault && !ApisHubRelease::where('is_default', true)->exists()) {
                ApisHubRelease::where('version_tag', 'v1.18.2')->update(['is_default' => true]);
            }
        });
    }
};
