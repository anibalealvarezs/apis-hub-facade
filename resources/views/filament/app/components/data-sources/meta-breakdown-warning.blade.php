
@php
    $notice = explode(':country', __('Breakdowns are temporarily unavailable for campaigns targeting people in :country.'));
    $action = explode(':option', __('Select :option to collect data for these campaigns.'));
@endphp
<div role="alert" class="meta-breakdown-warning">
    <p class="meta-breakdown-warning__title">
        <svg class="meta-breakdown-warning__icon" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m10.3 3.9-8 14a2 2 0 0 0 1.7 3h16a2 2 0 0 0 1.7-3l-8-14a2 2 0 0 0-3.4 0ZM12 9v4m0 4h.01"/></svg>
        {{ __('Temporary breakdown limitation') }}
    </p>
    <p>{{ $notice[0] }}<strong class="meta-breakdown-warning__country">{{ __('United States') }}</strong>{{ $notice[1] ?? '' }}</p>
    <p class="meta-breakdown-warning__action">{{ $action[0] }}<strong class="meta-breakdown-warning__option">{{ __('No breakdown (totals only)') }}</strong>{{ $action[1] ?? '' }}</p>
</div>
