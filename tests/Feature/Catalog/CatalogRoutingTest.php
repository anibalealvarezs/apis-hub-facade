<?php

namespace Tests\Feature\Catalog;

use App\Filament\App\Pages\CatalogEntity;
use App\Filament\App\Pages\MarketingOverview;
use Illuminate\Http\Request;
use Tests\TestCase;

class CatalogRoutingTest extends TestCase
{
    public function test_overview_resolves_to_its_page_instead_of_an_entity(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/app/mabe-dev/catalog/overview'));

        $this->assertSame(MarketingOverview::class, $route->getActionName());
        $this->assertSame('filament.app.pages.catalog.overview', $route->getName());
        $this->assertNull($route->parameter('entity'));
    }

    public function test_supported_entities_keep_their_list_and_detail_routes(): void
    {
        foreach (array_keys(CatalogEntity::ENTITIES) as $entity) {
            foreach (['', '/'.rawurlencode('channeled:google_ads:'.$entity.':42')] as $recordPath) {
                $route = app('router')->getRoutes()->match(
                    Request::create('/app/mabe-dev/catalog/'.$entity.$recordPath)
                );

                $this->assertSame(CatalogEntity::class, $route->getActionName());
                $this->assertSame($entity, $route->parameter('entity'));
            }
        }
    }

    public function test_compiled_routes_resolve_overview_and_entity_details(): void
    {
        $router = app('router');
        $routes = $router->getRoutes()->toCompiledRouteCollection($router, app());
        $overview = $routes->match(Request::create('/app/mabe-dev/catalog/overview'));
        $detail = $routes->match(Request::create('/app/mabe-dev/catalog/campaigns/'.rawurlencode('channeled:google_ads:campaigns:42')));

        $this->assertSame(MarketingOverview::class, $overview->getActionName());
        $this->assertSame(CatalogEntity::class, $detail->getActionName());
        $this->assertSame('campaigns', $detail->parameter('entity'));
    }
}
