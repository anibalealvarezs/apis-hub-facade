@once
    <style>
        .meta-breakdown-warning { border: 1px solid #f59e0b; border-left: 5px solid #ea580c; border-radius: .75rem; background: #fff7ed; color: #7c2d12; padding: 1rem; font-size: .875rem; line-height: 1.6; }
        .meta-breakdown-warning__title { display: flex; align-items: center; gap: .5rem; color: #9a3412; font-weight: 700; margin-bottom: .5rem; }
        .meta-breakdown-warning__icon { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }
        .meta-breakdown-warning__country, .meta-breakdown-warning__option { border-radius: .25rem; padding: .125rem .375rem; font-weight: 700; box-decoration-break: clone; }
        .meta-breakdown-warning__country { background: #ffe4e6; color: #9f1239; }
        .meta-breakdown-warning__action { margin-top: .5rem; }
        .meta-breakdown-warning__option { background: #e0f2fe; color: #075985; }
        .dark .meta-breakdown-warning { background: #431407; border-color: #d97706; border-left-color: #fb923c; color: #ffedd5; }
        .dark .meta-breakdown-warning__title { color: #fdba74; }
        .dark .meta-breakdown-warning__country { background: #881337; color: #ffe4e6; }
        .dark .meta-breakdown-warning__option { background: #164e63; color: #cffafe; }
    </style>
@endonce
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
