<?php

namespace Tests\Feature;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class SubNavOverrideRenderTest extends TestCase
{
    public function test_sidebar_override_renders_in_livewire_context(): void
    {
        $component = Livewire::test(new class extends Component {
            public function getRenderHookScopes(): array
            {
                return [];
            }

            public function render()
            {
                $navigation = collect([
                    NavigationGroup::make('Knowledge Base')
                        ->items([
                            NavigationItem::make('Analytics')->url('/app/kb/analytics'),
                        ]),
                ]);
                return view('filament-panels::components.page.sub-navigation.sidebar', [
                    'navigation' => $navigation,
                ]);
            }
        });

        $html = $component->html();

        $this->assertStringContainsString('fi-page-sub-navigation-sidebar-ctn', $html);
        $this->assertStringContainsString('fi-subnav-toggle', $html);
        $this->assertStringContainsString('$store.subnav.toggle()', $html);
        $this->assertStringContainsString("window.Alpine.store('subnav'", $html);
        $this->assertStringContainsString('fi-page-sub-navigation-sidebar', $html);
        $this->assertStringContainsString('x-tooltip.html="tooltip"', $html);
        $this->assertStringContainsString('$store.subnav.isOpen', $html);
    }
}
