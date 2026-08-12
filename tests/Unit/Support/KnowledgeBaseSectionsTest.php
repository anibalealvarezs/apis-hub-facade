<?php

namespace Tests\Unit\Support;

use App\Filament\App\Pages\AccountStructureReference;
use App\Filament\App\Pages\SubscriptionFeatures;
use App\Support\KnowledgeBaseSections;
use Illuminate\Support\Str;
use Tests\TestCase;

class KnowledgeBaseSectionsTest extends TestCase
{
    public function test_anchors_for_page_return_slug_ids_matching_the_blades(): void
    {
        $anchors = KnowledgeBaseSections::anchorsForPage(AccountStructureReference::class);

        $this->assertNotEmpty($anchors);

        foreach ($anchors as $anchor) {
            $this->assertArrayHasKey('title', $anchor);
            $this->assertArrayHasKey('id', $anchor);
            $this->assertSame(Str::slug($anchor['title']), $anchor['id']);
        }

        $this->assertSame(
            [
                'title' => 'User Accounts vs. Billing Profiles',
                'id' => 'user-accounts-vs-billing-profiles',
            ],
            $anchors[0],
        );
    }

    public function test_anchors_for_page_include_tier_sections_on_subscription_features(): void
    {
        $anchors = KnowledgeBaseSections::anchorsForPage(SubscriptionFeatures::class);

        $this->assertSame(
            ['free', 'pro', 'ultra', 'enterprise', 'important-note-on-api-access'],
            array_column($anchors, 'id'),
        );
    }

    public function test_anchors_for_page_returns_empty_for_unknown_page(): void
    {
        $this->assertSame([], KnowledgeBaseSections::anchorsForPage(self::class));
    }

    public function test_anchors_for_url_returns_empty_when_no_page_matches(): void
    {
        $this->assertSame([], KnowledgeBaseSections::anchorsFor('http://localhost/app/unknown-page'));
    }

    public function test_every_registered_page_has_anchors(): void
    {
        foreach (KnowledgeBaseSections::registeredPages() as $page) {
            $this->assertNotEmpty(
                KnowledgeBaseSections::anchorsForPage($page),
                "Page [{$page}] should expose anchor sections.",
            );
        }
    }
}
