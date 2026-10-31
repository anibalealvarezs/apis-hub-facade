@once
    <style>
        .gsc-search-types-warning { border: 1px solid #f59e0b; border-left: 5px solid #ea580c; border-radius: .75rem; background: #fff7ed; color: #7c2d12; padding: 1rem; font-size: .875rem; line-height: 1.6; }
        .gsc-search-types-warning__title { display: flex; align-items: center; gap: .5rem; color: #9a3412; font-weight: 700; margin-bottom: .5rem; }
        .gsc-search-types-warning__icon { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }
        .gsc-search-types-warning__tag { border-radius: .25rem; padding: .125rem .375rem; font-weight: 700; background: #e0f2fe; color: #075985; box-decoration-break: clone; }
        .dark .gsc-search-types-warning { background: #431407; border-color: #d97706; border-left-color: #fb923c; color: #ffedd5; }
        .dark .gsc-search-types-warning__title { color: #fdba74; }
        .dark .gsc-search-types-warning__tag { background: #164e63; color: #cffafe; }
    </style>
@endonce
<div role="alert" class="gsc-search-types-warning">
    <p class="gsc-search-types-warning__title">
        <svg class="gsc-search-types-warning__icon" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m10.3 3.9-8 14a2 2 0 0 0 1.7 3h16a2 2 0 0 0 1.7-3l-8-14a2 2 0 0 0-3.4 0ZM12 9v4m0 4h.01"/></svg>
        {{ __('Sync Duration & Schedule Notice') }}
    </p>
    <p>
        {{ __('Standard search') }} (<strong class="gsc-search-types-warning__tag">Web</strong>) {{ __('is always synchronized by default. Each additional search type selected below requires its own independent multi-pass retrieval and reconciliation process, which dramatically increases data synchronization times.') }}
    </p>
    <p class="mt-2 text-xs opacity-90">
        {{ __('To prevent schedule collisions and quota contention on day-to-day sync jobs—especially if you have multiple properties—we strongly encourage selecting only the specific additional search types you actively analyze.') }}
    </p>
</div>
