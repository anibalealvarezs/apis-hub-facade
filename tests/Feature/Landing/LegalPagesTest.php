<?php

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    /** @test */
    public function it_renders_privacy_policy_in_both_languages_with_unified_header_and_footer()
    {
        // English
        $response = $this->get('/privacy');
        $response->assertStatus(200);
        $response->assertSee('PRIVACY POLICY');
        $response->assertSee('Plans &amp; Features', false);
        $response->assertSee('Home');
        $response->assertSee('Try beta');
        $response->assertSee('glass-panel');

        // Spanish
        $responseEs = $this->get('/es/privacy');
        $responseEs->assertStatus(200);
        $responseEs->assertSee('POLÍTICA DE PRIVACIDAD');
        $responseEs->assertSee('Planes y Funcionalidades');
        $responseEs->assertSee('Inicio');
        $responseEs->assertSee('glass-panel');
    }

    /** @test */
    public function it_renders_terms_of_service_with_unified_header()
    {
        $response = $this->get('/tos');
        $response->assertStatus(200);
        $response->assertSee('TERMS OF SERVICE');
        $response->assertSee('Plans &amp; Features', false);
        $response->assertSee('Try beta');

        $responseEs = $this->get('/es/tos');
        $responseEs->assertStatus(200);
        $responseEs->assertSee('TÉRMINOS DE SERVICIO');
        $responseEs->assertSee('Planes y Funcionalidades');
    }

    /** @test */
    public function it_renders_data_deletion_page_with_unified_header()
    {
        $response = $this->get('/data-deletion');
        $response->assertStatus(200);
        $response->assertSee('DATA DELETION INSTRUCTIONS');
        $response->assertSee('Plans &amp; Features', false);

        $responseEs = $this->get('/es/data-deletion');
        $responseEs->assertStatus(200);
        $responseEs->assertSee('INSTRUCCIONES DE ELIMINACIÓN DE DATOS');
        $responseEs->assertSee('Planes y Funcionalidades');
    }
}
