@extends('legal.layout')

@section('title', 'Terms of Service')

@section('content')
    <h1>TERMS OF SERVICE</h1>

    <p>Welcome to APIs Hub. By using our application, you agree to comply with and be bound by the following terms and conditions of use.</p>

    <h2>1. Description of Service</h2>
    <p>APIs Hub is a specialized infrastructure and reporting tool designed to aggregate and visualize data from various marketing APIs, including but not limited to Meta Ads (Facebook), Google Ads, and Klaviyo.</p>

    <h2>2. Beta Status & Service Availability</h2>
    <p>APIs Hub is currently in <strong>Beta</strong>. While we strive for maximum uptime, the service is provided "as is" and "as available". We do not offer Service Level Agreements (SLAs) regarding uptime, API synchronization delays, or feature stability during the Beta phase. Occasional downtime or data delays may occur as we upgrade our infrastructure.</p>

    <h2>3. Use of Data</h2>
    <p>Our application accesses data through official APIs using OAuth authentication. By connecting your accounts, you grant APIs Hub permission to retrieve and store reporting metrics solely for the purpose of providing you with visual dashboards and automated reports. We do not modify or write data back to your connected platforms.</p>

    <h2>4. Fair Use & API Limits</h2>
    <p>To ensure the stability of the APIs Hub Network, you agree to a Fair Use Policy. Excessive, automated, or malicious polling of our synchronization endpoints or attempting to bypass third-party API rate limits may result in the temporary throttling or permanent suspension of your account and connected projects.</p>

    <h2>5. Billing & Subscriptions</h2>
    <p>If you upgrade to a paid tier via Stripe or PayPal, subscriptions are billed in advance on a recurring basis. All fees are non-refundable unless otherwise required by applicable law. You may cancel your subscription at any time through your account settings, and you will retain access to the paid features until the end of your current billing cycle.</p>

    <h2>6. User Responsibilities</h2>
    <ul>
        <li>You are responsible for maintaining the confidentiality of your access tokens and account credentials.</li>
        <li>You agree not to use the application for any illegal or unauthorized purpose.</li>
        <li>You must comply with all applicable terms and policies of the third-party platforms (e.g., Meta Developer Policies) you connect to APIs Hub.</li>
    </ul>

    <h2>7. Limitation of Liability</h2>
    <p>APIs Hub is provided "as is" without any warranties of any kind. Under no circumstances shall Aníbal Álvarez or the development team be liable for any direct, indirect, incidental, or consequential damages resulting from the use of this software or the inability to use it.</p>

    <h2>8. Modifications to Service</h2>
    <p>We reserve the right to modify or discontinue, temporarily or permanently, the service (or any part thereof) with or without notice.</p>

    <h2>9. Privacy</h2>
    <p>Your use of the service is also governed by our <a href="{{ app()->getLocale() === 'es' ? route('legal.privacy.es') : route('legal.privacy') }}" class="text-brand-blue hover:underline">Privacy Policy</a>.</p>

    <hr>
    <p><strong>Last updated: March 24, 2026</strong><br>
    For queries regarding these terms, contact us at <a href="mailto:admin@apis-hub.cloud">admin@apis-hub.cloud</a>.</p>
@endsection
