<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class VerifyUrlDiagnostic extends Command
{
    protected $signature = 'debug:verify-url {email}';
    protected $description = 'Generate and inspect a verification URL for debugging';

    public function handle(): void
    {
        $user = \App\Models\User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error("User not found: {$this->argument('email')}");
            return;
        }

        $this->info("=== User Info ===");
        $this->line("ID: {$user->id}");
        $this->line("Email: {$user->email}");
        $this->line("Verified: " . ($user->hasVerifiedEmail() ? 'YES' : 'NO'));

        $this->newLine();
        $this->info("=== URL Generation Context ===");
        $this->line("APP_URL: " . config('app.url'));
        $this->line("URL::forceScheme active: " . (parse_url(config('app.url'), PHP_URL_SCHEME) === 'https' ? 'https' : 'http'));
        $this->line("URL root: " . url('/'));

        // Generate the hash the same way Filament does
        $hash = sha1($user->getEmailForVerification());
        $this->newLine();
        $this->info("=== Hash Check ===");
        $this->line("Email for verification: {$user->getEmailForVerification()}");
        $this->line("SHA1 hash: {$hash}");

        // Generate the Filament verify URL
        $panel = \Filament\Facades\Filament::getPanel('app');
        \Filament\Facades\Filament::setCurrentPanel($panel);
        $verifyUrl = $panel->getVerifyEmailUrl($user);

        $this->newLine();
        $this->info("=== Generated Verify URL ===");
        $this->line($verifyUrl);

        // Parse and check signature
        $parsed = parse_url($verifyUrl);
        $this->newLine();
        $this->info("=== URL Parts ===");
        $this->line("Scheme: " . ($parsed['scheme'] ?? 'MISSING'));
        $this->line("Host: " . ($parsed['host'] ?? 'MISSING'));
        $this->line("Path: " . ($parsed['path'] ?? 'MISSING'));

        // Test signature validity
        $request = \Illuminate\Http\Request::create($verifyUrl, 'GET');
        $isValid = URL::hasValidSignature($request);
        $this->newLine();
        $this->info("=== Signature Validation ===");
        $this->line("Valid: " . ($isValid ? '✅ YES' : '❌ NO'));

        if (!$isValid) {
            $this->warn("The signature does NOT match. This means the URL that the user clicks");
            $this->warn("is being reconstructed differently than the one that was signed.");
            $this->warn("Common cause: HTTP/HTTPS mismatch between proxy and Octane.");
        }
    }
}
