<?php

namespace Tests\Unit\Services;

use App\Domain\ChannelProfiles\Profiles\FacebookMarketingProfile;
use App\Filament\App\Pages\FacebookMarketingDashboard;
use App\Models\Project;
use App\Services\Analytics\ChannelBreakdownRegistry;
use App\Services\Analytics\MetaAdsBreakdownOptions;
use Filament\Facades\Filament;
use Tests\TestCase;

class MetaAdsBreakdownOptionsTest extends TestCase
{
    public function testNoneIsAValidProfileOptionAndSurvivesLegacyNormalization(): void
    {
        $field = (new FacebookMarketingProfile())->getSchemaDefinition()['fields']['breakdown_passes'];
        $this->assertArrayHasKey('none', $field['options']);
        foreach (['none', [], false, '', ['none'], ['demographics' => false], ['none', 'demographics']] as $value) {
            $this->assertSame('none', MetaAdsBreakdownOptions::normalize($value));
        }
        $this->assertSame('demographics', MetaAdsBreakdownOptions::normalize(null));
        $this->assertSame('placement', MetaAdsBreakdownOptions::normalize(['placement' => true]));
        $this->assertSame('geography', MetaAdsBreakdownOptions::normalize(['geography']));
    }

    public function testDisabledBreakdownsAreNotOfferedInExplorerOrWidgetDimensionLists(): void
    {
        $previous = Filament::getTenant();
        try {
            Filament::setTenant(new Project(['sync_config' => ['facebook_marketing' => ['breakdown_passes' => 'none']]]), isQuiet: true);
            $tabs = (new FacebookMarketingDashboard())->getAvailableTabs();
            $this->assertSame(['campaigns', 'adsets', 'ads'], array_keys($tabs));
            $definitions = ChannelBreakdownRegistry::getBreakdownsForChannel('facebook_marketing');
            foreach (['dimensions.age', 'dimensions.gender', 'dimensions.country', 'dimensions.publisher_platform', 'dimensions.platform_position'] as $key) $this->assertArrayNotHasKey($key, $definitions);
            $this->assertArrayHasKey('channeledCampaign', $definitions);
        } finally {
            Filament::setTenant($previous, isQuiet: true);
        }
    }

    public function testSpanishWarningExplainsTheTemporaryLimitationWithoutLegalReasons(): void
    {
        $previous = app()->getLocale();
        try {
            app()->setLocale('es');
            $html = view('filament.app.components.data-sources.meta-breakdown-warning')->render();
            $this->assertStringContainsString('role="alert"', $html);
            $this->assertStringContainsString('Estados Unidos', $html);
            $this->assertStringContainsString('Sin desglose', $html);
            $this->assertStringNotContainsString('ejecutiva', $html);
            $this->assertStringNotContainsString('legal', $html);
        } finally {
            app()->setLocale($previous);
        }
    }
}
