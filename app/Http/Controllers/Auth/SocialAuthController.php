<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect user to Google OAuth for app authentication/registration.
     */
    public function redirectToGoogle(Request $request)
    {
        $callbackUrl = route('auth.google.callback');

        Log::info('[SocialAuth] Initiating Google redirect', [
            'callback_url' => $callbackUrl,
            'client_ip' => $request->ip(),
        ]);

        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl($callbackUrl)
            ->scopes(['openid', 'profile', 'email'])
            ->with([
                'prompt' => 'select_account',
                'state' => 'app_auth',
            ])
            ->redirect();
    }

    /**
     * Handle the incoming OAuth callback from Google for app authentication.
     */
    public function handleGoogleCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('filament.app.auth.login')
                ->with('error', 'Google authentication was cancelled.');
        }

        try {
            Log::info('[SocialAuth] Google callback entered', [
                'has_code' => $request->has('code'),
                'state' => $request->input('state'),
                'all' => $request->all(),
            ]);

            $callbackUrl = route('auth.google.callback');

            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl($callbackUrl)
                ->user();

            $googleId = $googleUser->getId();
            $email = $googleUser->getEmail();
            $name = $googleUser->getName() ?: explode('@', $email)[0];
            $avatar = $googleUser->getAvatar();

            Log::info('[SocialAuth] Google user resolved', [
                'google_id' => $googleId,
                'email' => $email,
                'name' => $name,
            ]);

            if (!$email) {
                return redirect()->route('filament.app.auth.login')
                    ->with('error', 'Google account does not provide an email address.');
            }

            // 1. Check if user already exists by google_id
            $user = User::where('google_id', $googleId)->first();

            // 2. If not matched by google_id, match by existing verified email
            if (!$user) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Match found: Link google_id and avatar to the existing account
                    $user->google_id = $googleId;
                    if (!$user->avatar && $avatar) {
                        $user->avatar = $avatar;
                    }
                    if (!$user->email_verified_at) {
                        $user->email_verified_at = now();
                    }
                    $user->save();
                    Log::info("[SocialAuth] Linked Google account to existing user: {$email}");
                }
            }

            // 3. If no user exists with this email, register a new account
            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
                Log::info("[SocialAuth] Registered new user via Google auth: {$email}");
            }

            // 4. Log the user in with remember me enabled
            Filament::auth()->login($user, remember: true);
            session()->regenerate();
            Log::info("[SocialAuth] User authenticated via Filament", ['user_id' => $user->id]);

            // 5. Determine redirect destination
            $intended = session()->pull('url.intended');
            $preferredProject = $user->preferredProject();
            $redirectUrl = $preferredProject
                ? route('filament.app.pages.dashboard', ['tenant' => $preferredProject->subdomain])
                : route('filament.app.tenant.registration');

            // If an intended URL exists and is not the login or root page, honor it
            if ($intended && !str_contains($intended, '/login') && !str_contains($intended, '/auth/')) {
                Log::info("[SocialAuth] Redirecting to intended URL: {$intended}");
                return redirect($intended);
            }

            Log::info("[SocialAuth] Redirecting to dashboard URL: {$redirectUrl}");
            return redirect($redirectUrl);

        } catch (\Throwable $e) {
            Log::error('[SocialAuth] Google Social Auth Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('filament.app.auth.login')
                ->with('error', 'Unable to authenticate with Google. Please try again.');
        }
    }
}
