<div class="space-y-3 mt-2 text-sm opacity-85">
    <p><strong>{{ __('What is this?') }}</strong> {{ __('Synthetic calculations use an algorithmic method to infer attribution data that Google Search Console actively removes from your reports to protect user privacy.') }}</p>

    <p><strong>{{ __('The Problem:') }}</strong> {{ __('When you look at GSC data by a single dimension (like Page), Google gives you close to 100% of the actual events. However, when you break data down by multiple dimensions simultaneously (like Page + Query + Country + Device), Google hides almost 50% of the records because those specific combinations might identify users.') }}</p>

    <p><strong>{{ __('Our Solution:') }}</strong> {{ __('We query every possible subset of Google\'s data and run a reconciliation algorithm to deduce the missing pieces. This provides an almost complete picture of your traffic at the most granular level possible.') }}</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-3 border-t border-gray-200 dark:border-gray-700/50">
        <div>
            <h4 class="font-medium flex items-center gap-1 text-emerald-500">✨ {{ __('The Benefits') }}</h4>
            <p class="mt-1 text-xs opacity-80">{{ __('Unlike Google, your totals will remain highly consistent no matter how deeply you filter or group the data. You get deep, granular attribution that is normally impossible to see.') }}</p>
        </div>
        <div>
            <h4 class="font-medium flex items-center gap-1 text-amber-500">⚠️ {{ __('The Trade-offs') }}</h4>
            <p class="mt-1 text-xs opacity-80">{!! __('Because this is an inference engine, expect a slight margin of error (~2% on average) compared to Google\'s top-level totals. Additionally, <strong>syncing will take roughly 10x longer</strong> to process all the required subsets. Finally, API usage will be significantly more intense, which increases the chances of facing rate limit issues or token invalidations.') !!}</p>
        </div>
    </div>
</div>
