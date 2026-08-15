<div class="rounded-r-xl border-l-4 fb-rl-warning-box shadow-sm mt-4">
    <div class="flex items-start gap-4">
        <svg class="w-6 h-6 shrink-0 mt-0.5 fb-rl-warning-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
            <h3 class="text-base font-bold tracking-tight fb-rl-warning-text">{{ __('Rate Limits & Inactive Assets') }}</h3>
            <p class="text-sm mt-1 leading-relaxed fb-rl-warning-subtext">
                {{ __('Facebook\'s API rate limits are heavily influenced by the recent engagement your Pages and IG Accounts receive. Pages with a large volume of content but very low interaction face much stricter rate limits, increasing the risk of synchronization interruptions.') }} <strong class="font-semibold fb-rl-warning-text">{{ __('We strongly recommend disabling inactive assets (those with minimal analytic value) to prevent rate limit bottlenecks and preserve your subscription quota.') }}</strong>
            </p>
        </div>
    </div>
</div>
