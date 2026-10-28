<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_redirects_to_google_with_proper_identity_scopes()
    {
        $response = $this->get(route('auth.google.redirect'));

        // Socialite redirects to Google accounts oauth URL
        $response->assertStatus(302);
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    /** @test */
    public function it_matches_and_logs_in_existing_user_by_email()
    {
        $existingUser = User::factory()->create([
            'email' => 'marketing@agency.com',
            'name' => 'Original Name',
            'google_id' => null,
        ]);

        $project = \App\Models\Project::factory()->create([
            'user_id' => $existingUser->id,
            'subdomain' => 'agency-demo',
        ]);
        $existingUser->projects()->attach($project->id);

        $mockGoogleUser = Mockery::mock(SocialiteUser::class);
        $mockGoogleUser->shouldReceive('getId')->andReturn('google-unique-id-12345');
        $mockGoogleUser->shouldReceive('getEmail')->andReturn('marketing@agency.com');
        $mockGoogleUser->shouldReceive('getName')->andReturn('Google Verified Name');
        $mockGoogleUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar.jpg');

        $mockProvider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('redirectUrl')->andReturnSelf();
        $mockProvider->shouldReceive('user')->andReturn($mockGoogleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($mockProvider);

        $response = $this->get(route('auth.google.callback'));

        // Should NOT create duplicate user
        $this->assertEquals(1, User::where('email', 'marketing@agency.com')->count());

        // Should link the google_id and avatar
        $existingUser->refresh();
        $this->assertEquals('google-unique-id-12345', $existingUser->google_id);
        $this->assertEquals('https://lh3.googleusercontent.com/avatar.jpg', $existingUser->avatar);

        // Authenticated as the existing user and redirected to their project dashboard
        $this->assertAuthenticatedAs($existingUser);
        $response->assertRedirect(route('filament.app.pages.dashboard', ['tenant' => 'agency-demo']));
    }

    /** @test */
    public function it_creates_a_new_user_if_email_does_not_exist()
    {
        $mockGoogleUser = Mockery::mock(SocialiteUser::class);
        $mockGoogleUser->shouldReceive('getId')->andReturn('google-new-id-98765');
        $mockGoogleUser->shouldReceive('getEmail')->andReturn('brandnew@startup.io');
        $mockGoogleUser->shouldReceive('getName')->andReturn('New Google User');
        $mockGoogleUser->shouldReceive('getAvatar')->andReturn(null);

        $mockProvider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('redirectUrl')->andReturnSelf();
        $mockProvider->shouldReceive('user')->andReturn($mockGoogleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($mockProvider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertEquals(1, User::where('email', 'brandnew@startup.io')->count());
        $newUser = User::where('email', 'brandnew@startup.io')->first();

        $this->assertEquals('google-new-id-98765', $newUser->google_id);
        $this->assertNotNull($newUser->email_verified_at);
        $this->assertAuthenticatedAs($newUser);
        $response->assertRedirect(route('filament.app.tenant.registration'));
    }
}
