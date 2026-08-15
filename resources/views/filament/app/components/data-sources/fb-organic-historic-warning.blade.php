<style>
    .fb-warning-box { background-color: #fffbeb; border-color: #f59e0b; padding: 1.25rem; }
    .dark .fb-warning-box { background-color: rgba(245, 158, 11, 0.1); border-color: #f59e0b; }
    .fb-warning-text { color: #92400e; }
    .dark .fb-warning-text { color: #fcd34d; }
    .fb-warning-subtext { color: #b45309; }
    .dark .fb-warning-subtext { color: #fde68a; }
    .fb-warning-icon { color: #d97706; }
    .dark .fb-warning-icon { color: #fbbf24; }
</style>
<div class="rounded-r-xl border-l-4 fb-warning-box shadow-sm">
    <div class="flex items-start gap-4">
        <svg class="w-6 h-6 shrink-0 mt-0.5 fb-warning-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
            <h3 class="text-base font-bold tracking-tight fb-warning-text">{{ __('Historic Metrics Limitation') }}</h3>
            <p class="text-sm mt-1 leading-relaxed fb-warning-subtext">
                {{ __('Facebook does not provide historic metrics for posts and media; it only provides daily snapshots. Therefore, we will build the history for your assets by caching the daily data to provide time series starting from today.') }} <strong class="font-semibold fb-warning-text">{{ __('To successfully build these time series without gaps, you must keep the channel and the asset enabled continuously.') }}</strong>
            </p>
        </div>
    </div>
</div>
