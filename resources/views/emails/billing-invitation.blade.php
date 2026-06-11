<!DOCTYPE html>
<html>
<head>
    <title>Invitation to join Billing Profile: {{ $invitation->billingProfile->name }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <h2>You've been invited!</h2>
    <p>Hello,</p>
    <p>You have been invited to collaborate and share the Billing Profile <strong>{{ $invitation->billingProfile->name }}</strong> on APIs Hub.</p>
    
    <p>If you don't have an account, you will need to create one using this email address ({{ $invitation->email }}).</p>
    
    <div style="margin: 30px 0;">
        <a href="{{ route('filament.account.auth.login', ['billing_invitation' => $invitation->token]) }}" style="background-color: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Accept Invitation</a>
    </div>
    
    <p style="font-size: 12px; color: #9CA3AF;">This invitation expires in 7 days.</p>
</body>
</html>
