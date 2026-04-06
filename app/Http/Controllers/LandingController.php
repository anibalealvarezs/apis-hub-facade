<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeAlphaLead;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class LandingController extends Controller
{
    /**
     * Show the landing page with obfuscated portal links.
     */
    public function index()
    {
        $gtmId = config('services.gtm.id');
        
        return view('welcome', [
            'portals' => [
                'app' => base64_encode('/app'),
                'admin' => base64_encode('/admin'),
                'docs' => base64_encode('https://docs.apis-hub.cloud'),
            ],
            'gtmId' => ($gtmId && $gtmId !== 'GTM-XXXXXXX') ? $gtmId : null,
        ]);
    }

    /**
     * Collect interest for the alpha launch.
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:leads,email',
        ], [
            'email.unique' => 'You are already on our waitlist!',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        $lead = Lead::create([
            'email' => $request->email,
            'source' => 'landing_page_launch',
            'status' => 'alpha_waitlist',
        ]);

        // Send Welcome Email via Zoho (Synchronous for now, or via Queue if configured)
        Mail::to($lead->email)->send(new WelcomeAlphaLead($lead));

        return back()->with('success', 'Success! You are now on the APIs Hub Alpha waitlist.');
    }

    /**
     * Unsubscribe a lead from the mailing list.
     */
    public function unsubscribe(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired unsubscribe link.');
        }

        $lead = Lead::where('email', $request->email)->first();

        if ($lead) {
            $lead->update(['status' => 'unsubscribed']);
        }

        return view('welcome', [
            'portals' => [
                'app' => base64_encode('/app'),
                'admin' => base64_encode('/admin'),
                'docs' => base64_encode('https://docs.apis-hub.cloud'),
            ],
            'gtmId' => config('services.gtm.id'),
            'unsubscribe_message' => 'You have been successfully unsubscribed from the APIs Hub Alpha waitlist.'
        ]);
    }
}
