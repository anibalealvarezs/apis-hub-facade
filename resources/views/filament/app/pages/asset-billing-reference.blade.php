<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Understand how APIs Hub tracks your asset usage, the 2-hour testing grace period, and how your billing quota resets.') }}
        </div>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-calculator" class="h-5 w-5 text-primary-500" />
                    <span>{{ __('How Asset Billing Works') }}</span>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('Assets (such as ad accounts, social media profiles, or store connections) are the fundamental unit of billing in APIs Hub. Each subscription tier provides a specific quota of active assets.') }}
                </p>
                <p>
                    {{ __('When you connect and enable an asset, it consumes 1 unit of your available quota. However, to prevent you from being permanently penalized for simply testing a connection, assets go through a "Staged" phase before being permanently "Locked" into your ledger for the month.') }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 text-warning-500" />
                    <span>{{ __('The 2-Hour Grace Period (Staged Assets)') }}</span>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {!! __('When you first enable a new asset, it enters a :strong_start 2-hour grace period :strong_end (marked as "Staged").', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <ul>
                    <li>{{ __('During this time, you can test the sync, verify the incoming data, and ensure it is the correct account.') }}</li>
                    <li>{!! __('The 2-hour countdown :strong_start only ticks while your project server is deployed and running :strong_end. If your server is paused or hasn\'t deployed yet, the countdown is paused.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __('If you disable the asset before the 2 hours expire, it will be removed from your ledger and will :strong_start not :strong_end permanently consume your quota.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                </ul>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-lock-closed" class="h-5 w-5 text-danger-500" />
                    <span>{{ __('Locked Assets & Quota Consumption') }}</span>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {!! __('Once the 2-hour grace period expires, the asset is formally :strong_start Locked :strong_end into your billing ledger.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <p>
                    {{ __('A locked asset consumes 1 unit of your quota for the remainder of the current billing cycle. This policy prevents abuse where users might otherwise constantly rotate hundreds of assets through a single slot within the same month.') }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 text-success-500" />
                    <span>{{ __('Releasing Quota & Billing Rollover') }}</span>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {!! __('If you disable an asset that has already been locked, its status changes to :strong_start Pending Release :strong_end.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <ul>
                    <li>{!! __('While Pending Release, the asset remains inactive but :strong_start continues to occupy its quota slot :strong_end for the rest of the current billing month.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __('At the exact start of your :strong_start next billing cycle :strong_end (the monthly rollover), the system automatically clears all disabled assets from your ledger.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{{ __('This formally frees up your quota, allowing you to connect new assets for the new month.') }}</li>
                </ul>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-gray-500" />
                    <span>{{ __('Payment Failure Grace Period') }}</span>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {!! __('Separate from asset locking, APIs Hub offers a :strong_start 7-day global grace period :strong_end for payment issues.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <p>
                    {{ __('If your subscription renewal fails, your active projects and assets will continue to sync normally for 7 days. If the payment is not resolved within this window, your projects will be suspended until the billing issue is corrected.') }}
                </p>
            </div>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5 text-indigo-500" />
                    <span>{{ __('Annual vs. Monthly Subscriptions') }}</span>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {!! __('Whether you pay for your subscription on a monthly or annual basis, your asset quota ledger always rolls over :strong_start monthly :strong_end.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <ul>
                    <li>{!! __('For annual subscriptions, the system still wipes your pending_release assets and frees up your quota on the :strong_start anniversary day of each month :strong_end.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{{ __('This ensures you are never permanently locked out of swapping assets for an entire year.') }}</li>
                </ul>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-arrow-trending-up" class="h-5 w-5 text-blue-500" />
                    <span>{{ __('Upgrades & Downgrades') }}</span>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('APIs Hub ensures predictable billing dates regardless of when you change your plan:') }}
                </p>
                <ul>
                    <li><strong>{{ __('Upgrades:') }}</strong> {!! __('If you upgrade mid-cycle, you get immediate access to the higher tier\'s features and quota limits. You are instantly charged a prorated difference for the remaining time in your current cycle. Your billing cycle anchor date :strong_start does not change :strong_end.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li><strong>{{ __('Downgrades:') }}</strong> {{ __('If you downgrade mid-cycle, you maintain your current higher-tier benefits until the end of the current billing cycle. The downgrade, along with the reduced quota limits, takes effect at the start of your next cycle. The billing date remains the same.') }}</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
