<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FacebookOrganicController extends Controller
{
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'tenant' => 'required|string',
            'account' => 'required|array',
            'account.*' => 'nullable',
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'required|string|in:facebook,instagram',
            'postId' => 'nullable|string',
        ]);
    }

    private function getTabConfig(string $tab): array
    {
        switch ($tab) {
            case 'ig_accounts':
                return [
                    'filters' => ['account_type' => 'instagram_account'],
                    'groupBy' => ['channeledAccount', 'channeled_account_id', 'page_platform_id', 'linked_fb_page_id'],
                    'aggregations' => [
                        'likes' => 'likes', 'comments' => 'comments', 'reach' => 'reach', 'views' => 'views',
                        'profile_views' => 'profile_views', 'website_clicks' => 'website_clicks', 
                        'profile_links_taps' => 'profile_links_taps', 'follows_and_unfollows' => 'follows_and_unfollows',
                        'saves' => 'saves', 'shares' => 'shares', 'total_interactions' => 'total_interactions',
                        'replies' => 'replies', 'accounts_engaged' => 'accounts_engaged'
                    ]
                ];
            case 'fb_pages':
                return [
                    'filters' => ['account_type' => 'facebook_page', 'channel' => 'facebook_organic', 'period' => 'daily'],
                    'groupBy' => ['page', 'page_id', 'page_title'],
                    'aggregations' => [
                        'reach' => 'reach', 'page_views_total' => 'page_views_total', 'video_views' => 'video_views',
                        'follows_and_unfollows' => 'follows_and_unfollows', 'total_interactions' => 'total_interactions', 'likes' => 'likes'
                    ]
                ];
            case 'ig_posts':
                return [
                    'filters' => ['account_type' => 'instagram_account', 'post' => 'NOT_NULL', 'snapshot_fallback_mode' => 'resilient', 'period' => 'lifetime', 'latest_snapshot' => true],
                    'groupBy' => ['post', 'post_id', 'caption', 'message', 'media_type', 'permalink', 'permalink_url', 'timestamp', 'created_time'],
                    'aggregations' => [
                        'comments' => 'comments', 'follows' => 'follows', 'ig_reels_avg_watch_time' => 'ig_reels_avg_watch_time',
                        'ig_reels_video_view_total_time' => 'ig_reels_video_view_total_time', 'likes' => 'likes',
                        'profile_activity' => 'profile_activity', 'profile_visits' => 'profile_visits', 'reach' => 'reach',
                        'reposts' => 'reposts', 'saved' => 'saved', 'shares' => 'shares', 'total_interactions' => 'total_interactions', 'views' => 'views'
                    ]
                ];
            case 'fb_posts':
                return [
                    'filters' => ['account_type' => 'facebook_page', 'post' => 'NOT_NULL', 'snapshot_fallback_mode' => 'resilient', 'period' => 'lifetime', 'latest_snapshot' => true],
                    'groupBy' => ['post', 'post_id', 'caption', 'message', 'media_type', 'permalink', 'permalink_url', 'timestamp', 'created_time'],
                    'aggregations' => [
                        'reach' => 'reach', 'total_interactions' => 'total_interactions', 'likes' => 'likes',
                        'post_clicks' => 'post_clicks', 'views' => 'views', 'video_views' => 'video_views',
                        'post_video_avg_time_watched' => 'post_video_avg_time_watched'
                    ]
                ];
        }
        return [];
    }

    public function summary(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $start = Carbon::parse($validated['dateStart']);
            $end = Carbon::parse($validated['dateEnd']);
            $diff = $start->diffInDays($end) + 1;
            
            $prevEnd = $start->copy()->subDay();
            $prevStart = $prevEnd->copy()->subDays($diff - 1);

            $internalTab = $validated['activeTab'] === 'facebook' ? 'fb_pages' : 'ig_accounts';
            $config = $this->getTabConfig($internalTab);
            
            $baseFilters = $config['filters'];
            
            $fbAccountIds = [];
            $igAccountIds = [];
            if (!empty($validated['account'])) {
                foreach ($validated['account'] as $accValue) {
                    $parts = explode('|', $accValue);
                    $fbAccountIds[] = $parts[0];
                    if (isset($parts[1]) && $parts[1] !== 'NONE') {
                        $igAccountIds[] = $parts[1];
                    }
                }
            }

            if ($validated['activeTab'] === 'instagram') {
                if (empty($igAccountIds)) {
                    $baseFilters['channeledAccount'] = ['operator' => 'in', 'value' => ['__NONE__']];
                } else {
                    $baseFilters['channeledAccount'] = count($igAccountIds) === 1 ? $igAccountIds[0] : ['operator' => 'in', 'value' => $igAccountIds];
                }
            } else {
                if (!empty($fbAccountIds)) {
                    $baseFilters['channeledAccount'] = count($fbAccountIds) === 1 ? $fbAccountIds[0] : ['operator' => 'in', 'value' => $fbAccountIds];
                }
            }

            $payloads = [
                'summary' => [
                    'aggregations' => $config['aggregations'],
                    'groupBy' => [],
                    'filters' => $baseFilters,
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd']
                ],
                'previous' => [
                    'aggregations' => $config['aggregations'],
                    'groupBy' => [],
                    'filters' => $baseFilters,
                    'startDate' => $prevStart->format('Y-m-d'), 
                    'endDate' => $prevEnd->format('Y-m-d')
                ],
            ];

            $results = $service->aggregateChanneledPool($tenant, 'facebook_organic', 'metric', $payloads);

            return response()->json([
                'summary' => $results['summary']['data'][0] ?? new \stdClass(),
                'previous' => $results['previous']['data'][0] ?? new \stdClass(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function chart(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $internalTab = $validated['activeTab'] === 'facebook' ? 'fb_pages' : 'ig_accounts';
            if (!empty($validated['postId'])) {
                $internalTab = $validated['activeTab'] === 'facebook' ? 'fb_posts' : 'ig_posts';
            }
            $config = $this->getTabConfig($internalTab);
            
            $baseFilters = $config['filters'];
            
            $fbAccountIds = [];
            $igAccountIds = [];
            if (!empty($validated['account'])) {
                foreach ($validated['account'] as $accValue) {
                    $parts = explode('|', $accValue);
                    $fbAccountIds[] = $parts[0];
                    if (isset($parts[1]) && $parts[1] !== 'NONE') {
                        $igAccountIds[] = $parts[1];
                    }
                }
            }

            if ($validated['activeTab'] === 'instagram') {
                if (empty($igAccountIds)) {
                    $baseFilters['channeledAccount'] = ['operator' => 'in', 'value' => ['__NONE__']];
                } else {
                    $baseFilters['channeledAccount'] = count($igAccountIds) === 1 ? $igAccountIds[0] : ['operator' => 'in', 'value' => $igAccountIds];
                }
            } else {
                if (!empty($fbAccountIds)) {
                    $baseFilters['channeledAccount'] = count($fbAccountIds) === 1 ? $fbAccountIds[0] : ['operator' => 'in', 'value' => $fbAccountIds];
                }
            }

            if (!empty($validated['postId'])) {
                $baseFilters['post'] = $validated['postId'];
                // For historic charts of posts, we want the daily deltas (which are virtual metrics generated from lifetime snapshots).
                // We must query the lifetime snapshots to allow the backend to generate the deltas,
                // so we only unset 'latest_snapshot' to get all historic records instead of just one.
                unset($baseFilters['latest_snapshot']);
            }

            $aggregations = [];
            foreach ($config['aggregations'] as $k => $v) {
                // For chart, prefix with trend_total_ or trend_average_
                $aggregations['trend_total_' . $k] = $v;
            }

            $payloads = [
                'chart' => [
                    'aggregations' => $aggregations,
                    'groupBy' => ['daily'],
                    'filters' => $baseFilters,
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                    'limit' => 1000
                ]
            ];

            $results = $service->aggregateChanneledPool($tenant, 'facebook_organic', 'metric', $payloads);

            return response()->json([
                'chart' => $results['chart']['data'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function table(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $internalTab = $validated['activeTab'] === 'facebook' ? 'fb_posts' : 'ig_posts';
            $config = $this->getTabConfig($internalTab);
            
            $baseFilters = $config['filters'];
            
            $fbAccountIds = [];
            $igAccountIds = [];
            if (!empty($validated['account'])) {
                foreach ($validated['account'] as $accValue) {
                    $parts = explode('|', $accValue);
                    $fbAccountIds[] = $parts[0];
                    if (isset($parts[1]) && $parts[1] !== 'NONE') {
                        $igAccountIds[] = $parts[1];
                    }
                }
            }

            if ($validated['activeTab'] === 'instagram') {
                if (empty($igAccountIds)) {
                    $baseFilters['channeledAccount'] = ['operator' => 'in', 'value' => ['__NONE__']];
                } else {
                    $baseFilters['channeledAccount'] = count($igAccountIds) === 1 ? $igAccountIds[0] : ['operator' => 'in', 'value' => $igAccountIds];
                }
            } else {
                if (!empty($fbAccountIds)) {
                    $baseFilters['channeledAccount'] = count($fbAccountIds) === 1 ? $fbAccountIds[0] : ['operator' => 'in', 'value' => $fbAccountIds];
                }
            }

            $payloads = [
                'table' => [
                    'aggregations' => $config['aggregations'],
                    'groupBy' => $config['groupBy'],
                    'filters' => $baseFilters,
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                    'limit' => 500
                ]
            ];

            $results = $service->aggregateChanneledPool($tenant, 'facebook_organic', 'metric', $payloads);
            $tableData = $results['table']['data'] ?? [];

            // Normalize ID and Name for frontend table rendering
            foreach ($tableData as &$row) {
                $rowLower = array_change_key_case($row, CASE_LOWER);
                
                if (isset($rowLower['post_id'])) {
                    $row['id'] = $rowLower['post_id'];
                    // Limit name length for posts
                    $name = $rowLower['message'] ?? $rowLower['caption'] ?? $rowLower['post_id'];
                    $row['name'] = mb_strlen($name) > 50 ? mb_substr($name, 0, 47) . '...' : $name;
                } elseif (isset($rowLower['page_id'])) {
                    $row['id'] = $rowLower['page_id'];
                    $row['name'] = $rowLower['page_title'] ?? $rowLower['page'] ?? $rowLower['page_id'];
                } elseif (isset($rowLower['channeled_account_id'])) {
                    $row['id'] = $rowLower['channeled_account_id'];
                    $row['name'] = $rowLower['channeledaccount'] ?? $rowLower['channeled_account_id'];
                } else {
                    $row['id'] = 'Unknown';
                    $row['name'] = 'Unknown';
                }
            }

            return response()->json([
                'table' => $tableData,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function post(Request $request)
    {
        try {
            $validated = $request->validate([
                'tenant' => 'required|string',
                'postId' => 'required|string',
            ]);
            
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);
            
            // Query the orchestrator for the specific post entity
            $results = $service->listChanneled($tenant, 'facebook_organic', 'post', [
                'limit' => 1,
                'postId' => $validated['postId'] // Doctrine ORM expects camelCase property name
            ]);

            return response()->json([
                'post' => $results['data'][0] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
