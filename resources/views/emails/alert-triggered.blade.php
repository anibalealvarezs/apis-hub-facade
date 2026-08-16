@component('mail::message')
# Alert Triggered: {{ $alert->name }}

Hello {{ $user->name }},

An alert threshold has been reached for project **{{ $projectName }}**.

@component('mail::panel')
**Alert Details:**
- **Source:** {{ $alertLog->source_summary }}
- **Asset/Target:** {{ $alertLog->asset_summary }}
- **Evaluated Value:** {{ $evaluatedVal }}
- **Threshold ({{ ucfirst($alertLog->threshold_type ?? 'limit') }}):** {{ $thresholdVal }}
- **Triggered At:** {{ $alertLog->triggered_at->format('Y-m-d H:i:s T') }}
@endcomponent

@if ($alert->description)
_{{ $alert->description }}_
@endif

@component('mail::button', ['url' => $alertUrl])
View Alert & Logs
@endcomponent

Thank you for using APIs Hub.
@endcomponent
