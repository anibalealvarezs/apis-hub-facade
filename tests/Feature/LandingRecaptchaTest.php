<?php

namespace Tests\Feature;

use App\Models\Lead;
use Anibalealvarezs\GoogleApi\Services\Recaptcha\RecaptchaApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class LandingRecaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected $recaptchaMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mocking the RecaptchaApi service
        $this->recaptchaMock = Mockery::mock(RecaptchaApi::class);
        
        // Global binding to ensure the middleware gets the mock
        $this->app->bind(RecaptchaApi::class, function () {
            return $this->recaptchaMock;
        });
    }

    /** @test */
    public function it_successfully_subscribes_with_valid_recaptcha_token()
    {
        // 1. Mock valid verification
        $this->recaptchaMock->shouldReceive('verifyToken')
            ->once()
            ->andReturn(true);

        // 2. Submit form
        $response = $this->post(route('landing.subscribe'), [
            'email' => 'test-alpha@apis-hub.cloud',
            'recaptcha_token' => 'valid-mock-token'
        ]);

        // 3. Assertions
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leads', ['email' => 'test-alpha@apis-hub.cloud']);
    }

    /** @test */
    public function it_fails_subscription_when_recaptcha_is_invalid()
    {
        // 1. Mock invalid verification
        $this->recaptchaMock->shouldReceive('verifyToken')
            ->once()
            ->andReturn(false);

        // 2. Submit form
        $response = $this->post(route('landing.subscribe'), [
            'email' => 'spam-bot@evil.com',
            'recaptcha_token' => 'fake-malicious-token'
        ]);

        // 3. Assertions
        $response->assertRedirect();
        $response->assertSessionHasErrors(['recaptcha_token']);
        $this->assertDatabaseMissing('leads', ['email' => 'spam-bot@evil.com']);
    }

    /** @test */
    public function it_requires_recaptcha_token_completely()
    {
        // Submit form without token
        $response = $this->post(route('landing.subscribe'), [
            'email' => 'forgetful@user.com'
            // recaptcha_token missing
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['recaptcha_token']);
    }
}
