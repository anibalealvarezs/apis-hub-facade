<?php

use Tests\TestCase;

class PublicPlansPageTest extends TestCase
{
    /** @test */
    public function it_renders_the_plans_page_in_english()
    {
        $response = $this->get('/plans');

        $response->assertStatus(200);
        $response->assertSee('Plans &amp; Features', false);
        $response->assertSee('Free');
        $response->assertSee('Pro');
        $response->assertSee('Ultra / Founder');
        $response->assertSee('Enterprise');
        $response->assertSee('AI-assisted keywords classification');
        $response->assertDontSee('Clasficiación de keywords de SEO asistida por IA');
        $response->assertSee('application/ld+json', false);
        $response->assertSee('SoftwareApplication');
        $response->assertSee('FAQPage');
        $response->assertSee('rel="canonical"', false);
    }

    /** @test */
    public function it_renders_the_plans_page_in_spanish()
    {
        $response = $this->get('/es/planes');

        $response->assertStatus(200);
        $response->assertSee('Planes y Funcionalidades');
        $response->assertSee('Free');
        $response->assertSee('Pro');
        $response->assertSee('Ultra / Founder');
        $response->assertSee('Enterprise');
        $response->assertSee('Clasficiación de keywords de SEO asistida por IA');
        $response->assertSee('application/ld+json', false);
        $response->assertSee('SoftwareApplication');
        $response->assertSee('FAQPage');
        $response->assertSee('rel="canonical"', false);
    }

    /** @test */
    public function it_has_plans_link_in_welcome_page()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('/plans');

        $responseEs = $this->get('/es');
        $responseEs->assertStatus(200);
        $responseEs->assertSee('/es/planes');
    }
}
