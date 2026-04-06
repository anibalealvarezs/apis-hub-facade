<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
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

        Lead::create([
            'email' => $request->email,
            'source' => 'landing_page_launch',
            'status' => 'alpha_waitlist',
        ]);

        return back()->with('success', 'Welcome to the APIs Hub Alpha! We\'ll be in touch soon.');
    }
}
