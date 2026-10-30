<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesEntityAttributeFilters;
use App\Http\Controllers\Api\DashboardWidgetDataController;
use PHPUnit\Framework\TestCase;

class EntityAttributeFiltersTest extends TestCase
{
    public function testAttributesReachEveryPayloadWithoutOverridingAccountOrChannelScope(): void
    {
        $controller = new class {
            use AppliesEntityAttributeFilters;
            public function apply(array $payloads, array $validated): array { return $this->withEntityAttributeFilters($payloads, $validated); }
        };
        $result = $controller->apply([
            'summary' => ['filters' => ['channel' => 'facebook_marketing', 'channeledAccount' => 42]],
            'previous' => ['filters' => ['channel' => 'facebook_marketing', 'channeledAccount' => 42]],
        ], ['filters' => [
            'channel' => 'other', 'channeledAccount' => 999,
            'attributes.channeledCampaign.objective' => ['operator' => 'in', 'value' => ['OUTCOME_SALES', 'OUTCOME_LEADS']],
            'attributes.channeledCampaign.budget__1' => ['operator' => 'gte', 'value' => 0],
        ]]);
        foreach ($result as $payload) {
            self::assertSame('facebook_marketing', $payload['filters']['channel']);
            self::assertSame(42, $payload['filters']['channeledAccount']);
            self::assertSame(0, $payload['filters']['attributes.channeledCampaign.budget__1']['value']);
            self::assertCount(2, $payload['filters']['attributes.channeledCampaign.objective']['value']);
        }
    }

    public function testWidgetRulesPreserveMultipleBoundsOnTheSameAttribute(): void
    {
        $controller = new class extends DashboardWidgetDataController {
            public function __construct() {}
            public function normalize(array $rules): array { return $this->normalizeFiltersForPayload($rules); }
        };
        $path = 'attributes.channeledCampaign.budget';
        $result = $controller->normalize([
            ['dimension' => $path, 'operator' => 'gte', 'value' => '0'],
            ['dimension' => $path, 'operator' => 'lte', 'value' => '100'],
            ['dimension' => 'attributes.channeledCampaign.objective', 'operator' => 'in', 'value' => 'OUTCOME_SALES, OUTCOME_LEADS'],
            ['dimension' => 'attributes.channeledAdGroup.startDate', 'operator' => 'between', 'value' => '2026-01-01, 2026-02-01'],
        ]);
        self::assertSame('gte', $result[$path]['operator']);
        self::assertSame('lte', $result[$path.'__1']['operator']);
        self::assertSame(['OUTCOME_SALES', 'OUTCOME_LEADS'], $result['attributes.channeledCampaign.objective']['value']);
        self::assertSame(['2026-01-01', '2026-02-01'], $result['attributes.channeledAdGroup.startDate']['value']);
    }
}
