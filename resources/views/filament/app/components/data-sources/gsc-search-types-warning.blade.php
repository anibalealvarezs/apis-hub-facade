
<div role="alert" class="gsc-search-types-warning">
    <p class="gsc-search-types-warning__title">
        <svg class="gsc-search-types-warning__icon" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m10.3 3.9-8 14a2 2 0 0 0 1.7 3h16a2 2 0 0 0 1.7-3l-8-14a2 2 0 0 0-3.4 0ZM12 9v4m0 4h.01"/></svg>
        {{ __('Sync Duration & Schedule Notice') }}
    </p>
    <p>
        {{ __('Standard search') }} (<strong class="gsc-search-types-warning__tag">Web</strong>) {{ __('is always synchronized by default. Each additional search type selected below requires its own independent multi-pass retrieval and reconciliation process, which dramatically increases data synchronization times.') }}
    </p>
    <details class="mt-2 text-xs">
        <summary class="cursor-pointer font-semibold">{{ __('Scheduling guidance') }}</summary>
        <p class="mt-2">
            {{ __('To prevent schedule collisions and quota contention on day-to-day sync jobs—especially if you have multiple properties—we strongly encourage selecting only the specific additional search types you actively analyze.') }}
        </p>
    </details>
</div>
