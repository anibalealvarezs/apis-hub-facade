<?php

namespace App\Http\Controllers;

use App\Models\BillingInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingInvitationController extends Controller
{
    public function accept(Request $request, $token)
    {
        $invitation = BillingInvitation::where('token', $token)->where('status', 'pending')->firstOrFail();

        // 1. Check expiration
        if ($invitation->expires_at->isPast()) {
            return redirect('/')->withErrors(['invitation' => 'This invitation has expired.']);
        }

        // 2. If user is logged in
        if (Auth::check()) {
            $user = Auth::user();

            // Verify email match
            if (strtolower($user->email) !== strtolower($invitation->email)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $request->session()->put('billing_invitation_token', $token);
                return redirect()->route('filament.account.auth.login')
                    ->with('warning', 'The invitation is for a different email. Please log in or register with ' . $invitation->email);
            }

            // Bind profile
            $this->processInvitation($user, $invitation);

            return redirect()->route('filament.account.pages.dashboard')
                ->with('success', 'Invitation accepted successfully!');
        }

        // 3. If user is not logged in
        $request->session()->put('billing_invitation_token', $token);
        
        return redirect()->route('filament.account.auth.register')
            ->with('info', 'Please create an account to accept the billing invitation.');
    }

    protected function processInvitation($user, $invitation)
    {
        if (!$user->sharedBillingProfiles()->where('billing_profile_id', $invitation->billing_profile_id)->exists()) {
            $user->sharedBillingProfiles()->attach($invitation->billing_profile_id, ['role' => $invitation->role]);
        }

        $invitation->update(['status' => 'accepted']);
    }
}
