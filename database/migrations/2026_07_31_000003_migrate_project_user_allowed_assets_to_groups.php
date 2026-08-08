<?php

use App\Models\AssetGroup;
use App\Models\AssetGroupItem;
use App\Models\Project;
use App\Models\ProjectUserAssetGroup;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_user_allowed_assets')) {
            return;
        }

        $rows = DB::table('project_user_allowed_assets')->get();

        if (! Schema::hasTable('project_user_allowed_assets_snapshot')) {
            Schema::create('project_user_allowed_assets_snapshot', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('user_id');
                $table->string('channel');
                $table->json('allowed_assets')->nullable();
                $table->timestamps();
            });
        }

        if ($rows->isEmpty()) {
            Schema::dropIfExists('project_user_allowed_assets');

            return;
        }

        $service = app(\App\Services\CollaboratorAssetAccessService::class);

        foreach ($rows->groupBy(fn ($r) => $r->project_id . ':' . $r->user_id) as $key => $userRows) {
            [$projectId, $userId] = explode(':', $key);
            $projectId = (int) $projectId;
            $userId = (int) $userId;

            DB::table('project_user_allowed_assets_snapshot')->insert(
                $userRows->map(fn ($r) => [
                    'project_id' => $r->project_id,
                    'user_id' => $r->user_id,
                    'channel' => $r->channel,
                    'allowed_assets' => $r->allowed_assets,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ])->toArray()
            );

            $restricted = $userRows->filter(fn ($r) => $r->allowed_assets !== null);
            $allowAll = $userRows->filter(fn ($r) => $r->allowed_assets === null);

            if ($restricted->isEmpty()) {
                $this->setUnrestricted($projectId, $userId, true);

                continue;
            }

            $project = Project::find($projectId);
            $enabledChannels = $project ? $this->enabledChannels($project) : [];
            $covered = $userRows->pluck('channel')->map(fn ($c) => (string) $c)->all();

            $fullyRestricted = $allowAll->isEmpty()
                && ! empty($enabledChannels)
                && empty(array_diff($enabledChannels, $covered));

            if (! $fullyRestricted) {
                $this->setUnrestricted($projectId, $userId, true);

                continue;
            }

            $this->setUnrestricted($projectId, $userId, false);

            $userName = User::find($userId)?->name ?? "User {$userId}";

            foreach ($restricted as $row) {
                $channel = (string) $row->channel;
                $assetIds = json_decode((string) $row->allowed_assets, true) ?? [];
                if (empty($assetIds)) {
                    continue;
                }

                $valid = $project ? $service->getValidEnabledAssetsForChannel($project, $channel) : [];
                $validIds = array_values(array_intersect(array_map('strval', $assetIds), $valid));

                if (empty($validIds)) {
                    continue;
                }

                $group = AssetGroup::create([
                    'project_id' => $projectId,
                    'name' => "Imported · {$userName} · {$channel}",
                ]);

                foreach ($validIds as $assetId) {
                    AssetGroupItem::create([
                        'asset_group_id' => $group->id,
                        'channel' => $channel,
                        'asset_id' => $assetId,
                    ]);
                }

                ProjectUserAssetGroup::create([
                    'project_id' => $projectId,
                    'user_id' => $userId,
                    'asset_group_id' => $group->id,
                ]);
            }
        }

        Schema::dropIfExists('project_user_allowed_assets');
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_user_allowed_assets')) {
            Schema::create('project_user_allowed_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('channel');
                $table->json('allowed_assets')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'user_id', 'channel']);
            });
        }

        if (Schema::hasTable('project_user_allowed_assets_snapshot')) {
            foreach (DB::table('project_user_allowed_assets_snapshot')->get() as $row) {
                DB::table('project_user_allowed_assets')->insert([
                    'project_id' => $row->project_id,
                    'user_id' => $row->user_id,
                    'channel' => $row->channel,
                    'allowed_assets' => $row->allowed_assets,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            Schema::dropIfExists('project_user_allowed_assets_snapshot');
        }
    }

    protected function setUnrestricted(int $projectId, int $userId, bool $value): void
    {
        DB::table('project_user')
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->update(['asset_access_unrestricted' => $value]);
    }

    protected function enabledChannels(Project $project): array
    {
        $channels = [];
        foreach (($project->sync_config ?? []) as $channel => $data) {
            if (! empty($data['enabled'])) {
                $channels[] = (string) $channel;
            }
        }

        return $channels;
    }
};
