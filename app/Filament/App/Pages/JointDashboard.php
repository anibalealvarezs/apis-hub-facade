<?php

namespace App\Filament\App\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use App\Services\RemoteEngineService;

class JointDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $cluster = \App\Filament\App\Clusters\DataExplorer::class;
    
    public static function getNavigationLabel(): string
    {
        return __('Joint Dashboard');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Exploration & Telemetry');
    }

    public function getTitle(): string
    {
        return __('Joint Dashboard (Correlation)');
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    protected static string $view = 'filament.app.pages.joint-dashboard';
    protected static ?string $slug = 'joint-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_data');
    }

    public ?string $dateStart = null;
    public ?string $dateEnd = null;

    public array $curveA = ['channel' => '', 'asset' => '', 'metric' => ''];
    public array $curveB = ['channel' => '', 'asset' => '', 'metric' => ''];

    // Data for dropdowns
    public array $channels = [
        'facebook_marketing' => 'Meta Ads',
        'facebook_organic' => 'FB & IG Organic',
        'google_search_console' => 'Google Search Console'
    ];

    public array $metricsDict = [
        'facebook_marketing' => [
            'spend' => 'Spend',
            'impressions' => 'Impressions',
            'clicks' => 'Clicks',
            'cpc' => 'CPC',
            'cpm' => 'CPM',
            'ctr' => 'CTR',
            'results' => 'Conversions',
            'cost_per_result' => 'CPA',
            'purchase_roas' => 'ROAS'
        ],
        'facebook_organic' => [
            'reach' => 'Reach',
            'impressions' => 'Impressions',
            'profile_views' => 'Profile Views',
            'total_interactions' => 'Engagements'
        ],
        'google_search_console' => [
            'clicks' => 'Clicks',
            'impressions' => 'Impressions',
            'ctr' => 'CTR',
            'position' => 'Position'
        ]
    ];

    // Data to pass to frontend
    public array $chartData = [];
    public array $availableAccounts = [
        'facebook_marketing' => [],
        'facebook_organic' => [],
        'google_search_console' => []
    ];

    public function mount(): void
    {
        $this->dateEnd = Carbon::now()->subDays(1)->format('Y-m-d');
        $this->dateStart = Carbon::now()->subDays(31)->format('Y-m-d');

        $this->availableAccounts['facebook_marketing'] = $this->fetchAccounts('facebook_marketing');
        $this->availableAccounts['facebook_organic'] = $this->fetchAccounts('facebook_organic');
        $this->availableAccounts['google_search_console'] = $this->fetchAccounts('google_search_console');
    }

    public function fetchAccounts(string $channel)
    {
        $accounts = [];
        try {
            $service = app(RemoteEngineService::class);
            $tenant = Filament::getTenant();
            $response = $service->listChanneled($tenant, $channel, 'channeled_account', ['limit' => 1000, 'enabled' => 1]);

            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $account) {
                    $accountId = (string) $account['id'];
                    $accounts[$accountId] = $account['name'] ?? $accountId;
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Joint Dashboard Accounts Error: " . $e->getMessage());
        }
        return $accounts;
    }

    public function fetchJointData(array $a, array $b, string $dStart, string $dEnd)
    {
        $tenant = Filament::getTenant();
        $service = app(RemoteEngineService::class);

        // Pad start date backwards by 10 days to allow for accurate diff/lag calculations on the frontend
        $paddedDateStart = Carbon::parse($dStart)->subDays(10)->format('Y-m-d');

        $seriesA = $this->fetchSeries($tenant, $service, $a, $paddedDateStart, $dEnd);
        $seriesB = $this->fetchSeries($tenant, $service, $b, $paddedDateStart, $dEnd);

        $this->chartData = [
            'curveA' => $seriesA,
            'curveB' => $seriesB,
            'originalStartDate' => $dStart,
            'originalEndDate' => $dEnd
        ];

        $this->dispatch('joint-data-loaded', $this->chartData);
    }

    protected function fetchSeries($tenant, RemoteEngineService $service, array $config, string $dateStart, string $dateEnd)
    {
        $channel = $config['channel'];
        $asset = $config['asset'];
        $metric = $config['metric'];

        $payload = [
            'aggregations' => [
                $metric => $metric
            ],
            'groupBy' => ['daily'],
            'startDate' => Carbon::parse($dateStart)->format('Y-m-d'),
            'endDate' => Carbon::parse($dateEnd)->format('Y-m-d'),
            'filters' => []
        ];

        $entity = 'metric';

        if ($channel === 'facebook_marketing') {
            $payload['filters']['channel'] = $channel;
            $payload['filters']['channeledAccount'] = $asset;
            if (in_array($metric, ['spend', 'impressions', 'clicks', 'reach', 'results'])) {
                $payload['aggregations'] = ['trend_total_' . $metric => $metric];
            } else {
                $payload['aggregations'] = ['trend_average_' . $metric => $metric];
            }
        } else if ($channel === 'facebook_organic') {
            $payload['filters']['channel'] = $channel;
            $payload['filters']['channeledAccount'] = $asset;
            $payload['filters']['period'] = 'daily';
            $payload['filters']['account_type'] = ['operator' => 'in', 'value' => ['facebook_page', 'instagram_account']];
        } else if ($channel === 'google_search_console') {
            $payload['filters']['channeledAccount'] = (string)$asset;
            $payload['filters']['dimensions.searchAppearance'] = 'standard';
        }

        $response = $service->aggregateChanneled($tenant, $channel, $entity, $payload);
        \Illuminate\Support\Facades\Log::info("JointDashboard Fetch {$channel} / {$metric}:", ['payload' => $payload, 'response' => $response]);

        $dates = [];
        $values = [];

        $periodStart = Carbon::parse($dateStart);
        $periodEnd = Carbon::parse($dateEnd);
        $periodDates = [];
        for ($date = $periodStart->copy(); $date->lte($periodEnd); $date->addDay()) {
            $periodDates[] = $date->format('Y-m-d');
        }

        if (isset($response['data']) && is_array($response['data'])) {
            $dataMap = [];
            foreach ($response['data'] as $row) {
                $d = null;
                if (isset($row['date_start'])) $d = substr($row['date_start'], 0, 10);
                elseif (isset($row['date'])) $d = substr($row['date'], 0, 10);
                elseif (isset($row['snapshot_date'])) $d = substr($row['snapshot_date'], 0, 10);
                elseif (isset($row['daily'])) $d = substr($row['daily'], 0, 10);

                if ($d) {
                    $val = floatval($row[$metric] ?? $row['trend_total_'.$metric] ?? $row['trend_average_'.$metric] ?? 0);
                    if (!isset($dataMap[$d])) {
                        $dataMap[$d] = $val;
                    } else {
                        $dataMap[$d] += $val;
                    }
                }
            }

            foreach ($periodDates as $d) {
                $dates[] = $d;
                $values[] = $dataMap[$d] ?? null;
            }
        } else {
            foreach ($periodDates as $d) {
                $dates[] = $d;
                $values[] = null;
            }
        }

        $channelName = $this->channels[$channel] ?? $channel;
        $metricName = $this->metricsDict[$channel][$metric] ?? $metric;

        return [
            'name' => "{$channelName} - {$metricName}",
            'metric' => $metric,
            'dates' => $dates,
            'values' => $values
        ];
    }
}
