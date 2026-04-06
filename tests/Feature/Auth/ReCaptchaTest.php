<?php

namespace Tests\Feature\Auth;

use Anibalealvarezs\GoogleApi\Services\Recaptcha\RecaptchaApi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class ReCaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Force configuration for reCAPTCHA during tests
        Config::set('services.recaptcha.site_key', 'test-site-key');
        Config::set('services.recaptcha.project_id', 'test-project-id');
        Config::set('services.recaptcha.api_key', 'test-api-key');

        // Allow routes to be accessed
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /**
     * Test that login fails if the recaptcha token is missing.
     */
    public function test_login_fails_if_recaptcha_token_is_missing(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['recaptcha_token']);
        $this->assertGuest();
    }

    /**
     * Test that login fails if the recaptcha token is invalid or low score.
     */
    public function test_login_fails_if_recaptcha_validation_fails(): void
    {
        // Mock the RecaptchaApi to return false (invalid/low score)
        $mock = Mockery::mock(RecaptchaApi::class);
        $mock->shouldReceive('verifyToken')->andReturn(false);
        
        $this->app->bind(RecaptchaApi::class, function () use ($mock) {
            return $mock;
        });

        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'recaptcha_token' => 'invalid-token',
        ]);

        $response->assertSessionHasErrors(['recaptcha_token']);
        $this->assertGuest();
    }

    /**
     * Test that login succeeds if recaptcha is valid.
     */
    public function test_login_succeeds_if_recaptcha_is_valid(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Mock the RecaptchaApi to return true (valid)
        $mock = Mockery::mock(RecaptchaApi::class);
        $mock->shouldReceive('verifyToken')->andReturn(true);
        
        $this->app->bind(RecaptchaApi::class, function () use ($mock) {
            return $mock;
        });

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'recaptcha_token' => 'valid-token',
        ]);

        // In Filament, success login redirects to dashboard
        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that login proceeds if the Google API service fails (failsafe).
     */
    public function test_login_proceeds_if_recaptcha_service_crashes(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Mock the RecaptchaApi to throw an exception
        $mock = Mockery::mock(RecaptchaApi::class);
        $mock->shouldReceive('verifyToken')->andThrow(new \Exception('Google API Timeout'));
        
        $this->app->bind(RecaptchaApi::class, function () use ($mock) {
            return $mock;
        });

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'recaptcha_token' => 'timeout-token',
        ]);

        // Middleware catch block logs error but allows the request
        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }
}
