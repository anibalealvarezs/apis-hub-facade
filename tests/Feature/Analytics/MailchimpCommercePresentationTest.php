<?php

namespace Tests\Feature\Analytics;

use App\Filament\App\Pages\CatalogEntity;
use App\Filament\App\Pages\MailchimpDashboard;
use App\Models\Project;
use App\Models\User;
use App\Services\RemoteEngineService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MailchimpCommercePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_commerce_pages_render_and_can_export_synthetic_browser_fixtures(): void
    {
        Permission::firstOrCreate(['name' => 'view_data', 'guard_name' => 'web']);
        $owner = User::factory()->create();
        $owner->givePermissionTo('view_data');
        $project = Project::factory()->create([
            'user_id' => $owner->id, 'subdomain' => 'commerce-fixture',
            'sync_config' => ['mailchimp' => ['enabled' => true, 'audiences' => [
                ['id' => 'audience', 'name' => 'Store audience', 'enabled' => true],
            ]]],
        ]);
        $project->users()->attach($owner->id, ['asset_access_unrestricted' => true]);
        $role = Role::firstOrCreate(['name' => 'project_owner', 'guard_name' => 'web']);
        DB::table('model_has_roles')->insertOrIgnore([
            'role_id' => $role->id, 'model_type' => User::class, 'model_id' => $owner->id, 'project_id' => $project->id,
        ]);
        $this->actingAs($owner);
        Filament::setTenant($project);
        $account = ['reference' => 'channeled:mailchimp:accounts:42', 'entity_type' => 'accounts',
            'id' => 42, 'name' => 'Store audience', 'platform_id' => 'audience'];
        $store = ['reference' => 'channeled:mailchimp:stores:8', 'entity_type' => 'stores', 'id' => 8,
            'name' => 'Example connected store', 'platform_id' => str_repeat('a', 64), 'channel' => 'mailchimp', 'status' => null,
            'channel_label' => 'Mailchimp', 'attributes' => ['domain' => 'store.example.test', 'currency' => 'EUR'],
            'relationships' => ['account' => $account], 'provenance' => ['updated_at' => '2026-11-06 12:00:00']];
        $engine = Mockery::mock(RemoteEngineService::class);
        $engine->shouldReceive('listChanneled')->andReturn(['status' => 'success', 'data' => [
            ['id' => 42, 'platformId' => 'audience', 'name' => 'Store audience'],
        ]]);
        $engine->shouldReceive('getCatalogCapabilities')->andReturn(['status' => 'success', 'data' => [
            'entities' => ['stores' => ['supported' => true]],
        ]]);
        $engine->shouldReceive('listCatalogEntities')->andReturn(['status' => 'success', 'data' => [$store],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 25, 'last_page' => 1]]);
        $engine->shouldReceive('getCatalogEntityProfile')->andReturn(['status' => 'success', 'data' => [
            'record' => $store, 'source_records' => [], 'relationships' => ['parents' => ['account' => $account],
                'children' => ['orders' => ['entity_type' => 'orders', 'label' => 'Orders', 'total_count' => 1, 'items' => [[
                    'reference' => 'channeled:mailchimp:orders:9', 'entity_type' => 'orders', 'name' => 'Order 1001', 'platform_id' => 'order-key', 'status' => null,
                ]]]]],
            'metrics' => ['summary' => [], 'series' => [], 'coverage' => ['notice' => 'Commerce performance is available in Connected Stores.']],
        ]]);
        $engine->shouldReceive('getCatalogEntityActivity')->andReturn(['status' => 'success', 'data' => [
            'items' => [], 'next_cursor' => null, 'has_more' => false,
        ]]);
        $this->app->instance(RemoteEngineService::class, $engine);
        $preview = Livewire::test(MailchimpDashboard::class)->assertSuccessful()->assertSee('Store currency');
        $list = Livewire::test(CatalogEntity::class, ['entity' => 'stores'])->assertSuccessful()->assertSee('Example connected store');
        $profile = Livewire::test(CatalogEntity::class, ['entity' => 'stores', 'record' => $store['reference']])
            ->assertSuccessful()->assertSee('Example connected store')->assertSee('Order 1001')
            ->assertSee('Open Connected Stores')->assertDontSee('No daily metric observations were recorded');

        if (getenv('MAILCHIMP_BROWSER_FIXTURES') === '1') {
            $directory = storage_path('framework/testing/mailchimp-commerce');
            if (!is_dir($directory)) mkdir($directory, 0777, true);
            foreach (['preview' => $preview, 'stores' => $list, 'profile' => $profile] as $name => $page) {
                file_put_contents($directory.'/'.$name.'.html', $page->html());
            }
        }
    }
}
