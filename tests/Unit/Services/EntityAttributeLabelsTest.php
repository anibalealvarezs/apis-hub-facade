<?php

namespace Tests\Unit\Services;

use App\Services\Analytics\EntityAttributeLabels;
use Tests\TestCase;

class EntityAttributeLabelsTest extends TestCase
{
    public function testKnownNamesAndUnknownFieldsAreReadableWithoutChangingFilterPaths(): void
    {
        app()->setLocale('en');
        $catalog = [
            'attributes.channeledCampaign.buyingType' => ['field' => 'buyingType', 'relation' => 'channeledCampaign'],
            'attributes.channeledAdGroup.optimization_goal' => ['field' => 'optimization_goal', 'relation' => 'channeledAdGroup'],
            'attributes.page.hostname' => ['field' => 'hostname', 'relation' => 'page'],
            'attributes.product.sku' => ['field' => 'sku', 'relation' => 'product'],
            'attributes.product.futureURLCode' => ['field' => 'futureURLCode', 'relation' => 'product'],
        ];
        $labels = EntityAttributeLabels::decorate($catalog, 'facebook_marketing');
        self::assertSame(array_keys($catalog), array_keys($labels));
        self::assertSame('Buying Type', $labels['attributes.channeledCampaign.buyingType']['display_label']);
        self::assertSame('Ad Set / Optimization Goal', $labels['attributes.channeledAdGroup.optimization_goal']['label']);
        self::assertSame('Domain', $labels['attributes.page.hostname']['display_label']);
        self::assertSame('SKU', $labels['attributes.product.sku']['display_label']);
        self::assertSame('Future URL Code', $labels['attributes.product.futureURLCode']['display_label']);
    }

    public function testLabelsUseTheCurrentUiLanguage(): void
    {
        app()->setLocale('es');
        $labels = EntityAttributeLabels::decorate(['attribute' => ['field' => 'buyingType', 'relation' => 'channeledCampaign']], 'facebook_marketing');
        self::assertSame('Campaña / Tipo de compra', $labels['attribute']['label']);
    }
}
