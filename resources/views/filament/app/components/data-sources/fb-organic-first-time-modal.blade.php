<div
    x-data="{ showWarningModal: false }"
    x-init="
        setTimeout(() => {
            if (!localStorage.getItem('fb_organic_warnings_seen_v1')) {
                showWarningModal = true;
            }
        }, 500);
    "
>
    <div x-show="showWarningModal" style="display: none;" class="fb-warning-modal-backdrop" x-transition.opacity>
        <div @click.away="localStorage.setItem('fb_organic_warnings_seen_v1', 'true'); showWarningModal = false" class="fb-warning-modal-dialog" x-transition.scale.origin.bottom>
            {{-- Close Button --}}
            <button
                type="button"
                @click="localStorage.setItem('fb_organic_warnings_seen_v1', 'true'); showWarningModal = false"
                class="fb-warning-modal-close"
                aria-label="{{ __('Close') }}"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Modal Title --}}
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 pr-8">
                <svg class="w-7 h-7 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                {{ __('Important: Facebook Organic') }}
            </h2>

            {{-- Warning Boxes --}}
            <div class="flex flex-col gap-4">
                <div class="rounded-r-xl border-l-4 fb-modal-warning-box shadow-sm">
                    <div class="flex items-start gap-4">
                        <svg class="w-6 h-6 shrink-0 mt-0.5 fb-modal-warning-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h3 class="text-base font-bold tracking-tight fb-modal-warning-text">{{ __('Historic Metrics Limitation') }}</h3>
                            <p class="text-sm mt-1 leading-relaxed fb-modal-warning-subtext">
                                {{ __('Facebook does not provide historic metrics for posts and media; it only provides daily snapshots. Therefore, we will build the history for your assets by caching the daily data to provide time series starting from today.') }} <strong class="font-semibold fb-modal-warning-text">{{ __('To successfully build these time series without gaps, you must keep the channel and the asset enabled continuously.') }}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-r-xl border-l-4 fb-modal-rl-warning-box shadow-sm">
                    <div class="flex items-start gap-4">
                        <svg class="w-6 h-6 shrink-0 mt-0.5 fb-modal-rl-warning-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h3 class="text-base font-bold tracking-tight fb-modal-rl-warning-text">{{ __('Rate Limits & Inactive Assets') }}</h3>
                            <p class="text-sm mt-1 leading-relaxed fb-modal-rl-warning-subtext">
                                {{ __('Facebook\'s API rate limits are heavily influenced by the recent engagement your Pages and IG Accounts receive. Pages with a large volume of content but very low interaction face much stricter rate limits, increasing the risk of synchronization interruptions.') }} <strong class="font-semibold fb-modal-rl-warning-text">{{ __('We strongly recommend disabling inactive assets (those with minimal analytic value) to prevent rate limit bottlenecks and preserve your subscription quota.') }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Button --}}
            <div class="mt-4 flex justify-end">
                <button
                    type="button"
                    @click="localStorage.setItem('fb_organic_warnings_seen_v1', 'true'); showWarningModal = false"
                    class="px-5 py-2.5 bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg shadow-sm transition-colors cursor-pointer"
                >
                    {{ __('I understand') }}
                </button>
            </div>
        </div>
    </div>
</div>
