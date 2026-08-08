<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Services\CollaboratorAssetAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeChannelAssetAccess
{
    public function __construct(protected CollaboratorAssetAccessService $access)
    {
    }

    public function handle(Request $request, Closure $next, string $channel): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $tenantKey = $request->input('tenant');
        if ($tenantKey === null) {
            return $next($request);
        }

        $project = null;
        if (is_numeric($tenantKey)) {
            $project = Project::query()->where('id', (int) $tenantKey)->first();
        }
        if (! $project) {
            $project = Project::query()->where('subdomain', (string) $tenantKey)->first();
        }

        if (! $project) {
            return $next($request);
        }

        $userId = (int) $user->getAuthIdentifier();

        if (! $this->access->isProjectMember($project, $userId)) {
            return response()->json([
                'success' => false,
                'error' => 'access_denied',
                'message' => 'You do not have access to this project.',
            ], 403);
        }

        if ($this->access->isUnrestricted($project, $userId)) {
            return $next($request);
        }

        $account = $request->input('account');
        if ($account === null || $account === '') {
            return $next($request);
        }

        $requestedIds = is_array($account)
            ? array_values(array_filter($account, fn ($v) => $v !== null && $v !== ''))
            : [$account];

        if (empty($requestedIds)) {
            return $this->accessRestricted();
        }

        $filtered = $this->access->filterRequestedChannelAssets($project, $userId, $channel, $requestedIds);

        if (empty($filtered)) {
            return $this->accessRestricted();
        }

        $request->merge(['account' => is_array($account) ? $filtered : $filtered[0]]);

        return $next($request);
    }

    protected function accessRestricted(): Response
    {
        return response()->json([
            'success' => false,
            'error' => 'access_restricted',
            'message' => 'You do not have access to the requested assets for this channel.',
        ], 403);
    }
}
