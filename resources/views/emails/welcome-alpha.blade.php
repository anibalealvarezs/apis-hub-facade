<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Welcome to APIs Hub Alpha</title>
    <style>
        /* Modern Email Styles - Dark Mode (Standardized for major clients) */
        body { 
            margin: 0; 
            padding: 0; 
            width: 100% !important; 
            background-color: #0B1120; 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            -webkit-font-smoothing: antialiased;
        }
        .wrapper { 
            width: 100%; 
            table-layout: fixed; 
            background-color: #0B1120; 
            padding: 40px 0;
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background-color: #111827; 
            border: 1px solid #1F2937;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
        }
        .header-bar {
            height: 4px;
            background: linear-gradient(90deg, #00A7F9 0%, #00CAC4 100%);
        }
        .content {
            padding: 40px;
            color: #D1D5DB;
        }
        .logo-text {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 30px;
            text-align: left;
            color: #F9FAFB;
            letter-spacing: -0.025em;
        }
        .logo-accent {
            color: #00CAC4;
        }
        h1 {
            font-size: 30px;
            font-weight: 800;
            color: #F9FAFB;
            margin-bottom: 20px;
            line-height: 1.2;
            letter-spacing: -0.05em;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
            font-weight: 300;
        }
        .highlight {
            color: #00A7F9;
            font-weight: 600;
        }
        .footer {
            padding: 30px 40px;
            background-color: #0F172A;
            border-top: 1px solid #1F2937;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #6B7280;
        }
        .badge {
            display: inline-block;
            background-color: rgba(0, 167, 249, 0.1);
            color: #00A7F9;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 20px;
        }
        /* Resets for HTML Email */
        a { color: #00A7F9; text-decoration: none; font-weight: 600; }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #00A7F9 0%, #00CAC4 100%);
            color: #FFFFFF !important;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header-bar"></div>
            <div class="content">
                <div class="logo-text">
                    <img src="{{ $message->embed(public_path('images/branding/apishub-trans-light-620.png')) }}" alt="APIs Hub" height="48" style="display: block; border: 0;">
                </div>
                
                <div class="badge">Alpha Confirmation</div>
                <h1>Welcome to the waitlist.</h1>
                
                <p>Hello,</p>
                
                <p>You’ve been added to the <span class="highlight">APIs Hub Alpha</span> waitlist. We’re building the elite infrastructure for <span class="highlight">Unified Data Analytics</span>, and you're now first in line to experience the Orchestrator Engine v1.0.</p>
                
                <p>A few highlights of what's coming:</p>
                <ul style="list-style-type: none; padding: 0;">
                    <li style="margin-bottom: 12px; border-left: 3px solid #00CAC4; padding-left: 15px;">
                        <strong style="color: #F9FAFB;">Computed Metrics</strong><br>
                        <span style="font-size: 14px;">Access ready-to-use data aggregations and totals, eliminating the need for expensive post-processing.</span>
                    </li>
                    <li style="margin-bottom: 12px; border-left: 3px solid #00A7F9; padding-left: 15px;">
                        <strong style="color: #F9FAFB;">Zero Gaps</strong><br>
                        <span style="font-size: 14px;">Automatic gap-filling for continuous, chart-ready time series.</span>
                    </li>
                    <li style="margin-bottom: 12px; border-left: 3px solid #F9FAFB; padding-left: 15px;">
                        <strong style="color: #F9FAFB;">Unified Integration</strong><br>
                        <span style="font-size: 14px;">A single engine for cross-platform data from multi-platform sources.</span>
                    </li>
                    <li style="margin-bottom: 12px; border-left: 3px solid #00CAC4; padding-left: 15px;">
                        <strong style="color: #F9FAFB;">Integrated Dashboards</strong><br>
                        <span style="font-size: 14px;">Real-time visualization of your unified data through an elite interface.</span>
                    </li>
                    <li style="margin-bottom: 12px; border-left: 3px solid #00A7F9; padding-left: 15px;">
                        <strong style="color: #F9FAFB;">Headless API Access</strong><br>
                        <span style="font-size: 14px;">Deploy your data into third-party apps or custom frontends via Managed API.</span>
                    </li>
                    <li style="margin-bottom: 12px; border-left: 3px solid #F9FAFB; padding-left: 15px;">
                        <strong style="color: #F9FAFB;">MCP Server Support</strong><br>
                        <span style="font-size: 14px;">Native Model Context Protocol integration to provide high-context data directly to AI agents.</span>
                    </li>
                    <li style="margin-bottom: 12px; border-left: 3px solid #00CAC4; padding-left: 15px;">
                        <strong style="color: #F9FAFB;">Elite Caching</strong><br>
                        <span style="font-size: 14px;">Sub-5ms response times via Redis-optimized pipelines.</span>
                    </li>
                </ul>

                <p>We will contact you as soon as an instance is ready for your project.</p>
                
                <p>Best regards,<br><strong>The APIs Hub Team</strong></p>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} APIs Hub Network. Managed by the Orchestrator Engine.<br>
                This is an automated alpha confirmation. No reply needed.
                <br><br>
                If you'd like to stop receiving these updates, you can <a href="{{ URL::signedRoute('landing.unsubscribe', ['email' => $lead->email]) }}" style="color: #6B7280; text-decoration: underline;">unsubscribe here</a>.
            </div>
        </div>
    </div>
</body>
</html>
