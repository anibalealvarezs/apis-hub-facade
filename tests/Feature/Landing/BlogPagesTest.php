<?php

namespace Tests\Feature\Landing;

use Tests\TestCase;

class BlogPagesTest extends TestCase
{
    /** @test */
    public function it_renders_the_blog_index_page()
    {
        $response = $this->get('/blog');
        $response->assertStatus(200);

        $responseEs = $this->get('/es/blog');
        $responseEs->assertStatus(200);
    }

    /** @test */
    public function it_renders_the_fix_looker_quota_article()
    {
        $response = $this->get('/blog/fix-gsc-looker-quota-limits');
        $response->assertStatus(200);

        $responseEs = $this->get('/es/blog/solucion-error-cuota-looker-studio-gsc');
        $responseEs->assertStatus(200);
    }

    /** @test */
    public function it_renders_supported_channels_and_faq_pages()
    {
        $responseChannels = $this->get('/solutions/supported-channels');
        $responseChannels->assertStatus(200);

        $responseFaq = $this->get('/faq');
        $responseFaq->assertStatus(200);
    }
}
