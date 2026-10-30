<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ScopeChannelAssetAccess;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Illuminate\Http\Request;

class EntityAttributeController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'tenant' => 'required|integer', 'channel' => 'required|string|regex:/^[a-z][a-z0-9_]*$/',
            'account' => 'nullable|array', 'account.*' => 'required|string|max:512',
            'relation' => 'nullable|string|regex:/^[a-zA-Z][a-zA-Z0-9_]*$/', 'values' => 'nullable|boolean',
            'assetContext' => 'nullable|string|max:64',
        ]);
        return app(ScopeChannelAssetAccess::class)->handle($request, function (Request $scoped) use ($validated) {
            $project = Project::findOrFail($validated['tenant']);
            $payload = ['relation' => $validated['relation'] ?? null, 'values' => $validated['values'] ?? false,
                'account' => $scoped->input('account', [])];
            // The organic explorer encodes both account IDs plus page identity
            // in each selected asset. Decode only after collaborator scoping.
            if ($validated['channel'] === 'facebook_organic') {
                $index = ($validated['assetContext'] ?? '') === 'instagram' ? 1 : 0;
                $payload['account'] = array_values(array_unique(array_filter(array_map(
                    static fn ($asset) => explode('|', (string)$asset)[$index] ?? '',
                    $payload['account']
                ), static fn ($id) => ctype_digit($id))));
                if (!$payload['account']) $payload['values'] = false;
            }
            return response()->json(app(RemoteEngineService::class)->getEntityAttributes($project, $validated['channel'], $payload));
        }, $validated['channel']);
    }
}
