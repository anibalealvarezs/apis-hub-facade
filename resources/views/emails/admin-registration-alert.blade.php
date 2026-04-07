<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New User Registration | APIs Hub</title>
    <style>
        /* APIs Hub Admin Email — Light Theme */
        body { 
            margin: 0; 
            padding: 0; 
            width: 100% !important; 
            background-color: #F9FAFB;
            font-family: 'Inter', 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: none;
            color: #3B3B3B;
        }
        .wrapper { 
            width: 100%; 
            table-layout: fixed; 
            background-color: #F9FAFB; 
            padding: 40px 0;
        }
        .container { 
            max-width: 570px; 
            margin: 0 auto; 
            background-color: #ffffff; 
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .header-bar {
            height: 4px;
            background: linear-gradient(90deg, #00A7F9 0%, #00CAC4 100%);
        }
        .header {
            padding: 32px 40px 0;
            text-align: left;
        }
        .content {
            padding: 24px 40px 40px;
            color: #3B3B3B;
        }
        .badge {
            display: inline-block;
            background-color: rgba(239, 68, 68, 0.08);
            color: #DC2626;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1A1A1A;
            margin: 0 0 16px;
            line-height: 1.3;
            letter-spacing: -0.025em;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 16px;
            color: #4B5563;
        }
        .detail-card {
            background-color: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 24px 0;
        }
        .detail-row {
            margin-bottom: 12px;
        }
        .detail-row:last-child {
            margin-bottom: 0;
        }
        .detail-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9CA3AF;
            margin: 0 0 2px;
        }
        .detail-value {
            font-size: 15px;
            font-weight: 500;
            color: #1A1A1A;
            margin: 0;
        }
        .btn {
            display: inline-block;
            background-color: #00A7F9;
            color: #ffffff !important;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            margin-top: 8px;
        }
        .footer {
            padding: 24px 40px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
        }
        .footer p {
            color: #9CA3AF;
            font-size: 12px;
            margin: 0;
            text-align: center;
        }
        a { color: #00A7F9; text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header-bar"></div>
            <div class="header">
                <img src="{{ config('app.url') }}/images/branding/apishub-trans-620.png" alt="APIs Hub" height="48" style="display: block; border: 0;">
            </div>
            <div class="content">
                <div class="badge">🚨 Admin Alert</div>
                <h1>New User Registration</h1>
                
                <p>A new user has just registered on the platform and is pending email verification.</p>
                
                <div class="detail-card">
                    <div class="detail-row">
                        <p class="detail-label">Full Name</p>
                        <p class="detail-value">{{ $userName }}</p>
                    </div>
                    <div class="detail-row">
                        <p class="detail-label">Email Address</p>
                        <p class="detail-value">{{ $userEmail }}</p>
                    </div>
                    <div class="detail-row">
                        <p class="detail-label">Registered At</p>
                        <p class="detail-value">{{ $registeredAt }}</p>
                    </div>
                </div>

                <a href="{{ config('app.url') }}/admin" class="btn">View in Admin Panel →</a>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} APIs Hub Network. Managed by the Orchestrator Engine.</p>
                <p style="margin-top: 4px;">This is an automated administrative alert. No reply needed.</p>
            </div>
        </div>
    </div>
</body>
</html>
