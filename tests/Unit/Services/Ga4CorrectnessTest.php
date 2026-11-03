<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\Api\GoogleAnalyticsController;
use App\Services\Analytics\Ga4ReportingSemantics;
use App\Services\Analytics\KpiFormBuilder;
use Illuminate\Http\Request;
use Tests\TestCase;

final class Ga4CorrectnessTest extends TestCase
{
    public function testUserNoticesFollowSelectedMetricsAndTheirOwnChannels(): void
    {
        foreach (['reach', 'activeUsers', 'active_users', 'newUsers', 'new_users', 'totalUsers', 'total_users', 'trend_total_total_users'] as $metric) {
            self::assertTrue(Ga4ReportingSemantics::usesGa4UserMetrics(['channel' => 'google_analytics', 'metrics' => [$metric]]));
        }
        foreach (['sessions', 'average_session_duration', 'bounce_rate', 'event_count', 'revenue', 'conversions', 'pageviews'] as $metric) {
            self::assertFalse(Ga4ReportingSemantics::usesGa4UserMetrics(['channel' => 'google_analytics', 'metrics' => [$metric], 'allowed_metrics' => ['reach']]));
        }
        $mixed = ['raw_series' => [
            ['channel' => 'google_analytics', 'metrics' => ['sessions']],
            ['channel' => 'facebook_marketing', 'metrics' => ['reach']],
        ]];
        self::assertFalse(Ga4ReportingSemantics::usesGa4UserMetrics($mixed));
        $mixed['series_metrics'] = [['total_users'], ['reach']];
        self::assertTrue(Ga4ReportingSemantics::usesGa4UserMetrics($mixed));
        $mixed['series_metrics'] = [['sessions'], ['reach']];
        $mixed['raw_series'][0]['metrics'] = ['total_users'];
        self::assertFalse(Ga4ReportingSemantics::usesGa4UserMetrics($mixed), 'Runtime selection replaces saved user metrics.');
        self::assertTrue(Ga4ReportingSemantics::usesGa4UserMetrics(['dependent_channel' => 'google_analytics', 'dependent_metric' => 'new_users']));
        self::assertTrue(Ga4ReportingSemantics::usesGa4UserMetrics(['independent_variables' => [['independent_channel' => 'google_analytics', 'independent_metric' => 'totalUsers']]]));
    }

    public function testAcquisitionAvailabilityAndWarningsAreConsistent(): void
    {
        $controller = new GoogleAnalyticsController();
        $this->app->instance('current_public_project', new \App\Models\Project(['sync_config' => ['google' => ['is_active' => true], 'google_analytics' => ['properties' => [['id' => 'fixture', 'enabled' => true]]]]]));
        $method = new \ReflectionMethod($controller, 'metricsForScope');
        self::assertNotContains('totalRevenue', $method->invoke($controller, 'acquisition_matrix'));
        $options = KpiFormBuilder::getMetricOptionsForChannel('google_analytics', 'daily', 'acquisition_matrix');
        self::assertNotEmpty($options);
        self::assertArrayNotHasKey('revenue', $options);
        self::assertStringContainsString('Reported Sum', $options['total_users']);
        self::assertTrue(Ga4ReportingSemantics::containsGa4([['series' => [['channel' => 'google_analytics']]]]));
        self::assertFalse(Ga4ReportingSemantics::containsGa4([['channel' => 'google_ads']]));
        self::assertStringContainsString('not unique users', Ga4ReportingSemantics::userCountNotice());
    }

    public function testCostIsRejectedBeforeAnyTenantRequest(): void
    {
        $request = Request::create('/fixture', 'POST', ['tenant' => 1, 'account' => ['1'], 'dateStart' => '2026-10-01', 'dateEnd' => '2026-10-02', 'metrics' => ['spend']]);
        $controller = new GoogleAnalyticsController();
        foreach (['summary', 'chart', 'table'] as $action) {
            self::assertSame(422, $controller->$action($request)->getStatusCode());
        }
    }

    public function testFailedScopeCannotBeDisplayedAsPartialTotals(): void
    {
        $this->expectException(\RuntimeException::class);
        Ga4ReportingSemantics::assertSuccessfulResults(['traffic' => ['data' => [['sessions' => 100]]], 'acquisition' => ['status' => 'error', 'message' => 'fixture']]);
    }

    public function testWidgetRateMissingnessIsNotTurnedIntoZero(): void
    {
        Ga4ReportingSemantics::assertWidgetRatesAvailable(['summary' => ['bounce_rate' => 0.0], 'previous' => ['bounce_rate' => null]]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Ga4ReportingSemantics::assertWidgetRatesAvailable(['chart' => [['bounceRate' => null]]]);
    }
}
